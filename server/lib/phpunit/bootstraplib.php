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
 * PHPUnit bootstrap function
 *
 * Note: these functions must be self contained and must not rely on any other library or include
 *
 * @package    core_phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace {

    use core\internal\phpunit\bootstrap;

    define('PHPUNIT_EXITCODE_PHPUNITMISSING', 129);
    define('PHPUNIT_EXITCODE_PHPUNITWRONG', 130);
    define('PHPUNIT_EXITCODE_PHPUNITEXTMISSING', 131);
    define('PHPUNIT_EXITCODE_CONFIGERROR', 135);
    define('PHPUNIT_EXITCODE_CONFIGWARNING', 136);
    define('PHPUNIT_EXITCODE_INSTALL', 140);
    define('PHPUNIT_EXITCODE_REINSTALL', 141);

    /**
     * Print error and stop execution
     * @param int $errorcode The exit error code
     * @param string $text An error message to display
     * @return void stops code execution with error code
     */
    function phpunit_bootstrap_error($errorcode, $text = '') {
        require_once(__DIR__ . '/../testing/lib.php');
        switch ($errorcode) {
            case 0:
                // this is not an error, just print information and exit
                break;
            case 1:
                $text = 'Error: ' . $text;
                break;
            case PHPUNIT_EXITCODE_PHPUNITMISSING:
                $text = "Can not find PHPUnit library, to install use: php composer.phar install";
                break;
            case PHPUNIT_EXITCODE_PHPUNITWRONG:
                $text = 'Totara requires PHPUnit 9, ' . $text . ' is not compatible';
                break;
            case PHPUNIT_EXITCODE_PHPUNITEXTMISSING:
                $text = 'Totara can not find required PHPUnit extension ' . $text;
                break;
            case PHPUNIT_EXITCODE_CONFIGERROR:
                $text = "Totara PHPUnit environment configuration error:\n" . $text;
                break;
            case PHPUNIT_EXITCODE_CONFIGWARNING:
                $text = "Totara PHPUnit environment configuration warning:\n" . $text;
                break;
            case PHPUNIT_EXITCODE_INSTALL:
                $path = testing_cli_argument_path('/admin/tool/phpunit/cli/init.php');
                $text = "Totara PHPUnit environment is not initialised, please use:\n php $path";
                break;
            case PHPUNIT_EXITCODE_REINSTALL:
                $path = testing_cli_argument_path('/admin/tool/phpunit/cli/init.php');
                $text = "Totara PHPUnit environment was initialised for different version, please use:\n php $path";
                break;
            default:
                $text = empty($text) ? '' : ': ' . $text;
                $text = 'Unknown error ' . $errorcode . $text;
                break;
        }

        testing_error($errorcode, $text);
    }

    function phpunit_bootstrap_initialise_cfg(): \core_config {
        $config_file = __DIR__ . '/../../../test/phpunit/config.php';
        $main_config_file = __DIR__ . '/../../../config.php';
        $adjuster = null;
        if (file_exists($config_file)) {
            $adjuster = function (\stdClass $cfg) {
                bootstrap::ensure_minimum_cfg($cfg, false);
                bootstrap::force_cfg_values($cfg);
                bootstrap::modify_for_parallel_testing($cfg);
            };
        } else if (file_exists($main_config_file)) {
            // This should be deprecated soon!
            $config_file = $main_config_file;
            $adjuster = function (\stdClass $cfg) {
                bootstrap::remap_cfg($cfg);
                bootstrap::ensure_minimum_cfg($cfg, true);
                bootstrap::force_cfg_values($cfg);
                bootstrap::modify_for_parallel_testing($cfg);
            };
        } else {
            phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'config.php file not found');
        }

        return \core\internal\config::initialise($config_file, $adjuster);
    }

}

namespace core\internal\phpunit {

    class bootstrap {

        public static function remap_cfg(\stdClass $cfg) {
            // The order of this may be important.
            $property_map = [
                'dirroot' => false,
                'srcroot' => false,
                'admin' => false,
                'dbtype' => false,
                'dblibrary' => false,
                'dbhost' => false,
                'dbname' => false,
                'dbuser' => false,
                'dbpass' => false,
                'dboptions' => false,
                'proxyhost' => false,
                'proxyport' => false,
                'proxytype' => false,
                'proxyuser' => false,
                'proxypassword' => false,
                'proxybypass' => false,
                'altcacheconfigpath' => false,
                'pathtogs' => false,
                'pathtodu' => false,
                'aspellpath' => false,
                'pathtodot' => false,
                'pathtounoconv' => false,
                'dataroot' => function (\stdClass $cfg) {
                    if (!isset($cfg->phpunit_dataroot)) {
                        phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'Missing $CFG->phpunit_dataroot in config.php, can not run tests!');
                    }
                    if (!file_exists($cfg->phpunit_dataroot)) {
                        mkdir($cfg->phpunit_dataroot, $cfg->directorypermissions);
                    }
                    if (!is_dir($cfg->phpunit_dataroot)) {
                        // Create test dir if does not exists yet.
                        phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, '$CFG->phpunit_dataroot directory can not be created, can not run tests!');
                    }
                    // Ensure we access to phpunit_dataroot realpath always.
                    $cfg->phpunit_dataroot = realpath($cfg->phpunit_dataroot);

                    if (isset($cfg->dataroot) and $cfg->phpunit_dataroot === $cfg->dataroot) {
                        phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, '$CFG->dataroot and $CFG->phpunit_dataroot must not be identical, can not run tests!');
                    }

