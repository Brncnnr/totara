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
 * setup.php - Sets up sessions, connects to databases and so on
 *
 * Normally this is only called by the main config.php file
 * Normally this file does not need to be edited.
 *
 * @package    core
 * @subpackage lib
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (class_exists('\core\internal\config') && !defined(\core\internal\config::INITIALISED)) {
    // This define was added in Totara 13.0 when the code was reorganised. This blocks existing config.php
    // files from loading lib/setup.php when they are done.
    // We want to control that now, so that we can encapsulate config.php inclusion completely.
    if (!defined('ABORT_AFTER_CONFIG_CANCEL')) {
        return;
    }
}

// Because old config.php files may be including this file using require_once when we include it in lib/init.php
// we need to use require() or include() and manually ensure that this file does not get included twice.
if (defined('TOTARA_SETUP_INCLUDED')) {
    // This is to detect the ABORT_AFTER_CONFIG_CANCEL hack and let us through here time and time again.
    // The calling script has to manually manage this.... good luck!
    if (!defined('ABORT_AFTER_CONFIG_CANCEL')) {
        return;
    }
} else {
    define('TOTARA_SETUP_INCLUDED', true);
}

if (!isset($CFG)) {
    exit(8332);
}
// special support for highly optimised scripts that do not need libraries and DB connection
if (defined('ABORT_AFTER_CONFIG')) {
    if (!defined('ABORT_AFTER_CONFIG_CANCEL')) {
        // hide debugging if not enabled in config.php - we do not want to disclose sensitive info
        error_reporting($CFG->debug);
        if (NO_DEBUG_DISPLAY) {
            // Some parts of Moodle cannot display errors and debug at all.
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        } else if (empty($CFG->debugdisplay)) {
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        } else {
            ini_set('display_errors', '1');
        }
        require_once("$CFG->dirroot/lib/configonlylib.php");
        return;
    }
}

// Early profiling start, based exclusively on config.php $CFG settings
if (!empty($CFG->earlyprofilingenabled)) {
    profiling_start();
}

/**
 * Database connection. Used for all access to the database.
 * @global moodle_database $DB
 * @name $DB
 */
global $DB;

/**
 * Moodle's wrapper round PHP's $_SESSION.
 *
 * @global object $SESSION
 * @name $SESSION
 */
global $SESSION;

/**
 * Holds the user table record for the current user. Will be the 'guest'
 * user record for people who are not logged in.
 *
 * $USER is stored in the session.
 *
 * Items found in the user record:
 *  - $USER->email - The user's email address.
 *  - $USER->id - The unique integer identified of this user in the 'user' table.
 *  - $USER->email - The user's email address.
 *  - $USER->firstname - The user's first name.
 *  - $USER->lastname - The user's last name.
 *  - $USER->username - The user's login username.
 *  - $USER->secret - The user's ?.
 *  - $USER->lang - The user's language choice.
 *
 * @global object $USER
 * @name $USER
 */
global $USER;

/**
 * Frontpage course record
 */
global $SITE;

/**
 * A central store of information about the current page we are
 * generating in response to the user's request.
 *
 * @global moodle_page $PAGE
 * @name $PAGE
 */
global $PAGE;

/**
 * The current course. An alias for $PAGE->course.
 * @global object $COURSE
 * @name $COURSE
 */
global $COURSE;

/**
 * $OUTPUT is an instance of core_renderer or one of its subclasses. Use
 * it to generate HTML for output.
 *
 * $OUTPUT is initialised the first time it is used. See {@link bootstrap_renderer}
 * for the magic that does that. After $OUTPUT has been initialised, any attempt
 * to change something that affects the current theme ($PAGE->course, logged in use,
 * httpsrequried ... will result in an exception.)
 *
 * Please note the $OUTPUT is replacing the old global $THEME object.
 *
 * @global object $OUTPUT
 * @name $OUTPUT
 */
global $OUTPUT;

/**
 * Full script path including all params, slash arguments, scheme and host.
 *
 * Note: Do NOT use for getting of current page URL or detection of https,
 * instead use $PAGE->url or is_https().
 *
 * @global string $FULLME
 * @name $FULLME
 */
