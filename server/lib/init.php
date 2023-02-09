<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 */

/**
 * This file prepares the Totara server environment.
 */

namespace core\internal {

    final class config {

        private static $init_log = [];

        public const INITIALISED = 'TOTARA_READY_FOR_SETUP';

        /**
         * Prepare global $CFG.
         *
         * @param string $config_file path to main config.php file
         * @param callable|null $adjuster callback for adjusting data from config file
         * @return \core_config future global $CFG
         */
        public static function initialise(string $config_file, callable $adjuster = null): \core_config {
            require_once(__DIR__ . '/classes/config.php'); // Cannot use class loader yet.

            $loader = function (string $config_file): \stdClass {

                // This is needed as config.php files used to declare $CFG as global.
                // We're getting away from that.
                $originalCFG = null;
                if (isset($GLOBALS['CFG'])) {
                    $originalCFG = clone($GLOBALS['CFG']);
                }

                // There is no global here. Very intentionally.
                // We define a new $CFG object that the config.php file will alter.
                // But we do so without making it the global. The calling script can choose an appropriate time to make it global.
                // Do not use the core_config class here yet, they can still annotate the $CFG in /config.php manually.
                $CFG = new \stdClass;
                if (!file_exists($config_file)) {
                    // Uncomment me if you need to identify the config.php that is being looked for.
                    // die('Config.php file does not exist, expected at: ' . $config_file);
                    die('config.php file does not exist');
                }
                // Must be require, as we will be here multiple times.
                require($config_file);

                $newCFG = clone($CFG);
                $CFG = null;
                unset($CFG);
                if ($originalCFG) {
                    $GLOBALS['CFG'] = $originalCFG;
                }

                return $newCFG;
            };

            $rawcfg = $loader($config_file);
            if ($adjuster) {
                // Let behat and phpunit do its magic.
                $adjuster($rawcfg);
            }

            // Create a fresh new config class and reapply all properties.
            $cfg = new \core_config($rawcfg);

            self::initialise_environment();
            self::establish_defines();
            self::set_paths($cfg);
            self::defaults($cfg);
            self::force_settings($cfg); // Must be last, so that nothing else can mess with these things.
            self::validate_environment($cfg);
            self::finalise($cfg);

            self::$init_log[] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            if (defined(self::INITIALISED)) {
                // Believe it or not there are scripts that frivolously include config.php for often undocumented reasons
                // as such it is possible we get here multiple times.
                // This should not happen, often by the looks it occurs because someone didn't think to just call
                // global $CFG;
                // This may be too early to call debugging, double check.
                if (function_exists('debugging')) {
                    debugging('Please remove additional calls to include config.php in your scripts', DEBUG_DEVELOPER);
                    print_r(self::$init_log);
                }
                return $cfg;
            }


            // This here will ensure that lib/setup.php does not execute until this function has been called and completed.
            // It is required because legacy config.php files from prior to Totara 13 include lib/setup.php at the end using
            // require_once(). This means that when it is required again we can only use require, or include, and that we
            // must manually manage its inclusion. This define is for that purpose. lib/setup.php execution is blocked
            // until this define is set.
            define(self::INITIALISED, true);

            return $cfg;
        }

        private static function set_paths(\core_config $cfg) {
            // Available in all releases.
            $cfg->dirroot = realpath(__DIR__ . '/../.');
            // TOTARA: Available since Totara 13 when we moved source code into the server directory.
            $cfg->srcroot = realpath($cfg->dirroot . '/..');
            // Convenience: path to lib dir.
            $cfg->libdir = $cfg->dirroot . '/lib';
            // New libraries directory.
            $cfg->libraries = $cfg->srcroot . '/libraries';
            // Allow overriding of tempdir but be backwards compatible
            if (!isset($cfg->tempdir)) {
                $cfg->tempdir = "$cfg->dataroot/temp";
            }
            // Allow overriding of cachedir but be backwards compatible
            if (!isset($cfg->cachedir)) {
                $cfg->cachedir = "$cfg->dataroot/cache";
            }
            // Allow overriding of localcachedir.
            if (!isset($cfg->localcachedir)) {
                $cfg->localcachedir = "$cfg->dataroot/localcache";
            }
            // Location of all languages except core English pack.
            if (!isset($cfg->langotherroot)) {
                $cfg->langotherroot = $cfg->dataroot . '/lang';
            }
            // Location of local lang pack customisations (dirs with _local suffix).
            if (!isset($cfg->langlocalroot)) {
                $cfg->langlocalroot = $cfg->dataroot . '/lang';
            }
        }

