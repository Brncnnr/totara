<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\local;

use totara_mobile\event\fcmtoken_removed;
use core\orm\query\builder;

/**
 * Class with mobile device related methods.
 */
final class device {
    /** @var int character length of API key */
    const API_KEY_LENGTH = 50;

    /** @var int character length of setup secret*/
    const SETUP_SECRET_LENGTH = 30;

    /** @var int number of seconds specifying how long the setup secret is valid  */
    const SETUP_SECRET_VALIDITY = 60 * 60;

    /** @var int number of seconds specifying how long the setup secret is valid  */
    const LOGIN_SECRET_VALIDITY = 60 * 20;

    /** @var string default url scheme value (overridden by 'totara_mobile | urlscheme' setting) */
    const DEFAULT_URL_SCHEME = 'https://mobile.totaralearning.com/register';

    /**
     * Generate a secret string for verification
     *
     * @param string $dbtable - The table to check for duplicates
     * @param string $dbfield - The field to check for duplicates
     * @return string         - The secret used for the login
     */
    private static function generate_secret(string $dbtable, string $dbfield) {
        global $DB;

        if (empty($dbtable) || empty($dbfield)) {
            throw new \coding_exception('invalid parameters handed to generate_secret()');
        }

        do {
            $secret = random_string(self::SETUP_SECRET_LENGTH);
        } while ($DB->record_exists($dbtable, [$dbfield => $secret]));

        return $secret;
    }

    /**
     * Generate a request record for the given userid
     *
     * @param int $userid
     * @return string
     */
    private static function generate_request(int $userid) {
        global $DB;

        $trans = $DB->start_delegated_transaction();

        // Create external device record, without API key.
        $request = new \stdClass();
        $request->userid = $userid;
        $request->setupsecret = self::generate_secret('totara_mobile_requests', 'setupsecret');
        $request->timecreated = time();
        $request->id = $DB->insert_record('totara_mobile_requests', $request);

        $trans->allow_commit();

        return $request->setupsecret;
    }

    /**
     * Generate the registration entry linking the user and apikey.
     *
     * @param int $userid - the id of the user
     * @return string     - the api key for the record
     */
    private static function generate_registration(int $userid) {
        global $DB;

        $trans = $DB->start_delegated_transaction();

        // Create external device record, with an API key.
        $device = new \stdClass();
        $device->userid = $userid;
        do {
            $device->keyprefix = random_string(10);
        } while ($DB->record_exists_select('totara_mobile_devices', "LOWER(keyprefix) = LOWER(:keyprefix)", ['keyprefix' => $device->keyprefix]));
        $apikey = $device->keyprefix . random_string(self::API_KEY_LENGTH - 10);
        $device->timeregistered = time();
        // NOTE: this is not a regular password hashing, it needs to be less costly because we have to check
        //       the API key on every request. Hash collisions, weak salts and rainbow tables should not worry us here.
        $device->keyhash = sha1($apikey);

        $device->id = $DB->insert_record('totara_mobile_devices', $device);

        $trans->allow_commit();

        return $apikey;
    }

    /**
     * Request a new setup secret to register mobile app.
     *
     * @return string setup secret
     */
    public static function request(): string {
        global $USER;

        if (!isloggedin() or isguestuser()) {
            throw new \coding_exception('invalid use of mobile app registration request');
        }

        return self::generate_request($USER->id);
    }

    /**
     * Link for mobile app registration from mobile web browser.
     *
     * @param string $setupsecret
     * @return \moodle_url
     */
    public static function get_universal_link_register_url(string $setupsecret): \moodle_url {
        global $CFG;
        $url_scheme = get_config('totara_mobile', 'urlscheme');
        if (empty($url_scheme)) {
            $url_scheme = self::DEFAULT_URL_SCHEME;
        }
        return new \moodle_url($url_scheme, ['site' => $CFG->wwwroot . '/', 'setupsecret' => $setupsecret]);
    }