global $FULLME;

/**
 * Script path including query string and slash arguments without host.
 * @global string $ME
 * @name $ME
 */
global $ME;

/**
 * $FULLME without slasharguments and query string.
 * @global string $FULLSCRIPT
 * @name $FULLSCRIPT
 */
global $FULLSCRIPT;

/**
 * Relative moodle script path '/course/view.php'
 * @global string $SCRIPT
 * @name $SCRIPT
 */
global $SCRIPT;

// Set httpswwwroot to $CFG->wwwroot for backwards compatibility
// The loginhttps option is deprecated, so httpswwwroot is no longer necessary. See MDL-42834.
$CFG->httpswwwroot = $CFG->wwwroot;

require_once($CFG->libdir .'/setuplib.php');        // Functions that MUST be loaded first

if (NO_OUTPUT_BUFFERING) {
    // we have to call this always before starting session because it discards headers!
    disable_output_buffering();
}

// Increase memory limits if possible
raise_memory_limit(MEMORY_STANDARD);

// Time to start counting
init_performance_info();

// Put $OUTPUT in place, so errors can be displayed.
$OUTPUT = new bootstrap_renderer();

// set handler for uncaught exceptions - equivalent to print_error() call
if (!PHPUNIT_TEST or PHPUNIT_UTIL) {
    set_exception_handler('default_exception_handler');
    set_error_handler('default_error_handler', E_ALL | E_STRICT);
}

// Totara: there is no need to creates behat hacks to deal with errors, we use normal error logs.

// If there are any errors in the standard libraries we want to know!
error_reporting(E_ALL | E_STRICT);

// Just say no to link prefetching (Moz prefetching, Google Web Accelerator, others)
// http://www.google.com/webmasters/faq.html#prefetchblock
if (!empty($_SERVER['HTTP_X_moz']) && $_SERVER['HTTP_X_moz'] === 'prefetch'){
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Prefetch Forbidden');
    echo('Prefetch request forbidden.');
    exit(1);
}

//point pear include path to moodles lib/pear so that includes and requires will search there for files before anywhere else
//the problem is that we need specific version of quickforms and hacked excel files :-(
ini_set('include_path', $CFG->libdir.'/pear' . PATH_SEPARATOR . ini_get('include_path'));

// First add composer classloader.
require_once(__DIR__ . '/../../libraries/required/autoload.php');
// Register our classloader, in theory somebody might want to replace it to load other hacked core classes.
if (defined('COMPONENT_CLASSLOADER')) {
    spl_autoload_register(COMPONENT_CLASSLOADER);
} else {
    spl_autoload_register('core_component::classloader');
}

// Remember the default PHP timezone, we will need it later.
core_date::store_default_php_timezone();

// Load up standard libraries
require_once($CFG->libdir .'/filterlib.php');       // Functions for filtering test as it is output
require_once($CFG->libdir .'/ajax/ajaxlib.php');    // Functions for managing our use of JavaScript and YUI
require_once($CFG->libdir .'/weblib.php');          // Functions relating to HTTP and content
require_once($CFG->libdir .'/outputlib.php');       // Functions for generating output
require_once($CFG->libdir .'/navigationlib.php');   // Class for generating Navigation structure
require_once($CFG->libdir .'/dmllib.php');          // Database access
require_once($CFG->libdir .'/datalib.php');         // Legacy lib with a big-mix of functions.
require_once($CFG->libdir .'/accesslib.php');       // Access control functions
require_once($CFG->libdir .'/deprecatedlib.php');   // Deprecated functions included for backward compatibility
require_once($CFG->libdir .'/moodlelib.php');       // Other general-purpose functions
require_once($CFG->libdir .'/enrollib.php');        // Enrolment related functions
require_once($CFG->libdir .'/pagelib.php');         // Library that defines the moodle_page class, used for $PAGE
require_once($CFG->libdir .'/blocklib.php');        // Library for controlling blocks
require_once($CFG->libdir .'/eventslib.php');       // Events functions
require_once($CFG->libdir .'/grouplib.php');        // Groups functions
require_once($CFG->libdir .'/sessionlib.php');      // All session and cookie related stuff
require_once($CFG->libdir .'/editorlib.php');       // All text editor related functions and classes
require_once($CFG->libdir .'/messagelib.php');      // Messagelib functions
require_once($CFG->libdir .'/modinfolib.php');      // Cached information on course-module instances
require_once($CFG->dirroot.'/cache/lib.php');       // Cache API

