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
 * @package totara_mobile
 */

namespace totara_mobile\local;

/**
 * Class util
 */
final class util {

    /**
     * API Version
     * Update whenever there is a significant change to the mobile plugins endpoints's expectations or responses.
     */
    const API_VERSION = '2020100100';

    /**
     * Minimum supported App version
     * Update whenever an old version of the app becomes unsupported.
     * Important: Mirror any updates here in totara_mobile/js/device_emulator.js
     */
    const MIN_APP_VERSION = '1.0.0';

    /**
     * Totara App user agent string
     */
    const APP_USER_AGENT_MATCH = 'TotaraMobileApp';

    /**
     * Is the current access done from a Mobile App WebView?
     *
     * @return bool
     */
    public static function is_mobile_webview() {
        // Use a simple string match, the stakes are low for mimicking the mobile app in a webview.
        if (strpos(\core_useragent::get_user_agent_string(), self::APP_USER_AGENT_MATCH) === false
            and empty($_SERVER['HTTP_X_TOTARA_DEVICE_EMULATION'])
        ) {
            return false;
        }
        return true;
    }

    /**
     * Is the mobile plugin allowed to use native authentication?
     *
     * Checks system settings generally, and also considers user auth setting if available.
     * @param \stdClass|null $user
     * @return bool
     */
    public static function native_auth_allowed(?\stdClass $user = null): bool {
        $allowed = [];
        // We currently allow manual and ldap
        if (is_enabled_auth('manual')) {
            $allowed['manual'] = 'manual';
        }
        if (is_enabled_auth('ldap')) {
            // LDAP is allowed, but not the NTLM SSO flavour, which requires a browser.
            $ntlm_sso = get_config('auth_ldap', 'ntlmsso_enabled');
            if(!$ntlm_sso) {
                $allowed['ldap'] = 'ldap';
            }
        }
        // Now compare with user if provided.
        if ($user) {
            // User's authentication method is not allowed.
            if (!in_array($user->auth, $allowed)) {
                return false;
            }
        }
        return (bool) count($allowed);
    }

    /**
     * If the app banner should be shown, return a url with setup secret to use for the button.
     *
     * @return bool|\moodle_url
     */
    public static function app_banner_url() {
        global $SESSION;

        // Is this a mobile device?
        $device_type = \core_useragent::get_device_type();
        if (!in_array($device_type, [\core_useragent::DEVICETYPE_MOBILE, \core_useragent::DEVICETYPE_TABLET])) {
            // Is this a behat-emulated mobile device?
            if (!defined('BEHAT_SITE_RUNNING')) {
                return false;
            }
        }
        // Is mobile enabled?
        if (!get_config('totara_mobile', 'enable')) {
            return false;
        }
        // Require login.
        if (!isloggedin()) {
            return false;
        }
        // Require mobile use capability.
        if (!has_capability('totara/mobile:use', \context_system::instance())) {
            return false;
        }
        // Has app banner been seen in this session?
        if (!empty($SESSION->totara_mobile_app_banner_shown)) {
            return false;
        }

        // Only show the banner once per session.
        $SESSION->totara_mobile_app_banner_shown = true;

        // Build the url and return.
        $setupsecret = device::request();
        $url = device::get_universal_link_register_url($setupsecret);
        return $url;
    }

    /**
     * To be called from login/index.php page only.
     */
    public static function login_page_hook_start() {
        global $SESSION, $CFG, $PAGE;

        if (!self::is_mobile_webview()) {
            return;
        }

        if (!empty($_SERVER['HTTP_X_TOTARA_MOBILE_DEVICE_REGISTRATION'])) {
            $SESSION->totara_mobile_device_registration = true;
        }

        $PAGE->set_url("$CFG->wwwroot/login/index.php");
        $PAGE->set_context(\context_system::instance());

        $SESSION->forcepagelayout = 'webview';
        $SESSION->wantsurl = $CFG->wwwroot . '/totara/mobile/device_request.php';
        if (!empty($_SERVER['HTTP_X_TOTARA_DEVICE_EMULATION'])) {
            $SESSION->device_emulation = 1;
        }

        if (!get_config('totara_mobile', 'enable')) {
            util::webview_error(get_string('errormobileunavailable', 'totara_mobile'));
        }

        if (empty($SESSION->totara_mobile_device_registration)) {
            util::webview_error();
        }

        if (is_major_upgrade_required()) {
            util::webview_error(get_string('errormobileunavailable', 'totara_mobile'));
        }

        if (isloggedin() and !isguestuser()) {
            // We should not get here, but if by any chance we do we can finish the device registration now.
            redirect($SESSION->wantsurl);
        }

        // Temporarily override settings we do not want in mobile device registration,
        // but make sure security is not weakened because users may inject request headers.
        $CFG->persistentloginenable = 0;
        $CFG->rememberusername = 0;
        $CFG->guestloginbutton = 0;
        $CFG->preventmultiplelogins = 0;
        $CFG->nolastloggedin = 1;
    }

    /**
     * To be called from login/index.php page only.
     */
    public static function login_page_hook_loggedin() {
        global $SESSION, $CFG;

        if (empty($SESSION->totara_mobile_device_registration)) {
            return;
        }

        if (!isloggedin() or isguestuser()) {
            return;
        }

        // Tidy up session.
        unset($SESSION->login_username);
        unset($SESSION->login_remember);
        unset($SESSION->loginerrormsg);
        $SESSION->forcepagelayout = 'webview';
        $SESSION->wantsurl = $CFG->wwwroot . '/totara/mobile/device_request.php';

        // Display the secret key and force logout.
        redirect($SESSION->wantsurl);
    }