    /**
     * Register device using secret key.
     *
     * @param string $setupsecret value obtained from self::request()
     * @return string|null api key on success, null if anything fails
     */
    public static function register(string $setupsecret) : ?string {
        global $DB;

        // NOTE: there are no error messages on failure for security reasons.

        if (strlen($setupsecret) !== self::SETUP_SECRET_LENGTH) {
            return null;
        }

        // Delete all expired requests.
        $DB->delete_records_select('totara_mobile_requests', "timecreated < ?", [time() - self::SETUP_SECRET_VALIDITY]);

        $request = $DB->get_record('totara_mobile_requests', ['setupsecret' => $setupsecret]);
        if (!$request) {
            return null;
        }

        // Only use requests once!
        $DB->delete_records('totara_mobile_requests', ['id' => $request->id]);

        return self::generate_registration($request->userid);
    }

    /**
     * Request a new login secret to register login requests.
     *
     * @return string login secret
     */
    public static function login_setup(): string {
        global $DB;

        if (isloggedin() or isguestuser()) {
            throw new \coding_exception('invalid use of mobile app login request');
        }

        $trans = $DB->start_delegated_transaction();

        // Create a login request, limited time secret with no associated userid.
        $request = new \stdClass();
        $request->loginsecret = self::generate_secret('totara_mobile_tokens', 'loginsecret');
        $request->timecreated = time();
        $request->id = $DB->insert_record('totara_mobile_tokens', $request);

        $trans->allow_commit();

        return $request->loginsecret;
    }

    /**
     * Log in mobile user using secret key.
     *
     * @param string $setupsecret value obtained from self::request()
     * @param string $username
     * @param string $password
     * @return string|null api key on success, null if anything fails
     */
    public static function login(string $loginsecret, string $username, string $password) : ?string {
        global $DB;

        // NOTE: there are no error messages on failure for security reasons.

        if (strlen($loginsecret) !== self::SETUP_SECRET_LENGTH) {
            return null;
        }

        // Delete any expired requests before checking for the associated request, so we don't use it if it has expired.
        $DB->delete_records_select('totara_mobile_tokens', "timecreated < ?", [time() - self::LOGIN_SECRET_VALIDITY]);

        $request = $DB->get_record('totara_mobile_tokens', ['loginsecret' => $loginsecret]);
        if (!$request) {
            return null;
        }
        // Use only once!
        $DB->delete_records('totara_mobile_tokens', ['id' => $request->id]);

        // Check the user's auth setting is set to something that will work here.
        $user = $DB->get_record('user', ['username' => $username], '*');
        if (empty($user)) {
            // The user MUST exist
            return null;
        }
        $user->auth = empty($user->auth) ? 'manual' : $user->auth;

        // Check that native authentication is allowed for this user.
        if (!util::native_auth_allowed($user)) {
            return null;
        }

        // Check if the user has the mobile capability BEFORE logging them into the system.
        if (!has_capability('totara/mobile:use', \context_user::instance($user->id), $user->id)) {
            return null;
        }

        // Attempt to log the user in, if it fails return failure.
        if (!authenticate_user_login($username, $password)) {
            return null;
        }

        return self::generate_request($user->id);
    }

    /**
     * Wipe the fcmtoken for any devices using it.
     *
     * @param string $token
     * @return bool
     */
    public static function invalidate_fcmtoken($token) {
        global $DB;

        // If the invalid token is an empty string, we have nothing to do.
        if (empty($token)) {
            return true;
        }

        // There should only ever be one device associated with the token.
        $devices = $DB->get_records('totara_mobile_devices', ['fcmtoken' => $token]);
        foreach ($devices as $device) {
            $DB->set_field('totara_mobile_devices', 'fcmtoken', null, ['id' => $device->id]);

            // Trigger a token-removed event.
            fcmtoken_removed::create_from_device($device)->trigger();
        }

        return true;
    }