/* Requires for Totara */
require_once($CFG->libdir . '/coursecatlib.php');   // coursecat class is used all over the place, so include it always.
require_once($CFG->dirroot .'/totara/core/totara.php');// Standard functions used by Totara
totara_setup();

// make sure PHP is not severly misconfigured
setup_validate_php_configuration();

// Connect to the database
setup_DB();

$useconfigcache = true;
if (PHPUNIT_TEST and !PHPUNIT_UTIL) {
    $useconfigcache = false;
    // make sure tests do not run in parallel
    \core_phpunit\internal_util::acquire_lock();
    $dbhash = null;
    try {
        if ($dbhash = $DB->get_field('config', 'value', array('name'=>'phpunittest'))) {
            // reset DB tables
            \core_phpunit\internal_util::reset_database();
        }
    } catch (Throwable $e) {
        if ($dbhash) {
            // we ned to reinit if reset fails
            $DB->set_field('config', 'value', 'na', array('name'=>'phpunittest'));
        }
    }
    unset($dbhash);
} else if (defined('BEHAT_TEST') && BEHAT_TEST) {
    $useconfigcache = false;
    $DB->get_manager()->snapshot_rollback();
} else if (defined('BEHAT_UTIL') && BEHAT_UTIL) {
    $useconfigcache = false;
}


// Load up any configuration from the config table or MUC cache.
initialise_cfg($useconfigcache);
unset($useconfigcache);

if (!empty($CFG->tenantsenabled)) {
    // Totara: force-disable incompatible features and subsystems
    $CFG->enablereportcaching = '0';
    $CFG->config_php_settings['enablereportcaching'] = $CFG->enablereportcaching;
} else if (!$DB->is_create_table_from_select_supported()) {
    // Report caching cannot work if creation of tables from select is not supported,
    // such as with MySQL GTID replication.
    $CFG->enablereportcaching = '0';
    $CFG->config_php_settings['enablereportcaching'] = $CFG->enablereportcaching;
}
$CFG->mnet_localhost_id = '1';
$CFG->config_php_settings['mnet_localhost_id'] = $CFG->mnet_localhost_id;

if (isset($CFG->debug)) {
    $CFG->debug = (int)$CFG->debug;
    error_reporting($CFG->debug);
}  else {
    $CFG->debug = 0;
}
$CFG->debugdeveloper = (($CFG->debug & DEBUG_DEVELOPER) === DEBUG_DEVELOPER);

// Find out if PHP configured to display warnings,
// this is a security problem because some moodle scripts may
// disclose sensitive information.
if (ini_get_bool('display_errors')) {
    define('WARN_DISPLAY_ERRORS_ENABLED', true);
}
// If we want to display Moodle errors, then try and set PHP errors to match.
if (!isset($CFG->debugdisplay)) {
    // Keep it "as is" during installation.
} else if (NO_DEBUG_DISPLAY) {
    // Some parts of Moodle cannot display errors and debug at all.
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else if (empty($CFG->debugdisplay)) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    // This is very problematic in XHTML strict mode!
    ini_set('display_errors', '1');
}

// Register our shutdown manager, do NOT use register_shutdown_function().
core_shutdown_manager::initialize();

// Verify upgrade is not running unless we are in a script that needs to execute in any case
if (!defined('NO_UPGRADE_CHECK') and isset($CFG->upgraderunning)) {
    if ($CFG->upgraderunning < time()) {
        unset_config('upgraderunning');
    } else {
        print_error('upgraderunning');
    }
}

// enable circular reference collector in PHP 5.3,
// it helps a lot when using large complex OOP structures such as in amos or gradebook
if (function_exists('gc_enable')) {
    gc_enable();
}

