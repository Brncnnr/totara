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
 * Class for loading/storing oauth2 linked logins from the DB.
 *
 * @package    auth_oauth2
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace auth_oauth2;

use context_user;
use stdClass;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Static list of api methods for auth oauth2 configuration.
 *
 * @package    auth_oauth2
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class api {
    /** @var int how much time users have to confirm account linking or creation in seconds */
    public const CONFIRMATION_EXPIRY = 60 * 30;

    /**
     * Link a login to this account.
     *
     * NOTE: this is a low level API that does not check permissions.
     *
     * @param array $userinfo as returned from an oauth client.
     * @param \core\oauth2\issuer $issuer
     * @param stdClass $localuser
     * @return linked_login
     * @throws moodle_exception if account cannot be linked
     */
    public static function link_login(array $userinfo, \core\oauth2\issuer $issuer, stdClass $localuser): linked_login {
        $existing = linked_login::get_record(['issuerid' => $issuer->get('id'), 'userid' => $localuser->id]);
        if ($existing) {
            if ($existing->get('confirmed')) {
                // User is already linked.
                throw new moodle_exception('accountalreadylinked', 'auth_oauth2');
            }
            $existing->set('username', $userinfo['username']);
            $existing->set('email', $userinfo['email']);
            $existing->set('confirmed', '1');
            $existing->set('confirmtoken', '');
            $existing->set('confirmtokenexpires', null);
            $existing->update();
            return $existing;
        }

        $existing = linked_login::get_record(['issuerid' => $issuer->get('id'), 'username' => $userinfo['username']]);
        if ($existing) {
            if ($existing->get('confirmed')) {
                // Some other user is linked and confirmed, we cannot hijack it.
                throw new moodle_exception('accountalreadylinked', 'auth_oauth2');
            }
            // Take over any unconfirmed link.
            $existing->set('email', $userinfo['email']);
            $existing->set('userid', $localuser->id);
            $existing->set('confirmed', '1');
            $existing->set('confirmtoken', '');
            $existing->set('confirmtokenexpires', null);
            $existing->update();
            return $existing;
        }

        $record = new stdClass();
        $record->issuerid = $issuer->get('id');
        $record->username = $userinfo['username'];
        $record->userid = $localuser->id;
        $record->email = $userinfo['email'];
        $record->confirmed = '1';
        $record->confirmtoken = '';
        $record->confirmtokenexpires = null;
        $linkedlogin = new linked_login(0, $record);
        return $linkedlogin->create();
    }

    /**
     * Can current user link new issuer?
     *
     * @param int $issuerid
     * @return bool
     */
    public static function can_link_login(int $issuerid): bool {
        global $USER;

        if (!$issuerid) {
            return false;
        }

        if (\core\session\manager::is_loggedinas()) {
            return false;
        }

        $issuer = \core\oauth2\api::get_issuer($issuerid);
        if (!$issuer->is_login_enabled()) {
            return false;
        }

        $existing = linked_login::get_record(['issuerid' => $issuerid, 'userid' => $USER->id]);
        if ($existing && $existing->get('confirmed')) {
            return false;
        }

        $usercontext = context_user::instance($USER->id);
        if (has_capability('auth/oauth2:managelinkedlogins', $usercontext)) {
            return true;
        }

        return false;
    }

    /**
     * Send an email with a link to confirm linking this account.
     *
     * @param array $userinfo as returned from an oauth client.
     * @param \core\oauth2\issuer $issuer
     * @param \stdClass $localuser
     * @return bool success
     */
    public static function send_confirm_link_login_email(array $userinfo, \core\oauth2\issuer $issuer, \stdClass $localuser): bool {
        $localuser = clone($localuser);
        $localuser->mailformat = 1;  // Always send HTML version as well.

        $linkedlogin = linked_login::get_record(['issuerid' => $issuer->get('id'), 'username' => $userinfo['username']]);

        if ($linkedlogin) {
            // Most likely user tries again after token expired.
            if (!$linkedlogin->get('userid') || $linkedlogin->get('userid') != $localuser->id) {
                throw new \coding_exception('Invalid local user id, it cannot change in existing confirmation');
            }
            if ($linkedlogin->get('confirmed')) {
                return false;
            }
            $linkedlogin->set('confirmed', 0);
            $linkedlogin->set('confirmtoken', random_string(32));
            $linkedlogin->set('confirmtokenexpires', time() + self::CONFIRMATION_EXPIRY);
            $linkedlogin->update();
        } else {
            // First attempt.
            $record = new stdClass();
            $record->userid = $localuser->id;
            $record->issuerid = $issuer->get('id');
            $record->username = $userinfo['username'];
            $record->email = $userinfo['email'];
            $record->confirmed = 0;
            $record->confirmtoken = random_string(32);
            $record->confirmtokenexpires = time() + self::CONFIRMATION_EXPIRY;
            $linkedlogin = new linked_login(0, $record);
            $linkedlogin->create();
        }

        $site = get_site();
        $supportuser = \core_user::get_support_user();

        // Construct the email.
        $data = new stdClass();
        $data->fullname = fullname($localuser);
        $data->sitename  = format_string($site->fullname);
        $data->admin     = generate_email_signoff();
        $data->issuername = format_string($issuer->get('name'));
        $data->linkedemail = clean_string($userinfo['email']);

        $subject = get_string('confirmlinkedloginemailsubject', 'auth_oauth2', format_string($site->fullname));

        $params = [
            'token' => $linkedlogin->get('confirmtoken'),
            'linkid' => $linkedlogin->get('id'),
        ];
        $confirmationurl = new moodle_url('/auth/oauth2/confirm-linkedlogin.php', $params);

        $confirmurl = $confirmationurl->out(false);

        $data->link = $confirmurl;
        $message = get_string('confirmlinkedloginemail', 'auth_oauth2', $data);

        $data->link = "[$confirmurl]($confirmurl)";
        $messagehtml = markdown_to_html(get_string('confirmlinkedloginemail', 'auth_oauth2', $data));

        // Directly email rather than using the messaging system to ensure its not routed to a popup or jabber.
        return email_to_user($localuser, $supportuser, $subject, $message, $messagehtml);
    }

    /**
     * Look for a waiting confirmation token, and if we find a match - confirm it.
     *
     * @param int $linkid
     * @param string $token
     * @return stdClass|null local user record if confirmed, NULL if not
     */
    public static function confirm_link_login(int $linkid, string $token): ?stdClass {
        global $DB;

        $login = linked_login::get_record(['id' => $linkid]);
        if (!$login) {
            return null;
        }
        if (!$login->get('userid')) {
            return null;
        }

        // Make sure user is active.
        $user = $DB->get_record('user', ['id' => $login->get('userid')]);
        if (!$user || !$user->confirmed || $user->deleted || $user->suspended || $user->auth === 'nologin' || !is_enabled_auth($user->auth)) {
            return null;
        }

        if (!$login->get('confirmed')) {
            if ($login->get('confirmtoken') === '') {
                return null;
            }
            if ($login->get('confirmtoken') !== $token) {
                return null;
            }
            if (time() > $login->get('confirmtokenexpires')) {
                return null;
            }
            $login->set('confirmed', 1);
            $login->set('confirmtoken', '');
            $login->set('confirmtokenexpires', null);
            $login->update();
        }

        return $user;
    }


    /**
     * Send an email with a link to confirm creating this account.
     *
     * @param array $userinfo as returned from an oauth client.
     * @param \core\oauth2\issuer $issuer
     * @return bool success
     */
    public static function send_confirm_account_email(array $userinfo, \core\oauth2\issuer $issuer): bool {
        $linkedlogin = linked_login::get_record(['issuerid' => $issuer->get('id'), 'username' => $userinfo['username']]);
        if ($linkedlogin) {
            // Most likely user tries again after token expired.
            if ($linkedlogin->get('userid')) {
                return false;
            }
            $linkedlogin->set('confirmed', 0);
            $linkedlogin->set('confirmtoken', random_string(32));
            $linkedlogin->set('confirmtokenexpires', time() + self::CONFIRMATION_EXPIRY);
            $linkedlogin->update();
        } else {
            // First attempt.
            $record = new stdClass();
            $record->userid = null;
            $record->issuerid = $issuer->get('id');
            $record->username = $userinfo['username'];
            $record->email = $userinfo['email'];
            $record->confirmed = 0;
            $record->confirmtoken = random_string(32);
            $record->confirmtokenexpires = time() + self::CONFIRMATION_EXPIRY;
            $linkedlogin = new linked_login(0, $record);
            $linkedlogin->create();
        }

        // Construct the email.
        $site = get_site();
        $supportuser = \core_user::get_support_user();
        $user = \totara_core\totara_user::get_external_user($userinfo['email']);
        $user->mailformat = 1;  // Always send HTML version as well.

        $data = new stdClass();
        $data->fullname = clean_string($userinfo['email']);
        $data->sitename = format_string($site->fullname);
        $data->admin = generate_email_signoff();

        $subject = get_string('confirmaccountemailsubject', 'auth_oauth2', format_string($site->fullname));

        $params = [
            'token' => $linkedlogin->get('confirmtoken'),
            'linkid' => $linkedlogin->get('id'),
        ];
        $confirmationurl = new moodle_url('/auth/oauth2/confirm-account.php', $params);

        $confirmurl = $confirmationurl->out(false);

        $data->link = $confirmurl;
        $message = get_string('confirmaccountemail', 'auth_oauth2', $data);

        $data->link = "[$confirmurl]($confirmurl)";
        $messagehtml = markdown_to_html(get_string('confirmaccountemail', 'auth_oauth2', $data));

        // Directly email rather than using the messaging system to ensure its not routed to a popup or jabber.
        email_to_user($user, $supportuser, $subject, $message, $messagehtml);
        return true;
    }

    /**
     * Create an account with a linked login that is already confirmed.
     *
     * @param array $userinfo as returned from an oauth client.
     * @param \core\oauth2\issuer $issuer
     * @param linked_login|null $linkedlogin
     * @return \stdClass user record
     */
    public static function create_new_confirmed_account(array $userinfo, \core\oauth2\issuer $issuer, ?linked_login $linkedlogin): stdClass {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/user/profile/lib.php');

        if ($linkedlogin && $linkedlogin->get('userid')) {
            throw new \coding_exception('User account for linked_login already exists');
        }

        do {
            // Create random username with known prefix to prevent conflicts,
            // we can do this because the internal username is never used for log in.
            $username = 'oauth2_' . $issuer->get('id') . '_' . strtolower(random_string(16));
        } while ($DB->record_exists('user', ['username' => $username]));

        $user = new stdClass();
        $user->confirmed = 1;
        $user->suspended = 0;
        $user->deleted = 0;
        $user->username = $username;
        $user->email = $userinfo['email'];
        $user->auth = 'oauth2';
        $user->lastname = isset($userinfo['lastname']) ? $userinfo['lastname'] : '';
        $user->firstname = isset($userinfo['firstname']) ? $userinfo['firstname'] : '';
        $user->url = isset($userinfo['url']) ? $userinfo['url'] : '';
        $user->alternatename = isset($userinfo['alternatename']) ? $userinfo['alternatename'] : '';

        // Sanitise all external data properly!
        foreach ((array)$user as $k => $v) {
            $prop = \core_user::get_property_definition($k);
            $user->$k = clean_param($v, $prop['type']);
        }

        $trans = $DB->start_delegated_transaction();

        $userid = user_create_user($user, false, true);

        if ($linkedlogin) {
            $linkedlogin->set('username', $userinfo['username']);
            $linkedlogin->set('userid', $userid);
            $linkedlogin->set('confirmed', 1);
            $linkedlogin->set('confirmtoken', '');
            $linkedlogin->set('confirmtokenexpires', null);
            $linkedlogin->update();
        } else {
            $record = new stdClass();
            $record->userid = $userid;
            $record->issuerid = $issuer->get('id');
            $record->username = $userinfo['username'];
            $record->email = $userinfo['email'];
            $record->confirmed = 1;
            $record->confirmtoken = '';
            $record->confirmtokenexpires = null;
            $linkedlogin = new linked_login(0, $record);
            $linkedlogin->create();
        }

        $trans->allow_commit();

        return $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    }

    /**
     * Look for a waiting confirmation token, and if we find a match - confirm it.
     *
     * @param int $linkid
     * @param string $token
     * @return bool success
     */
    public static function confirm_new_account(int $linkid, string $token): bool {
        $login = linked_login::get_record(['id' => $linkid]);
        if (!$login) {
            return false;
        }

        if (!$login->get('confirmed')) {
            if ($login->get('confirmtoken') === '') {
                return false;
            }
            if ($login->get('confirmtoken') !== $token) {
                return false;
            }
            if (time() > $login->get('confirmtokenexpires')) {
                return false;
            }
            $issuer = \core\oauth2\issuer::get_record(['id' => $login->get('issuerid')]);
            $userinfo = ['username' => $login->get('username'), 'email' => $login->get('email')];
            return (null !== self::create_new_confirmed_account($userinfo, $issuer, $login));
        }

        return true;
    }

    /**
     * Can current user delete linked login?
     *
     * @param int|linked_login|stdClass $linkedlogin
     * @return bool
     */
    public static function can_delete_linked_login($linkedlogin): bool {
        global $USER;

        if (\core\session\manager::is_loggedinas()) {
            return false;
        }

        if (is_numeric($linkedlogin)) {
            $linkedlogin = linked_login::get_record(['id' => $linkedlogin]);
        }
        if (!$linkedlogin) {
            return false;
        }
        if ($linkedlogin instanceof linked_login) {
            $linkedlogin = $linkedlogin->to_record();
        }
        $linkedlogin = (object)$linkedlogin;

        if ($USER->id == $linkedlogin->userid && is_enabled_auth('oauth2')) {
            $usercontext = context_user::instance($linkedlogin->userid);
            if (has_capability('auth/oauth2:managelinkedlogins', $usercontext)) {
                if ($USER->auth === 'oauth2' && $linkedlogin->confirmed) {
                    // Do not allow deleting of the last link to prevent account lockouts!
                    $existing = linked_login::get_records(['userid' => $linkedlogin->userid, 'confirmed' => 1]);
                    if (count($existing) < 2) {
                        return false;
                    }
                }
                return true;
            }
        }

        $usercontext = context_user::instance($linkedlogin->userid, IGNORE_MISSING);
        if (!$usercontext) {
            // Deleted user, use system context.
            $usercontext = \context_system::instance();
        }
        if (has_capability('auth/oauth2:deletelinkedlogins', $usercontext)) {
            // Allow deleting even if it is the last link here.
            return true;
        }

        return false;
    }

    /**
     * Delete linked login
     *
     * @param int $linkedloginid
     * @return bool success, true if already deleted
     */
    public static function delete_linked_login(int $linkedloginid): bool {
        $login = linked_login::get_record(['id' => $linkedloginid]);
        if (!$login) {
            return true;
        }
        return $login->delete();
    }

    /**
     * Returns list of login compatible issuers.
     *
     * @return array
     */
    public static function get_login_issuers_menu(): array {
        $result = [];
        $issuers = \core\oauth2\issuer::get_records([], 'sortorder', 'ASC');
        foreach ($issuers as $issuer) {
            if ($issuer->is_login_enabled()) {
                $result[$issuer->get('id')] = format_string($issuer->get('name'));
            }
        }
        return $result;
    }

    /**
     * List linked logins that current user may see,
     * only confirmed links are included
     *
     * @return linked_login[] indexed with issuerid
     */
    public static function get_visible_linked_logins(): array {
        global $USER, $DB;

        // Use db records to work around sorting issue.
        $records = $DB->get_records_sql(
            'SELECT ll.*
               FROM "ttr_auth_oauth2_linked_login" ll
               JOIN "ttr_oauth2_issuer" i ON i.id = ll.issuerid
              WHERE ll.confirmed = 1 AND ll.userid = :userid
           ORDER BY i.sortorder ASC', ['userid' => $USER->id]
        );

        $result = [];
        foreach ($records as $record) {
            $linkedlogin = new linked_login(0, $record);
            $issuer = \core\oauth2\api::get_issuer($record->issuerid);
            if ($issuer->is_login_enabled()) {
                $result[$record->issuerid] = $linkedlogin;
            }
        }

        return $result;
    }
}