    /**
     * Delete one users device or all their devices.
     *
     * @param int $userid
     * @param int $deviceid
     * @return bool success
     */
    public static function delete(int $userid, int $deviceid = null) : bool {
        global $DB;

        $devices = $DB->get_records('totara_mobile_devices', ['userid' => $userid]);
        foreach ($devices as $device) {
            if ($deviceid and $deviceid != $device->id) {
                continue;
            }
            $webviews = $DB->get_records('totara_mobile_webviews', ['deviceid' => $device->id]);
            foreach ($webviews as $webview) {
                if ($webview->sid) {
                    \core\session\manager::kill_session($webview->sid);
                }
                $DB->delete_records('totara_mobile_webviews', ['id' => $webview->id]);
            }
            $DB->delete_records('totara_mobile_devices', ['id' => $device->id]);

            // Trigger a token-deleted event
            if (!empty($device->fcmtoken)) {
                // Are there no more device records with the same token?
                $token_exists = builder::table('totara_mobile_devices')
                    ->where('fcmtoken', $device->fcmtoken)
                    ->count();
                if (!$token_exists) {
                    // Trigger a token-removed event.
                    fcmtoken_removed::create_from_device($device)->trigger();
                }
            }
        }

        return true;
    }

    /**
     * Find out if given apikey represents a known device and return it if found.
     *
     * @param string $apikey
     * @return \stdClass|null device record
     */
    public static function find($apikey) {
        global $DB;

        if (strlen($apikey) !== self::API_KEY_LENGTH) {
            return null;
        }
        $keyprefix = substr($apikey, 0, 10);

        $sql = "SELECT d.*, u.auth
                  FROM {totara_mobile_devices} d
                  JOIN {user} u ON (u.id = d.userid AND u.deleted = 0 AND u.suspended = 0 AND u.auth <> 'webservice' AND u.auth <> 'nologin')
                 WHERE d.keyprefix = :keyprefix AND d.timeregistered IS NOT NULL";
        $params = array('keyprefix' => $keyprefix);

        $device = $DB->get_record_sql($sql, $params);
        if (!$device) {
            return null;
        }

        if (!is_enabled_auth($device->auth)) {
            return null;
        }

        if (!$device->keyhash) {
            // Key was not generated yet.
            return null;
        }

        // This should be considered secure because the password part is random,
        // we cannot use proper password hashing because this is checked on each request.
        if (!hash_equals(sha1($apikey), $device->keyhash)) {
            return null;
        }

        // Is key expired?
        $max_age_days = get_config('totara_mobile', 'timeout');
        if (!empty($max_age_days)) {
            $max_age_secs = $max_age_days * DAYSECS;
            $expiry_time = time() - $max_age_secs;
            if ($device->timeregistered < $expiry_time) {
                return null;
            }
        }

        return $device;
    }

    /**
     * Prepare webview access.
     *
     * @param int $deviceid
     * @param string $url
     * @return string secret
     */
    public static function create_webview(int $deviceid, string $url) {
        global $DB;

        $webview = new \stdClass();
        $webview->deviceid = $deviceid;
        $webview->url = $url;
        do {
            $webview->secret = random_string(self::SETUP_SECRET_LENGTH);
        } while ($DB->record_exists('totara_mobile_webviews', ['secret' => $webview->secret]));
        $webview->timecreated = time();
        $webview->id = $DB->insert_record('totara_mobile_webviews', $webview);

        return $webview->secret;
    }

    /**
     * Delete webview which also results is session termination.
     *
     * @param string $secret
     * @return bool success true
     */
    public static function delete_webview(string $secret) {
        global $DB;

        $webview = $DB->get_record('totara_mobile_webviews', ['secret' => $secret]);
        if ($webview) {
            if ($webview->sid) {
                \core\session\manager::kill_session($webview->sid);
            }
            $DB->delete_records('totara_mobile_webviews', ['id' => $webview->id]);
        }

        return true;
    }
}
