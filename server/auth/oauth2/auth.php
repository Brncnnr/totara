<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Open ID authentication.
 *
 * @package auth_oauth2
 * @copyright 2017 Damyon Wiese
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

use auth_oauth2\api;
use auth_oauth2\linked_login;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * Plugin for oauth2 authentication.
 *
 * @package auth_oauth2
 * @copyright 2017 Damyon Wiese
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
final class auth_plugin_oauth2 extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'oauth2';
        $this->config = get_config('auth_oauth2');
    }

    /**
     * Returns true if the username and password work or don't exist and false
     * if the user exists and the password is wrong.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        return false;
    }

    /**
     * We don't want to allow users setting an internal password.
     *
     * @return bool
     */
    public function prevent_local_passwords() {
        return true;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
        return false;
    }

    /**
     * Indicates if Totara should automatically update internal user
     * records with data from external sources.
     *
     * @return bool true means automatically copy data from ext to user table
     */
    public function is_synchronised_with_external() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    public function change_password_url() {
        return null;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return false;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        return true;
    }

    /**
     * Do some checks on the identity provider before showing it on the login page.
     * @param \core\oauth2\issuer $issuer
     * @return boolean
     */
    private function is_ready_for_login_page(\core\oauth2\issuer $issuer) {
        return $issuer->get('enabled') &&
            $issuer->is_configured() &&
            !empty($issuer->get('showonloginpage'));
    }

    /**
     * Return a list of identity providers to display on the login page.
     *
     * @param string|moodle_url $wantsurl The requested URL.
     * @return array List of arrays with keys url, iconurl and name.
     */
    public function loginpage_idp_list($wantsurl) {
        global $OUTPUT;
        $providers = \core\oauth2\api::get_all_issuers();
        $result = [];
        if (empty($wantsurl)) {
            $wantsurl = '/';
        }
        foreach ($providers as $idp) {
            if ($this->is_ready_for_login_page($idp)) {
                $params = ['id' => $idp->get('id'), 'wantsurl' => $wantsurl, 'sesskey' => sesskey()];
                $url = new moodle_url('/auth/oauth2/login.php', $params);
                $template = ['url' => $url, 'name' => $idp->get('name'), 'authtype' => $this->authtype];

                // Totara: Show a hard-coded logo instead if it is explicitly set to be overridden for this issuer.
                if ($idp->get('show_default_branding')) {
                    $type = $idp->get('type');
                    $template['issuertype'] = $type;
                    $template['buttonimageurl'] = $OUTPUT->image_url('login_button/' . $type, 'auth_oauth2');
                } else {
                    $icon = $idp->get('image');
                    $template['iconurl'] = $icon;
                }

                $result[] = $template;
            }
        }
        return $result;
    }

    /**
     * If this user has no picture - but we got one from oauth - set it.
     *
     * @param string $oauthpicture
     * @return boolean True if the image was updated.
     */
    private function update_picture(string $oauthpicture): bool {
        global $CFG, $USER;

        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->libdir . '/gdlib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        if (!empty($USER->picture)) {
            return false;
        }
        if (!empty($CFG->enablegravatar)) {
            return false;
        }

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id, MUST_EXIST);
        $fs->delete_area_files($context->id, 'user', 'newicon');

        $filerecord = array(
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'newicon',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'image'
        );

        try {
            $fs->create_file_from_string($filerecord, $oauthpicture);
        } catch (\file_exception $e) {
            return get_string($e->errorcode, $e->module, $e->a);
        }

        $iconfile = $fs->get_area_files($context->id, 'user', 'newicon', false, 'itemid', false);

        // There should only be one.
        $iconfile = reset($iconfile);

        // Something went wrong while creating temp file - remove the uploaded file.
        if (!$iconfile = $iconfile->copy_content_to_temp()) {
            $fs->delete_area_files($context->id, 'user', 'newicon');
            return false;
        }

        // Copy file to temporary location and the send it for processing icon.
        $newpicture = (int) process_new_icon($context, 'user', 'icon', 0, $iconfile);
        // Delete temporary file.
        @unlink($iconfile);
        // Remove uploaded file.
        $fs->delete_area_files($context->id, 'user', 'newicon');
        // Set the user's picture.
        $updateuser = new stdClass();
        $updateuser->id = $USER->id;
        $updateuser->picture = $newpicture;
        user_update_user($updateuser);
        $USER->picture = (string)$updateuser->picture;

        return true;
    }

    /**
     * Update user data according to data sent by authorization server.
     *
     * @param array $externaldata data from authorization server
     * @param stdClass $userdata Current data of the user to be updated
     * @return array The updated get_complete_user_data() result, or the existing one if there's nothing to be updated.
     */
    private function update_user(array $externaldata, $userdata): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $user = (object) [
            'id' => $userdata->id,
        ];

        // We can only update if the default authentication type of the user is set to OAuth2 as well. Otherwise, we might mess
        // up the user data of other users that use different authentication mechanisms (e.g. linked logins).
        if ($userdata->auth !== $this->authtype) {
            return (array)get_complete_user_data('id', $userdata->id);
        }

        // Go through each field from the external data.
        foreach ($externaldata as $fieldname => $value) {
            if ($fieldname === 'username') {
                // Local username is not used, we are using random string with oauth2_ prefix instead.
                // Make sure it gets skipped even if added to $this->>userfields.
                continue;
            }
            if (!in_array($fieldname, $this->userfields)) {
                // Skip if this field doesn't belong to the list of fields that can be synced with the OAuth2 issuer.
                continue;
            }

            if (!property_exists($userdata, $fieldname)) {
                // Just in case this field is on the list, but not part of the user data. This shouldn't happen though.
                continue;
            }

            // OAuth 2 servers might not be controlled by Totara admins, better sanitise all incoming data.
            $prop = \core_user::get_property_definition($fieldname);
            $user->$fieldname = clean_param($value, $prop['type']);

            // Get the old value.
            $oldvalue = (string)$userdata->$fieldname;

            // Get the lock configuration of the field.
            $lockvalue = $this->config->{'field_lock_' . $fieldname};

            // We should update fields that meet the following criteria:
            // - Lock value set to 'unlocked'; or 'unlockedifempty', given the current value is empty.
            // - The value has changed.
            if ($lockvalue === 'unlocked' || ($lockvalue === 'unlockedifempty' && empty($oldvalue))) {
                $value = (string)$value;
                if ($oldvalue !== $value) {
                    $user->$fieldname = $value;
                }
            }
        }
        // Update the user data.
        user_update_user($user, false);

        // Save user profile data.
        profile_save_data($user);

        // Refresh user for $USER variable.
        return (array) get_complete_user_data('id', $user->id);
    }

    /**
     * Complete the login process after oauth handshake is complete.
     * @param \core\oauth2\client $client
     * @param string|moodle_url $redirecturl
     * @return void Either redirects or throws an exception
     */
    public function complete_login(\core\oauth2\client $client, $redirecturl) {
        global $SESSION, $DB, $USER, $CFG;

        if (!is_enabled_auth('oauth2')) {
            throw new \moodle_exception('notenabled', 'auth_oauth2');
        }

        $userinfo = $client->get_userinfo();

        if (!$userinfo) {
            // Trigger login failed event.
            $failurereason = AUTH_LOGIN_NOUSER;
            $event = \core\event\user_login_failed::create(['other' => ['username' => 'unknown',
                'reason' => $failurereason]]);
            $event->trigger();

            $errormsg = get_string('loginerror_nouserinfo', 'auth_oauth2');
            $SESSION->loginerrormsg = $errormsg;
            $client->log_out();
            redirect(new moodle_url('/login/index.php'));
        }
        if (empty($userinfo['username']) || empty($userinfo['email'])) {
            // Trigger login failed event.
            $failurereason = AUTH_LOGIN_NOUSER;
            $event = \core\event\user_login_failed::create(['other' => ['username' => 'unknown',
                'reason' => $failurereason]]);
            $event->trigger();

            $errormsg = get_string('loginerror_userincomplete', 'auth_oauth2');
            $SESSION->loginerrormsg = $errormsg;
            $client->log_out();
            redirect(new moodle_url('/login/index.php'));
        }

        // Do NOT mangle the external username in any way, we use it to identify external accounts,
        // it MUST not ever change in external system after local account is created or linked.
        $oauthusername = $userinfo['username'];
        // Email is used to map external account to local Totara account when creating new accounts.
        $oauthemail = $userinfo['email'];

        // Clean and remember the picture / lang.
        $oauthpicture = false;
        if (!empty($userinfo['picture'])) {
            $oauthpicture = $userinfo['picture'];
        }
        unset($userinfo['picture']);

        if (!empty($userinfo['lang'])) {
            $userinfo['lang'] = str_replace('-', '_', trim(core_text::strtolower($userinfo['lang'])));
            if (!get_string_manager()->translation_exists($userinfo['lang'], false)) {
                unset($userinfo['lang']);
            }
        }

        // First we try and find a defined mapping.
        $params = [
            'issuerid' => $client->get_issuer()->get('id'),
            'username' => $oauthusername,
        ];
        $linkedlogin = linked_login::get_record($params);
        if (!$linkedlogin) {
            // Previously the external username was incorrectly lowercased, fix it if necessary.
            $lowercaseusername = core_text::strtolower($oauthusername);
            if ($lowercaseusername !== $oauthusername) {
                $params['username'] = $lowercaseusername;
                $linkedlogin = linked_login::get_record($params);
                if ($linkedlogin) {
                    $linkedlogin->set('username', $oauthusername);
                    $linkedlogin->save();
                }
            }
        }
        if (!$linkedlogin) {
            $linkedlogin = null;
        }

        $mappeduser = false;
        $candidateuser = false;
        if ($linkedlogin && $linkedlogin->get('userid')) {
            $mappeduser = $DB->get_record('user', ['id' => $linkedlogin->get('userid')]);
            if (!$mappeduser || $mappeduser->deleted || !$mappeduser->confirmed) {
                // User record is not valid any more, do NOT recreate the account.
                $event = \core\event\user_login_failed::create(['other' => ['username' => $oauthusername, 'reason' => AUTH_LOGIN_UNAUTHORISED]]);
                $event->trigger();
                $reason = get_string('loginerror_cannotcreateaccounts', 'auth_oauth2');
                $errormsg = get_string('notloggedindebug', 'auth_oauth2', $reason);
                $SESSION->loginerrormsg = $errormsg;
                $client->log_out();
                redirect(get_login_url());
            }
            if ($mappeduser->suspended || $mappeduser->auth === 'nologin' || !is_enabled_auth($mappeduser->auth)) {
                // We need to respect suspended flag and disabled auths here.
                $event = \core\event\user_login_failed::create(['userid' => $mappeduser->id, 'other' => ['username' => $oauthusername, 'reason' => AUTH_LOGIN_SUSPENDED]]);
                $event->trigger();
                $SESSION->loginerrormsg = get_string('invalidlogin');
                $client->log_out();
                redirect(get_login_url());
            }
        }
        if (!$mappeduser) {
            $candidateusers = $DB->get_records_select('user', "LOWER(email) = LOWER(:email) AND deleted = 0 AND confirmed = 1", ['email' => $oauthemail]);
            if (count($candidateusers) == 1) {
                // One candidate found means we will be linking the account (CFG->allowaccountssameemail is irrelevant here),
                // we do not care here about conflicts of Totara usernames because we do not use them here.
                $candidateuser = reset($candidateusers);
                if (!get_config('auth_oauth2', 'allowautolinkingexisting')) {
                    // If the email is taken, bad luck - linking is not allowed,
                    // let's not even consider creating accounts with duplicate emails here.
                    $event = \core\event\user_login_failed::create(['userid' => $candidateuser->id, 'other' => ['username' => $oauthusername, 'reason' => AUTH_LOGIN_UNAUTHORISED]]);
                    $event->trigger();
                    $SESSION->loginerrormsg = get_string('accountwithemailexists', 'auth_oauth2');
                    $client->log_out();
                    redirect(get_login_url());
                }
                if ($candidateuser->suspended || $candidateuser->auth === 'nologin' || !is_enabled_auth($candidateuser->auth)) {
                    // We need to respect suspended flag and disabled auths here.
                    $event = \core\event\user_login_failed::create(['userid' => $candidateuser->id, 'other' => ['username' => $oauthusername, 'reason' => AUTH_LOGIN_SUSPENDED]]);
                    $event->trigger();
                    $SESSION->loginerrormsg = get_string('invalidlogin');
                    $client->log_out();
                    redirect(get_login_url());
                }
            } else if (count($candidateusers) > 1) {
                // More than one user is sharing this email, bad luck, no oauth account for them!
                $event = \core\event\user_login_failed::create(['other' => ['username' => $oauthusername, 'reason' => AUTH_LOGIN_UNAUTHORISED]]);
                $event->trigger();
                $reason = get_string('loginerror_cannotcreateaccounts', 'auth_oauth2');
                $errormsg = get_string('notloggedindebug', 'auth_oauth2', $reason);
                $SESSION->loginerrormsg = $errormsg;
                $client->log_out();
                redirect(get_login_url());
            } else {
                // No candidate account found, we will be creating a new Totara account.
                if (empty($this->config->allowaccountcreation)) {
                    // Account creation is prevented.
                    $event = \core\event\user_login_failed::create(['other' => ['username' => $oauthusername, 'reason' => AUTH_LOGIN_UNAUTHORISED]]);
                    $event->trigger();
                    $reason = get_string('loginerror_cannotcreateaccounts', 'auth_oauth2');
                    $errormsg = get_string('notloggedindebug', 'auth_oauth2', $reason);
                    $SESSION->loginerrormsg = $errormsg;
                    $client->log_out();
                    redirect(get_login_url());
                }
            }
            unset($candidateusers);
        }

        // Make sure the email is allowed if 'alloweddomains' is set in issuer configuration.
        $issuer = $client->get_issuer();
        if (!$issuer->is_valid_login_domain($oauthemail)) {
            $event = \core\event\user_login_failed::create(['other' => ['username' => $oauthusername, 'reason' => AUTH_LOGIN_UNAUTHORISED]]);
            $event->trigger();
            $errormsg = get_string('notloggedindebug', 'auth_oauth2', get_string('loginerror_invaliddomain', 'auth_oauth2'));
            $SESSION->loginerrormsg = $errormsg;
            $client->log_out();
            redirect(new moodle_url('/login/index.php'));
        }

        if ($mappeduser) {
            // We have user and login link already, just make sure they are confirmed and log them in if yes.
            if (!$linkedlogin->get('confirmed')) {
                // User did not confirm the account yet, fail the login.
                $event = \core\event\user_login_failed::create(['other' => ['username' => $oauthusername, 'reason' => AUTH_LOGIN_NOTCONFIRMED]]);
                $event->trigger();
                if ($linkedlogin->get('confirmtoken') === '' || $linkedlogin->get('confirmtokenexpires') < time()) {
                    // Resend the confirmation link, this happens also
                    // after upgrade that moves away from user.confirmed flag.
                    api::send_confirm_link_login_email($userinfo, $issuer, $mappeduser);
                }
                $SESSION->loginerrormsg = get_string('confirmationpending', 'auth_oauth2');
                $client->log_out();
                redirect(get_login_url());
            }
        } else if ($candidateuser) {
            if ($issuer->get('requireconfirmation')) {
                if (!$linkedlogin || !$linkedlogin->get('confirmed')) {
                    // User did not confirm the account yet, fail the login.
                    $event = \core\event\user_login_failed::create(['other' => ['username' => $oauthusername, 'reason' => AUTH_LOGIN_NOTCONFIRMED]]);
                    $event->trigger();
                    if (!$linkedlogin || $linkedlogin->get('confirmtoken') === '' || $linkedlogin->get('confirmtokenexpires') < time()) {
                        // Ask them to confirm the future account.
                        api::send_confirm_link_login_email($userinfo, $issuer, $candidateuser);
                    }
                    $SESSION->loginerrormsg = get_string('confirmationpending', 'auth_oauth2');
                    $client->log_out();
                    redirect(get_login_url());
                }
            }
            $linkedlogin = api::link_login($userinfo, $issuer, $candidateuser);
            $mappeduser = $candidateuser;
        } else {
            if ($issuer->get('requireconfirmation')) {
                if (!$linkedlogin || !$linkedlogin->get('confirmed')) {
                    // User did not confirm the account yet, fail the login.
                    $event = \core\event\user_login_failed::create(['other' => ['username' => $oauthusername, 'reason' => AUTH_LOGIN_NOTCONFIRMED]]);
                    $event->trigger();
                    if (!$linkedlogin || $linkedlogin->get('confirmtoken') === '' || $linkedlogin->get('confirmtokenexpires') < time()) {
                        // Ask them to confirm the future account.
                        api::send_confirm_account_email($userinfo, $issuer);
                    }
                    $SESSION->loginerrormsg = get_string('confirmationpending', 'auth_oauth2');
                    $client->log_out();
                    redirect(get_login_url());
                }
            }
            $mappeduser = api::create_new_confirmed_account($userinfo, $issuer, $linkedlogin);
        }

        // We used to call authenticate_user - but that won't work if the current user has a different default authentication
        // method. Since we now ALWAYS link a login - if we get to here we can directly allow the user in.
        $user = get_complete_user_data('id', $mappeduser->id);
        $user = (object)$this->update_user($userinfo, $user);
        complete_user_login($user);

        if ($oauthpicture) {
            $this->update_picture($oauthpicture);
        }

        // No URL means validation of PARAM_LOCALURL failed in calling code, in that case go to homepage.
        $redirecturl = ($redirecturl === '') ? new moodle_url('/') : $redirecturl;

        if (user_not_fully_set_up($USER, true)) {
            unset($SESSION->wantsurl);
            $redirecturl = $CFG->wwwroot . '/user/edit.php?returnurl=' . urlencode($redirecturl);
        }

        redirect($redirecturl);
    }

    /**
     * Returns information on how the specified user can change their password.
     * The password of the oauth2 accounts is not stored in Totara.
     *
     * @param stdClass $user A user object
     * @return string[] An array of strings with keys subject and message
     */
    public function get_password_change_info(stdClass $user): array {
        $site = get_site();

        $data = new stdClass();
        $data->firstname = clean_string($user->firstname);
        $data->lastname  = clean_string($user->lastname);
        // The local username is irrelevant to this auth plugin,
        // do not add it to the ['emailpasswordchangeinfo', 'auth_oauth2'] string!
        $data->username  = clean_string($user->username);
        $data->sitename  = format_string($site->fullname);
        $data->admin     = generate_email_signoff();

        $message = get_string('emailpasswordchangeinfo', 'auth_oauth2', $data);
        $subject = get_string('emailpasswordchangeinfosubject', 'auth_oauth2', format_string($site->fullname));

        return [
            'subject' => $subject,
            'message' => $message
        ];
    }
}