                    if (!is_writable($cfg->phpunit_dataroot)) {
                        // try to fix permissions if possible
                        if (function_exists('posix_getuid')) {
                            $chmod = fileperms($cfg->phpunit_dataroot);
                            if (fileowner($cfg->phpunit_dataroot) == posix_getuid()) {
                                $chmod = $chmod | 0700;
                                chmod($cfg->phpunit_dataroot, $chmod);
                            }
                        }
                        if (!is_writable($cfg->phpunit_dataroot)) {
                            phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, '$CFG->phpunit_dataroot directory is not writable, can not run tests!');
                        }
                    }
                    return $cfg->phpunit_dataroot;
                },
                'prefix' => function (\stdClass $cfg) {
                    // verify db prefix
                    if (!isset($cfg->phpunit_prefix)) {
                        phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'Missing $CFG->phpunit_prefix in config.php, can not run tests!');
                    }
                    if ($cfg->phpunit_prefix === '') {
                        phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, '$CFG->phpunit_prefix can not be empty, can not run tests!');
                    }
                    if (isset($cfg->prefix) and $cfg->prefix === $cfg->phpunit_prefix) {
                        phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, '$CFG->prefix and $CFG->phpunit_prefix must not be identical, can not run tests!');
                    }
                    return $cfg->phpunit_prefix;
                },
                'profilingenabled' => function (\stdClass $cfg) {
                    if (isset($cfg->phpunit_profilingenabled) && $cfg->phpunit_profilingenabled) {
                        $cfg->profilingincluded = '*';
                        return true;
                    }
                    return false;
                }
            ];
            foreach ($property_map as $property => $processor) {
                if (is_callable($processor)) {
                    continue;
                }
                $phpunit_property = 'phpunit_' . $property;
                if (isset($cfg->{$phpunit_property})) {
                    $cfg->{$property} = $cfg->{$phpunit_property};
                } else if ($processor === true) {
                    phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'Missing $CFG->' . $phpunit_property . ' in config.php, can not run tests!');
                }
            }
            foreach ($property_map as $property => $processor) {
                if (!is_callable($processor)) {
                    continue;
                }
                $cfg->{$property} = $processor($cfg);
            }
            foreach ((array)$cfg as $key => $value) {
                if (!isset($property_map[$key])) {
                    unset($cfg->{$key});
                }
            }
        }

        public static function force_cfg_values(\stdClass $cfg) {
            if (!isset($cfg->directorypermissions)) {
                $cfg->directorypermissions = 0770;
            }
            $cfg->filepermissions = ($cfg->directorypermissions & 0666);
            $cfg->wwwroot = 'https://www.example.com/moodle';

            // Do not allow overrides from config.php for these settings.
            unset($cfg->debug);
            unset($cfg->debugdeveloper);
            unset($cfg->debugdisplay);
            unset($cfg->themerev);
            unset($cfg->jsrev);
        }

        public static function ensure_minimum_cfg(\stdClass $cfg, bool $mainconfig) {
            $required = [
                'dataroot',
                'prefix',
                'dbtype',
                'dblibrary',
                'dbhost',
                'dbname',
                'dbuser',
                'dbpass',
                'dboptions',
            ];
            // dbuser and dbpass are optional on SQL Server.
            if (isset($cfg->dbtype) && $cfg->dbtype === 'sqlsrv') {
                $required = array_diff($required, ['dbuser', 'dbpass']);
            }
            array_walk($required, function ($property) use ($cfg, $mainconfig) {
                if (!isset($cfg->{$property})) {
                    if ($mainconfig) {
                        $phpunit_property = 'phpunit_' . $property;
                        phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'Missing $CFG->' . $phpunit_property . ' in /config.php, can not run tests!');
                    } else {
                        phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'Missing $CFG->' . $property . ' in /test/phpunit/config.php, can not run tests!');
                    }
                }
            });
        }

        public static function modify_for_parallel_testing(\stdClass $cfg) {
            // Totara: decide which instance to use.
            if (!defined('PHPUNIT_INSTANCE')) {
                if (getenv('TEST_TOKEN') !== false) {
                    // Running paratest.
                    define('PHPUNIT_PARATEST', true);

                    // NOTE: test tokens are inconsistent for different runners, see https://github.com/paratestphp/paratest/issues/213
                    $token = getenv('TEST_TOKEN');

                    if (!is_numeric($token)) {
                        echo "Invalid paratest token\n";
                        exit(1);
                    }

                    $token = str_pad((string)$token, 2, '0', STR_PAD_LEFT);
                    if (!file_exists($cfg->dataroot . '/' . $token)) {
                        echo "Environemnt not initialised\n";
                        exit(1);
                    }
                    define('PHPUNIT_INSTANCE', $token);

                } else {
                    // Normal run or paratest is just starting.
                    define('PHPUNIT_INSTANCE', '00');
                }
            }

            // Add the PHPUnit instance token to dataroot and prefix to create separation.
            $cfg->dataroot = $cfg->dataroot . DIRECTORY_SEPARATOR . PHPUNIT_INSTANCE;
            $cfg->prefix = $cfg->prefix . PHPUNIT_INSTANCE;

            // Set up dataroot at this point.
            testing_initdataroot($cfg->dataroot, 'phpunit', $cfg->directorypermissions, $cfg->filepermissions);
        }
    }
}