// Totara: do not localise these messages, they will change often and they are intended for admins only!
if (!empty($CFG->version)) {
    if (empty($CFG->totara_release)) {
        throw new Exception('You cannot upgrade directly to this Totara release from Moodle, please upgrade to latest Totara 13 first.');
    } else {
        if ($CFG->version < 2017111309) {
            // We cannot upgrade from Totara older than v9.0.
            throw new Exception('You cannot upgrade to this Totara version from a Totara version prior to 13.0, please upgrade to latest Totara 13 first.');
        }
    }
}

// Calculate and set $CFG->ostype to be used everywhere. Possible values are:
// - WINDOWS: for any Windows flavour.
// - UNIX: for the rest
// Also, $CFG->os can continue being used if more specialization is required
if (stristr(PHP_OS, 'win') && !stristr(PHP_OS, 'darwin')) {
    $CFG->ostype = 'WINDOWS';
} else {
    $CFG->ostype = 'UNIX';
}
$CFG->os = PHP_OS;

// Configure ampersands in URLs
ini_set('arg_separator.output', '&amp;');

// Work around for a PHP bug   see MDL-11237
ini_set('pcre.backtrack_limit', 20971520);  // 20 MB

// Work around for PHP7 bug #70110. See MDL-52475 .
if (ini_get('pcre.jit')) {
    ini_set('pcre.jit', 0);
}

// Set PHP default timezone to server timezone.
core_date::set_default_server_timezone();

// Location of standard files
$CFG->wordlist = $CFG->libdir .'/wordlist.txt';
$CFG->moddata  = 'moddata';

// neutralise nasty chars in PHP_SELF
if (isset($_SERVER['PHP_SELF'])) {
    $phppos = strpos($_SERVER['PHP_SELF'], '.php');
    if ($phppos !== false) {
        $_SERVER['PHP_SELF'] = substr($_SERVER['PHP_SELF'], 0, $phppos+4);
    }
    unset($phppos);
}

// initialise ME's - this must be done BEFORE starting of session!
initialise_fullme();

// define SYSCONTEXTID in config.php if you want to save some queries,
// after install it must match the system context record id.
if (!defined('SYSCONTEXTID')) {
    context_system::instance();
}

// Defining the site - aka frontpage course
try {
    $SITE = get_site();
} catch (moodle_exception $e) {
    $SITE = null;
    if (empty($CFG->version)) {
        $SITE = new stdClass();
        $SITE->id = 1;
        $SITE->shortname = null;
    } else {
        throw $e;
    }
}
// And the 'default' course - this will usually get reset later in require_login() etc.
$COURSE = clone($SITE);
// Id of the frontpage course.
define('SITEID', $SITE->id);

// init session prevention flag - this is defined on pages that do not want session
if (CLI_SCRIPT) {
    // no sessions in CLI scripts possible
    define('NO_MOODLE_COOKIES', true);

} else if (WS_SERVER) {
    // No sessions possible in web services.
    define('NO_MOODLE_COOKIES', true);

} else if (!defined('NO_MOODLE_COOKIES')) {
    if (empty($CFG->version) or $CFG->version < 2009011900) {
        // no session before sessions table gets created
        define('NO_MOODLE_COOKIES', true);
    } else if (CLI_SCRIPT) {
        // CLI scripts can not have session
        define('NO_MOODLE_COOKIES', true);
    } else {
        define('NO_MOODLE_COOKIES', false);
    }
}

// Start session and prepare global $SESSION, $USER.
if (empty($CFG->sessiontimeout)) {
    $CFG->sessiontimeout = 7200;
}
\core\session\manager::start();

// Set default content type and encoding, developers are still required to use
// echo $OUTPUT->header() everywhere, anything that gets set later should override these headers.
// This is intended to mitigate some security problems.
if (AJAX_SCRIPT) {
    if (!core_useragent::supports_json_contenttype()) {
        // Some bloody old IE.
        @header('Content-type: text/plain; charset=utf-8');
        @header('X-Content-Type-Options: nosniff');
    } else if (!empty($_FILES)) {
        // Some ajax code may have problems with json and file uploads.
        @header('Content-type: text/plain; charset=utf-8');
    } else {
        @header('Content-type: application/json; charset=utf-8');
    }
} else if (!CLI_SCRIPT) {
    @header('Content-type: text/html; charset=utf-8');
}