    /**
     * Send response and stop execution.
     *
     * @param array $response
     * @param int $statuscode
     * @return void - does not return
     */
    public static function send_response(array $response, $statuscode) {
        header('Content-type: application/json; charset=utf-8', true, $statuscode);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Accept-Ranges: none');
        echo json_encode($response, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        die;
    }

    /**
     * Send general error message without any logging.
     *
     * @param string $message
     * @param int $statuscode
     */
    public static function send_error(string $message, $statuscode) {
        self::send_response(['errors' => [['message' => $message]]], $statuscode);
    }

    /**
     * Default error handler for Web API ajax.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array|null $errcontext (not used in PHP 8.0)
     * @return bool false means use default error handler
     */
    public static function error_handler($errno, $errstr, $errfile, $errline, $errcontext = null) {
        if ($errno == 4096) {
            // Fatal catchable error.
            throw new \coding_exception('PHP catchable fatal error', $errstr);
        }
        return false;
    }

    /**
     * Default exception handler for Web API ajax.
     *
     * @param \Throwable $ex
     * @return void - does not return. Terminates execution!
     */
    public static function exception_handler($ex) {
        global $CFG, $PAGE;

        // Detect active db transactions, rollback and log as error.

        abort_all_db_transactions();

        $PAGE->set_context(null);

        self::log_exception($ex);

        // TODO: decide how to mark exceptions as "client aware"

        $response = [
            'errors' => \GraphQL\Error\FormattedError::createFromException($ex, (bool)$CFG->debugdeveloper),
        ];

        self::send_response($response, 500);
    }

    /**
     * Log exceptions during ajax execution.
     * @param \Throwable $ex
     */
    public static function log_exception($ex) {
        $message = $ex->getMessage();
        $info = get_exception_info($ex);
        error_log("MOBILE API error: $message Debug: " . $info->debuginfo . "\n" . format_backtrace($info->backtrace, true));
    }

    /**
     * Error handler for graphql-php error logging.
     *
     * @param array $errors
     * @param callable $formatter
     * @return array
     */
    public static function graphql_error_handler(array $errors, callable $formatter) {
        foreach ($errors as $error) {
            /** @var \Throwable $error */
            $prev = $error->getPrevious();
            if (!$prev) {
                continue;
            }
            self::log_exception($prev);
        }
        return array_map($formatter, $errors);
    }

    /**
     * Tell browser file does not exist.
     */
    public static function send_file_not_found(string $error = '') {
        // Error messages may be localised, this means we need to send charset somehow.
        header('Content-type: text/plain; charset=utf-8', true, 404);

        if (empty($error)) {
            if (function_exists('get_string')) {
                $error = get_string('filenotfound', 'error');
            } else {
                $error = 'Sorry, the requested file could not be found';
            }
        }

        echo $error;
        die;
    }

    /**
     * Print error and stop in a mobile app webview.
     *
     * @param string|null $message custom more specific message
     * @return void does not return
     */
    public static function webview_error(string $message = null) {
        global $OUTPUT;

        if (!$message) {
            $message = get_string('errorgeneral', 'totara_mobile');
        }

        // TODO: Print out something that can be read by the client app code, it is not really intended for users.
        echo $OUTPUT->header();
        echo $OUTPUT->notification($message, 'error');
        echo $OUTPUT->footer();
        die;
    }

    /**
     * Returns the url for the mobile logo if configured on the site or null if not
     *
     * @return string|null
     */
    private static function get_mobile_logo_url(): ?string {
        $context = \context_system::instance();

        $logo = get_config('totara_mobile', 'logo');
        if (empty($logo)) {
            return null;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'totara_mobile', 'logo', 0, "timemodified DESC", false);

        if (!empty($files)) {
            // There should only ever be one file, but just in case, we get the most recently modified one.
            $file = array_pop($files);
            $url = \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename(),
                false
            );
            return $url->out();
        }

        return null;
    }

    /**
     * Get site configuration info for mobile app to use.
     *
     * @param string $app_version
     * @return string
     */
    public static function get_site_info(string $app_version): array {
        global $CFG;

        if (version_compare(self::MIN_APP_VERSION, $app_version, '>')) {
            return [
                'upgrade' => self::MIN_APP_VERSION,
                'app_version' => $app_version,
            ];
        }

        $textcolour = get_config('totara_mobile', 'textcolour');
        $primarycolour = get_config('totara_mobile', 'primarycolour');

        return [
            'auth' => get_config('totara_mobile', 'authtype'),
            'siteMaintenance' => $CFG->maintenance_enabled,
            'theme' => [
                'urlLogo' => self::get_mobile_logo_url(),
                'colorPrimary' => !empty($primarycolour) ? $primarycolour : "#69BD45",
                'colorText' => !empty($textcolour) ? $textcolour : "#FFFFFF",
            ],
            'version' => self::get_api_version(),
            'app_version' => $app_version,
        ];
    }

    /**
     * Get mobile API version string for mobile app.
     *
     * @return string
     */
    public static function get_api_version(): string {
        return self::API_VERSION;
    }
}
