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
 * Prepares PHPUnit environment, the phpunit.xml configuration
 * must specify this file as bootstrap.
 *
 * Exit codes: {@see phpunit_bootstrap_error()}
 *
 * @package    core_phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (isset($_SERVER['REMOTE_ADDR'])) {
    die; // No access from web!
}

define('TOTARA_PHPUNIT_ORIGINAL_CWD', getcwd());

// we want to know about all problems
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Make sure OPcache does not strip comments, we need them in phpunit!
if (ini_get('opcache.enable') and strtolower(ini_get('opcache.enable')) !== 'off') {
    if (!ini_get('opcache.save_comments') or strtolower(ini_get('opcache.save_comments')) === 'off') {
        ini_set('opcache.enable', 0);
    } else {
        ini_set('opcache.load_comments', 1);
    }
}

require_once(__DIR__.'/bootstraplib.php');
require_once(__DIR__.'/../testing/lib.php');
require_once(__DIR__.'/../init.php');

if (isset($_SERVER['REMOTE_ADDR'])) {
    phpunit_bootstrap_error(1, 'Unit tests can be executed only from command line!');
}
if (defined('PHPUNIT_TEST')) {
    phpunit_bootstrap_error(1, "PHPUNIT_TEST constant must not be manually defined anywhere!");
}
if (defined('CLI_SCRIPT')) {
    phpunit_bootstrap_error(1, 'CLI_SCRIPT must not be manually defined in any PHPUnit test scripts');
}

$define_if_not = [
    'IGNORE_COMPONENT_CACHE' => true,
    'PHPUNIT_TEST' => true, /** PHPUnit testing framework active */
    'PHPUNIT_UTIL' => false, /** Identifies utility scripts - the database does not need to be initialised */
    'CLI_SCRIPT' => true,
    'NO_OUTPUT_BUFFERING' => true,
    'PHPUNIT_LONGTEST' => false, /** Execute longer version of tests */
];
array_walk($define_if_not, function ($value, $define) {
    if (!defined($define)) {
        define($define, $value);
    }
});

$phpunitversion = \PHPUnit\Runner\Version::id();
if ($phpunitversion === '@package_version@') {
    // library checked out from git, let's hope dev knows that 9.x is required
} else if (!version_compare($phpunitversion, '9.5.0', '>=') || version_compare($phpunitversion, '10.0', '>')) {
    phpunit_bootstrap_error(PHPUNIT_EXITCODE_PHPUNITWRONG, $phpunitversion);
}
unset($phpunitversion);

// only load CFG from config.php, stop ASAP in lib/setup.php
$GLOBALS['CFG'] = phpunit_bootstrap_initialise_cfg();
global $CFG;

// Some ugly hacks.
$CFG->themerev = 1;
$CFG->jsrev = 1;

// Totara: Make sure the dataroot is ready.
umask(0);

require("$CFG->dirroot/lib/setup.php");

raise_memory_limit(MEMORY_HUGE);
set_time_limit(0);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Load old stuff without autoloading.
require_once(__DIR__.'/classes/testcase_autoloader.php');
require_once(__DIR__.'/lib.php');

if (PHPUNIT_UTIL) {
    return;
}

// is database and dataroot ready for testing?
list($errorcode, $message) = \core_phpunit\internal_util::testing_ready_problem();
// print some version info
\core_phpunit\internal_util::bootstrap_moodle_info();
if ($errorcode) {
    phpunit_bootstrap_error($errorcode, $message);
}

// prepare for the first test run - store fresh globals, reset database and dataroot, etc.
\core_phpunit\internal_util::bootstrap_init();