// Totara: Block or restrict referrers if needed.
if (!CLI_SCRIPT) {
    $referrerpolicy = get_referrer_policy();
    if ($referrerpolicy !== null) {
        @header('Referrer-Policy: ' . $referrerpolicy);
    }
    unset($referrerpolicy);
}

// Totara: force https-only-access if requested, note you cannot easily disable this setting later!
if (!empty($CFG->stricttransportsecurity)) {
    if (strpos($CFG->wwwroot, 'https:') === 0) {
        header('Strict-Transport-Security: max-age=16070400'); // To be remembered for 186 days.
    }
}
// Totara: prevent embedding of server files in external PDF/Flash.
if (!empty($CFG->permittedcrossdomainpolicies)) {
    header('X-Permitted-Cross-Domain-Policies: ' . $CFG->permittedcrossdomainpolicies);
}

// Initialise some variables that are supposed to be set in config.php only.
if (!isset($CFG->filelifetime)) {
    $CFG->filelifetime = 60*60*6;
}

// Late profiling, only happening if early one wasn't started
if (!empty($CFG->profilingenabled)) {
    profiling_start();
}

// Process theme change in the URL.
if (!empty($CFG->allowthemechangeonurl) and !empty($_GET['theme'])) {
    // we have to use _GET directly because we do not want this to interfere with _POST
    $urlthemename = optional_param('theme', '', PARAM_PLUGIN);
    try {
        $themeconfig = theme_config::load($urlthemename);
        // Makes sure the theme can be loaded without errors.
        if ($themeconfig->name === $urlthemename) {
            $SESSION->theme = $urlthemename;
        } else {
            unset($SESSION->theme);
        }
        unset($themeconfig);
        unset($urlthemename);
    } catch (Exception $e) {
        debugging('Failed to set the theme from the URL.', DEBUG_DEVELOPER, $e->getTrace());
    }
}
unset($urlthemename);
// Pre-login tenant themes.
if (!empty($CFG->tenantsenabled) && !empty($CFG->allowprelogintenanttheme)) {
    if (!isloggedin() || isguestuser()) {
        if (isset($_GET['tenanttheme'])) {
            $themedtenant = $DB->get_record('tenant', ['idnumber' => clean_param($_GET['tenanttheme'], PARAM_RAW)]);
            if ($themedtenant) {
                $SESSION->themetenantid = $themedtenant->id;
                $SESSION->themetenantidnumber = $themedtenant->idnumber;
            } else {
                unset($SESSION->themetenantid);
                unset($SESSION->themetenantidnumber);
            }
            unset($themedtenant);
        }
    }
}

// Ensure a valid theme is set.
if (!isset($CFG->theme)) {
    $CFG->theme = 'ventura';
}

// Set language/locale of printed times.  If user has chosen a language that
// that is different from the site language, then use the locale specified
// in the language file.  Otherwise, if the admin hasn't specified a locale
// then use the one from the default language.  Otherwise (and this is the
// majority of cases), use the stored locale specified by admin.
// note: do not accept lang parameter from POST
if (isset($_GET['lang']) and ($lang = optional_param('lang', '', PARAM_SAFEDIR))) {
    if (get_string_manager()->translation_exists($lang, false)) {
        $SESSION->lang = $lang;
    }
}
unset($lang);

// PARAM_SAFEDIR used instead of PARAM_LANG because using PARAM_LANG results
// in an empty string being returned when a non-existant language is specified,
// which would make it necessary to log out to undo the forcelang setting.
// With PARAM_SAFEDIR, it's possible to specify ?forcelang=none to drop the forcelang effect.
if ($forcelang = optional_param('forcelang', '', PARAM_SAFEDIR)) {
    if (isloggedin()
        && get_string_manager()->translation_exists($forcelang, false)
        && has_capability('moodle/site:forcelanguage', context_system::instance())) {
        $SESSION->forcelang = $forcelang;
    } else if (isset($SESSION->forcelang)) {
        unset($SESSION->forcelang);
    }
}
unset($forcelang);