        /**
         * Make sure forbidden settings are disabled, but keep them to maintain compatibility with Moodle 3.2 and later.
         * @param \core_config $cfg
         */
        private static function force_settings(\core_config $cfg) {
            $cfg->admin = 'admin'; // Custom admin directory not supported!
            $cfg->slasharguments = '1'; // Cannot be disabled any more, admin must fix web server configuration if necessary.
            $cfg->loginhttps = '0'; // This setting was removed, use https:// in $cfg->wwwroot instead.
            $cfg->pathtounoconv = ''; // Unoconv is not secure for web servers!
            $cfg->enablemobilewebservice = '0'; // Not compatible with Totara.
            $cfg->formatstringstriptags = '1'; // Enforced for security reasons, course and activity titles must not have any html tags in them!
        }

        private static function defaults(\core_config $cfg) {

            // File permissions on created directories in the $cfg->dataroot
            if (!isset($cfg->directorypermissions)) {
                $cfg->directorypermissions = 02777;      // Must be octal (that's why it's here)
            }
            if (!isset($cfg->filepermissions)) {
                $cfg->filepermissions = ($cfg->directorypermissions & 0666); // strip execute flags
            }
            // Better also set default umask because developers often forget to include directory
            // permissions in mkdir() and chmod() after creating new files.
            if (!isset($cfg->umaskpermissions)) {
                $cfg->umaskpermissions = (($cfg->directorypermissions & 0777) ^ 0777);
            }

            // Normalise dataroot - we do not want any symbolic links, trailing / or any other weirdness there
            if (!isset($cfg->dataroot)) {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
                }
                echo('Fatal error: $cfg->dataroot is not specified in config.php! Exiting.' . "\n");
                exit(1);
            }
            $cfg->dataroot = realpath($cfg->dataroot);
            if ($cfg->dataroot === false) {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
                }
                echo('Fatal error: $cfg->dataroot is not configured properly, directory does not exist or is not accessible! Exiting.' . "\n");
                exit(1);
            } else if (!is_writable($cfg->dataroot)) {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
                }
                echo('Fatal error: $cfg->dataroot is not writable, admin has to fix directory permissions! Exiting.' . "\n");
                exit(1);
            }

            // wwwroot is mandatory
            if (!isset($cfg->wwwroot) or $cfg->wwwroot === 'http://example.com/moodle') {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
                }
                echo('Fatal error: $cfg->wwwroot is not configured! Exiting.' . "\n");
                exit(1);
            }

            // Make sure there is some database table prefix.
            if (!isset($cfg->prefix) || empty($cfg->prefix)) {
                echo('Fatal error: $cfg->prefix is not configured! Exiting.' . "\n");
                exit(1);
            }

            // Exact version of currently used yui2 and 3 library.
            $cfg->yui2version = '2.9.0';
            $cfg->yui3version = '3.17.2';

            // Patching the upstream YUI release.
            // For important information on patching YUI modules, please see http://docs.moodle.org/dev/YUI/Patching.
            // If we need to patch a YUI modules between official YUI releases, the yuipatchlevel will need to be manually
            // incremented here. The module will also need to be listed in the yuipatchedmodules.
            // When upgrading to a subsequent version of YUI, these should be reset back to 0 and an empty array.
            $cfg->yuipatchlevel = 2;
            $cfg->yuipatchedmodules = [
                'dom-core',
            ];

            // Store settings from config.php in array in $cfg - we can use it later to detect problems and overrides.
            $cfg->config_php_settings = (array)$cfg;
            // Forced plugin settings override values from config_plugins table.
            unset($cfg->config_php_settings['forced_plugin_settings']);
            unset($cfg->config_php_settings['config_php_settings']);
            if (!isset($cfg->forced_plugin_settings)) {
                $cfg->forced_plugin_settings = array();
            }

            if (isset($cfg->debug)) {
                $cfg->debug = (int)$cfg->debug;
            } else {
                if ((defined('PHPUNIT_TEST') && PHPUNIT_TEST) || (defined('PHPUNIT_UTIL') && PHPUNIT_UTIL) || (defined('BEHAT_UTIL') && BEHAT_UTIL)
                    || (defined('BEHAT_TEST') && BEHAT_TEST) || (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING)) {
                    $cfg->debug = (E_ALL | E_STRICT);
                } else {
                    $cfg->debug = 0;
                }
            }
            $cfg->debugdeveloper = (($cfg->debug & (E_ALL | E_STRICT)) === (E_ALL | E_STRICT)); // DEBUG_DEVELOPER is not available yet.
        }

        private static function establish_defines() {
            // Scripts may request no debug and error messages in output
            // please note it must be defined before including the config.php script
            // and in some cases you also need to set custom default exception handler
            if (!defined('NO_DEBUG_DISPLAY')) {
                if (defined('AJAX_SCRIPT') and AJAX_SCRIPT) {
                    // Moodle AJAX scripts are expected to return json data, any PHP notices or errors break it badly,
                    // developers simply must learn to watch error log.
                    define('NO_DEBUG_DISPLAY', true);
                } else {
                    define('NO_DEBUG_DISPLAY', false);
                }
            }

            // Some scripts such as upgrade may want to prevent output buffering
            if (!defined('NO_OUTPUT_BUFFERING')) {
                define('NO_OUTPUT_BUFFERING', false);
            }

            // PHPUnit tests need custom init
            if (!defined('PHPUNIT_TEST')) {
                define('PHPUNIT_TEST', false);
            }

            if (PHPUNIT_TEST || (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING)) {
                if (!defined('TOTARA_DISTRIBUTION_TEST')) {
                    define('TOTARA_DISTRIBUTION_TEST', false);
                }
            }

            // Performance tests needs to always display performance info, even in redirections.
            if (defined('MDL_PERF_TEST')) {
                if (MDL_PERF_TEST) {
                    // We force the ones we need.
                    if (!defined('MDL_PERF')) {
                        define('MDL_PERF', true);
                    }
                    if (!defined('MDL_PERFDB')) {
                        define('MDL_PERFDB', true);
                    }
                    if (!defined('MDL_PERFTOFOOT')) {
                        define('MDL_PERFTOFOOT', true);
                    }
                }
            } else {
                define('MDL_PERF_TEST', false);
            }

            // When set to true MUC (Moodle caching) will be disabled as much as possible.
            // A special cache factory will be used to handle this situation and will use special "disabled" equivalents objects.
            // This ensure we don't attempt to read or create the config file, don't use stores, don't provide persistence or
            // storage of any kind.
            if (!defined('CACHE_DISABLE_ALL')) {
                define('CACHE_DISABLE_ALL', false);
            }

            // When set to true MUC (Moodle caching) will not use any of the defined or default stores.
            // The Cache API will continue to function however this will force the use of the cachestore_dummy so all requests
            // will be interacting with a static property and will never go to the proper cache stores.
            // Useful if you need to avoid the stores for one reason or another.
            if (!defined('CACHE_DISABLE_STORES')) {
                define('CACHE_DISABLE_STORES', false);
            }

            // Detect CLI scripts - CLI scripts are executed from command line, do not have session and we do not want HTML in output
            // In your new CLI scripts just add "define('CLI_SCRIPT', true);" before requiring config.php.
            // Please note that one script can not be accessed from both CLI and web interface.
            if (!defined('CLI_SCRIPT')) {
                define('CLI_SCRIPT', false);
            }

            // All web service requests have WS_SERVER == true.
            if (!defined('WS_SERVER')) {
                define('WS_SERVER', false);
            }

            if (!defined('EXTERNAL_API')) {
                define('EXTERNAL_API', false);
            }

            // Detect ajax scripts - they are similar to CLI because we can not redirect, output html, etc.
            if (!defined('AJAX_SCRIPT')) {
                define('AJAX_SCRIPT', false);
            }

            if (!defined('MOODLE_INTERNAL')) { // Necessary because cli installer has to define it earlier.
                /** Used by library scripts to check they are being called by Moodle. */
                define('MOODLE_INTERNAL', true);
            }
        }

        public static function initialise_environment() {
            // sometimes default PHP settings are borked on shared hosting servers, I wonder why they have to do that??
            ini_set('precision', 14); // needed for upgrades and gradebook
            ini_set('serialize_precision', 17); // Make float serialization consistent on all systems.
            ini_set('default_charset', 'UTF-8'); // Totara: always use UTF-8 as default encoding.
            ini_set('input_encoding', '');
            ini_set('output_encoding', '');
            ini_set('mbstring.language', 'neutral');

            // Disable phar wrapper as this presents a security risk if user input can make it to just
            // about any PHP file function, including file_exists().
            // If the wrapper is needed, it can be enabled where required with stream_wrapper_register('phar')
            // just before it is required.
            // This line has been included after defines of constants such as PHPUNIT_TEST in case we
            // ever need to make it conditional on those. Otherwise it needs to be as early as possible.
            @stream_wrapper_unregister('phar');

            // Servers should define a default timezone in php.ini, but if they don't then make sure no errors are shown.
            date_default_timezone_set(@date_default_timezone_get());
        }

        /**
         * CFG initialiser for running behat site controlled by selenium.
         * @return \core_config
         */
        public static function initialise_behat_site(): \core_config {
            if (!isset($_SERVER['REMOTE_ADDR']) || empty($_COOKIE['BEHAT'])) {
                die('Invalid behat site access');
            }
            $adjuster = function (\stdClass $cfg) {
                \core\internal\config::adjust_for_behat($cfg);
            };
            return \core\internal\config::initialise(__DIR__ . '/../../config.php', $adjuster);
        }

        /**
         * CFG initialiser for behat manager running the steps.
         * @return \core_config
         */
        public static function initialise_behat_test(): \core_config {
            if (!defined('BEHAT_TEST') || !BEHAT_TEST) {
                die('Invalid behat test access');
            }
            $adjuster = function (\stdClass $cfg) {
                \core\internal\config::adjust_for_behat($cfg);
            };
            return \core\internal\config::initialise(__DIR__ . '/../../config.php', $adjuster);
        }

        /**
         * CFG initialiser for behat CLI utility scripts.
         * @return \core_config
         */
        public static function initialise_behat_util(): \core_config {
            if (!defined('BEHAT_UTIL') || !BEHAT_UTIL) {
                die('Invalid behat util access');
            }
            $adjuster = function (\stdClass $cfg) {
                \core\internal\config::adjust_for_behat($cfg);
            };
            return \core\internal\config::initialise(__DIR__ . '/../../config.php', $adjuster);
        }

        protected static function adjust_for_behat(\stdClass $cfg) {

            if (defined('BEHAT_SITE_RUNNING')) {
                // We already switched to behat test site previously.

            } else if (!empty($cfg->behat_wwwroot) or !empty($cfg->behat_dataroot) or !empty($cfg->behat_prefix)) {
                // Do not allow overrides from config.php for these settings.
                unset($cfg->debug);
                unset($cfg->debugdeveloper);
                unset($cfg->debugdisplay);
                unset($cfg->themerev);
                unset($cfg->jsrev);

                // The behat is configured on this server, we need to find out if this is the behat test
                // site based on the URL used for access.
                require_once(__DIR__ . '/../lib/behat/lib.php');
                // Update config variables for parallel behat runs.
                behat_update_vars_for_process($cfg);

                // If behat is being installed for parallel run, then we modify params for parallel run only.
                if (behat_is_test_site($cfg) && !(defined('BEHAT_PARALLEL_UTIL') && empty($cfg->behatrunprocess))) {
                    clearstatcache();

                    // Checking the integrity of the provided $cfg->behat_* vars and the
                    // selected wwwroot to prevent conflicts with production and phpunit environments.
                    behat_check_config_vars($cfg);

                    // Check that the directory does not contains other things.
                    if (!file_exists("$cfg->behat_dataroot/behattestdir.txt")) {
                        if ($dh = opendir($cfg->behat_dataroot)) {
                            while (($file = readdir($dh)) !== false) {
                                if ($file === 'behat' or $file === '.' or $file === '..' or $file === '.DS_Store' or is_numeric($file)) {
                                    continue;
                                }
                                behat_error(BEHAT_EXITCODE_CONFIG, "$cfg->behat_dataroot directory is not empty, ensure this is the " .
                                    "directory where you want to install behat test dataroot");
                            }
                            closedir($dh);
                            unset($dh);
                            unset($file);
                        }

                        if (defined('BEHAT_UTIL')) {

                            if (!isset($cfg->directorypermissions)) {
                                $cfg->directorypermissions = (isset($cfg->behat_directorypermissions)) ? $cfg->behat_directorypermissions : 02777;
                            }
                            if (!isset($cfg->filepermissions)) {
                                $cfg->filepermissions = (isset($cfg->behat_filepermissions)) ? $cfg->behat_filepermissions : ($cfg->directorypermissions & 0666); // strip execute flags;
                            }
                            // Now we create dataroot directory structure for behat tests.
                            testing_initdataroot($cfg->behat_dataroot, 'behat', $cfg->directorypermissions, $cfg->filepermissions);
                        } else {
                            behat_error(BEHAT_EXITCODE_INSTALL);
                        }
                    }

                    if (!defined('BEHAT_UTIL') and !defined('BEHAT_TEST')) {
                        // Somebody tries to access test site directly, tell them if not enabled.
                        $behatdir = preg_replace("#[/|\\\]" . BEHAT_PARALLEL_SITE_NAME . "\d{0,}$#", '', $cfg->behat_dataroot);
                        if (!file_exists($behatdir . '/test_environment_enabled.txt')) {
                            behat_error(BEHAT_EXITCODE_CONFIG, 'Behat is configured but not enabled on this test site.');
                        }
                    }

                    // Constant used to inform that the behat test site is being used,
                    // this includes all the processes executed by the behat CLI command like
                    // the site reset, the steps executed by the browser drivers when simulating
                    // a user session and a real session when browsing manually to $cfg->behat_wwwroot
                    // like the browser driver does automatically.
                    // Different from BEHAT_TEST as only this last one can perform CLI
                    // actions like reset the site or use data generators.
                    define('BEHAT_SITE_RUNNING', true);

                    // Clean extra config.php settings.
                    behat_clean_init_config($cfg);

                    // Now we can begin switching $cfg->X for $cfg->behat_X.
                    $cfg->wwwroot = $cfg->behat_wwwroot;
                    $cfg->prefix = $cfg->behat_prefix;
                    $cfg->dataroot = $cfg->behat_dataroot;
                    $cfg->dboptions = isset($cfg->behat_dboptions) ? $cfg->behat_dboptions : $cfg->dboptions;
                }
            }

            // Totara: redirect behat error logs to a special file, but do not log the errors from setup utils there.
            if (defined('BEHAT_SITE_RUNNING') or defined('BEHAT_TEST')) {
                error_reporting(E_ALL | E_STRICT);
                ini_set('display_errors', '1');
                ini_set('log_errors', '1');
                if (!defined('BEHAT_UTIL')) {
                    ini_set('error_log', dirname($cfg->dataroot) . '/' . basename($cfg->dataroot) . '_error.log');
                }
            }
        }

        private static function finalise(\core_config $cfg) {
            umask($cfg->umaskpermissions);

            // core_component can be used in any scripts, it does not need anything else.
            require_once($cfg->libdir . '/classes/component.php');
        }

        private static function validate_environment(\core_config $cfg) {
            // Sometimes people use different PHP binary for web and CLI, make 100% sure they have the supported PHP version.
            if (version_compare(PHP_VERSION, '7.4.3') < 0) {
                $phpversion = PHP_VERSION;
                // Do NOT localise - lang strings would not work here and we CAN NOT move it to later place.
                echo "Totara 17 or later requires at least PHP 7.4.3 (currently using version $phpversion).\n";
                echo "Some servers may have multiple PHP versions installed, are you using the correct executable?\n";
                exit(1);
            }

            // Make sure iconv is available.
            if (!function_exists('iconv')) {
                echo("Totara requires the iconv PHP extension. Please install or enable the iconv extension.\n");
                exit(1);
            }

            // Make sure xml extension is available - we need it to load full environment tests.
            if (!extension_loaded('xml')) {
                echo("Totara requires the xml PHP extension. Please install or enable the xml extension.\n");
                exit(1);
            }

            // Make sure php5-json is available.
            if (!function_exists('json_encode') or !function_exists('json_decode')) {
                echo("Totara requires the json PHP extension. Please install or enable the json extension.\n");
                exit(1);
            }

            if (defined('WEB_CRON_EMULATED_CLI')) {
                if (!isset($_SERVER['REMOTE_ADDR'])) {
                    echo('Web cron can not be executed as CLI script any more, please use admin/cli/cron.php instead' . "\n");
                    exit(1);
                }
            } else if (isset($_SERVER['REMOTE_ADDR'])) {
                if (CLI_SCRIPT) {
                    echo('Command line scripts can not be executed from the web interface');
                    exit(1);
                }
            } else {
                if (!CLI_SCRIPT) {
                    echo('Command line scripts must define CLI_SCRIPT before requiring config.php' . "\n");
                    exit(1);
                }
            }

            // Detect CLI maintenance mode - this is useful when you need to mess with database, such as during upgrades
            if (file_exists("$cfg->dataroot/climaintenance.html")) {
                if (!CLI_SCRIPT) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Totara under maintenance');
                    header('Status: 503 Moodle under maintenance');
                    header('Retry-After: 300');
                    header('Content-type: text/html; charset=utf-8');
                    header('X-UA-Compatible: IE=edge');
                    /// Headers to make it not cacheable and json
                    header('Cache-Control: no-store, no-cache, must-revalidate');
                    header('Cache-Control: post-check=0, pre-check=0', false);
                    header('Pragma: no-cache');
                    header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                    header('Accept-Ranges: none');
                    readfile("$cfg->dataroot/climaintenance.html");
                    die;
                }
                if (!defined('CLI_MAINTENANCE')) {
                    define('CLI_MAINTENANCE', true);
                }
            }
            if (!defined('CLI_MAINTENANCE')) {
                define('CLI_MAINTENANCE', false);
            }
        }
    }
}