setup_lang_from_browser();

if (empty($CFG->lang)) {
    if (empty($SESSION->lang)) {
        $CFG->lang = 'en';
    } else {
        $CFG->lang = $SESSION->lang;
    }
}

// Set the default site locale, a lot of the stuff may depend on this
// it is definitely too late to call this first in require_login()!
moodle_setlocale();

// Create the $PAGE global - this marks the PAGE and OUTPUT fully initialised, this MUST be done at the end of setup!
if (!empty($CFG->moodlepageclass)) {
    if (!empty($CFG->moodlepageclassfile)) {
        require_once($CFG->moodlepageclassfile);
    }
    $classname = $CFG->moodlepageclass;
} else {
    $classname = 'moodle_page';
}
$PAGE = new $classname();
unset($classname);

// Totara: login user automatically via persistent login if not forbidden..
if (!defined('PERSISTENT_LOGIN_SKIP') or !PERSISTENT_LOGIN_SKIP) {
    if (!empty($CFG->persistentloginenable) and session_id() and !isloggedin()) {
        \totara_core\persistent_login::attempt_auto_login();
    }
}

if (!empty($CFG->debugvalidators) and !empty($CFG->guestloginbutton)) {
    if ($CFG->theme == 'standard') {    // Temporary measure to help with XHTML validation
        if (isset($_SERVER['HTTP_USER_AGENT']) and empty($USER->id)) {      // Allow W3CValidator in as user called w3cvalidator (or guest)
            if ((strpos($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator') !== false) or
                (strpos($_SERVER['HTTP_USER_AGENT'], 'Cynthia') !== false )) {
                if ($user = get_complete_user_data("username", "w3cvalidator")) {
                    $user->ignoresesskey = true;
                } else {
                    $user = guest_user();
                }
                \core\session\manager::set_user($user);
            }
        }
    }
}

// Apache log integration. In apache conf file one can use ${MOODULEUSER}n in
// LogFormat to get the current logged in username in moodle.
// Alternatvely for other web servers a header X-TOTARAUSER can be set which
// can be using in the logfile and stripped out if needed.
if ($USER && isset($USER->username)) {
    $logmethod = '';
    $logvalue = 0;
    if (!empty($CFG->apacheloguser) && function_exists('apache_note')) {
        $logmethod = 'apache';
        $logvalue = $CFG->apacheloguser;
    }
    if (!empty($CFG->headerloguser)) {
        $logmethod = 'header';
        $logvalue = $CFG->headerloguser;
    }
    if (!empty($logmethod)) {
        $loguserid = $USER->id;
        $logusername = clean_filename($USER->username);
        $logname = '';
        if (isset($USER->firstname)) {
            // We can assume both will be set
            // - even if to empty.
            $logname = clean_filename($USER->firstname . " " . $USER->lastname);
        }
        if (\core\session\manager::is_loggedinas()) {
            $realuser = \core\session\manager::get_realuser();
            $logusername = clean_filename($realuser->username." as ".$logusername);
            $logname = clean_filename($realuser->firstname." ".$realuser->lastname ." as ".$logname);
            $loguserid = clean_filename($realuser->id." as ".$loguserid);
        }
        switch ($logvalue) {
            case 3:
                $logname = $logusername;
                break;
            case 2:
                $logname = $logname;
                break;
            case 1:
            default:
                $logname = $loguserid;
                break;
        }
        if ($logmethod == 'apache') {
            apache_note('TOTARAUSER', $logname);
        }

        if ($logmethod == 'header') {
            header("X-TOTARAUSER: $logname");
        }
    }
}

// Ensure the urlrewriteclass is setup correctly (to avoid crippling site).
if (isset($CFG->urlrewriteclass)) {
    if (!class_exists($CFG->urlrewriteclass)) {
        debugging("urlrewriteclass {$CFG->urlrewriteclass} was not found, disabling.");
        unset($CFG->urlrewriteclass);
    } else if (!in_array('core\output\url_rewriter', class_implements($CFG->urlrewriteclass))) {
        debugging("{$CFG->urlrewriteclass} does not implement core\output\url_rewriter, disabling.", DEBUG_DEVELOPER);
        unset($CFG->urlrewriteclass);
    }
}

// Use a custom script replacement if one exists
if (!empty($CFG->customscripts)) {
    if (($customscript = custom_script_path()) !== false) {
        require ($customscript);
    }
}

if (PHPUNIT_TEST) {
    // no ip blocking, these are CLI only
} else if (CLI_SCRIPT and !defined('WEB_CRON_EMULATED_CLI')) {
    // no ip blocking
} else if (!empty($CFG->allowbeforeblock)) { // allowed list processed before blocked list?
    // in this case, ip in allowed list will be performed first
    // for example, client IP is 192.168.1.1
    // 192.168 subnet is an entry in allowed list
    // 192.168.1.1 is banned in blocked list
    // This ip will be banned finally
    if (!empty($CFG->allowedip)) {
        if (!remoteip_in_list($CFG->allowedip)) {
            die(get_string('ipblocked', 'admin'));
        }
    }
    // need further check, client ip may a part of
    // allowed subnet, but a IP address are listed
    // in blocked list.
    if (!empty($CFG->blockedip)) {
        if (remoteip_in_list($CFG->blockedip)) {
            die(get_string('ipblocked', 'admin'));
        }
    }

} else {
    // in this case, IPs in blocked list will be performed first
    // for example, client IP is 192.168.1.1
    // 192.168 subnet is an entry in blocked list
    // 192.168.1.1 is allowed in allowed list
    // This ip will be allowed finally
    if (!empty($CFG->blockedip)) {
        if (remoteip_in_list($CFG->blockedip)) {
            // if the allowed ip list is not empty
            // IPs are not included in the allowed list will be
            // blocked too
            if (!empty($CFG->allowedip)) {
                if (!remoteip_in_list($CFG->allowedip)) {
                    die(get_string('ipblocked', 'admin'));
                }
            } else {
                die(get_string('ipblocked', 'admin'));
            }
        }
    }
    // if blocked list is null
    // allowed list should be tested
    if(!empty($CFG->allowedip)) {
        if (!remoteip_in_list($CFG->allowedip)) {
            die(get_string('ipblocked', 'admin'));
        }
    }

}

// // try to detect IE6 and prevent gzip because it is extremely buggy browser
if (!empty($_SERVER['HTTP_USER_AGENT']) and strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== false) {
    ini_set('zlib.output_compression', 'Off');
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', 1);
    }
}

// Switch to CLI maintenance mode if required, we need to do it here after all the settings are initialised.
if (isset($CFG->maintenance_later) and $CFG->maintenance_later <= time()) {
    if (!file_exists("$CFG->dataroot/climaintenance.html")) {
        require_once("$CFG->libdir/adminlib.php");
        enable_cli_maintenance_mode();
    }
    unset_config('maintenance_later');
    if (AJAX_SCRIPT) {
        die;
    } else if (!CLI_SCRIPT) {
        redirect(new moodle_url('/'));
    }
}

// Add behat_shutdown_function to shutdown manager, so we can capture php errors,
// but not necessary for behat CLI command as it's being captured by behat process.
if (defined('BEHAT_SITE_RUNNING') && !defined('BEHAT_TEST')) {
    core_shutdown_manager::register_function('behat_shutdown_function');
}

// note: we can not block non utf-8 installations here, because empty mysql database
// might be converted to utf-8 in admin/index.php during installation

// Totara: This function to protect against timing attacks was added in PHP 5.6.0.
if (!function_exists('hash_equals')) {
    function hash_equals($known_string, $user_string) {
        // If the function doesn't exist, fall back to the standard comparison.
        // It's not safe against timing attacks but it's the best we can do and the risk is very low.
        if ($known_string === $user_string) {
            return true;
        }

        return false;
    }
}


// Allow callbacks as soon possible after setup.php is loaded via a hook.
$hook = new \core\hook\after_config();
$hook->execute();
