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
 * Testing util classes
 *
 * @abstract
 * @package    core
 * @category   test
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Utils for test sites creation
 *
 * @package   core
 * @category  test
 * @copyright 2012 Petr Skoda {@link http://skodak.org}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class testing_util {

    /**
     * @var \core\testing\generator
     */
    protected static $generator = null;

    /**
     * @var string current version hash from php files
     */
    protected static $versionhash = null;

    /**
     * Returns the testing framework name
     * @static
     * @return string
     */
    final protected static function get_framework() {
        $classname = get_called_class();
        return substr($classname, 0, strpos($classname, '_'));
    }

    /**
     * Get data generator
     * @return \core\testing\generator
     */
    public static function get_data_generator() {
        return \core\testing\generator::instance();
    }

    /**
     * Does this site (db and dataroot) appear to be used for production?
     * We try very hard to prevent accidental damage done to production servers!!
     *
     * @static
     * @return bool
     */
    public static function is_test_site() {
        global $DB, $CFG;

        $framework = self::get_framework();

        if (!file_exists($CFG->dataroot . '/' . $framework . 'testdir.txt')) {
            // this is already tested in bootstrap script,
            // but anyway presence of this file means the dataroot is for testing
            return false;
        }

        $tables = $DB->get_tables(false);
        if ($tables) {
            if (!$DB->get_manager()->table_exists('config')) {
                return false;
            }
            // A direct database request must be used to avoid any possible caching of an older value.
            $dbhash = $DB->get_field('config', 'value', array('name' => $framework . 'test'));
            if (!$dbhash) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether test database and dataroot were created using the current version codebase
     *
     * @return bool
     */
    public static function is_test_data_updated() {
        global $DB, $CFG;

        $framework = self::get_framework();

        $datarootpath = $CFG->dataroot . '/' . $framework;

        if (!file_exists($datarootpath . '/versionshash.txt')) {
            return false;
        }

        $hash = core_component::get_all_versions_hash();
        $oldhash = file_get_contents($datarootpath . '/versionshash.txt');

        if ($hash !== $oldhash) {
            return false;
        }

        // A direct database request must be used to avoid any possible caching of an older value.
        $dbhash = $DB->get_field('config', 'value', array('name' => $framework . 'test'));
        if ($hash !== $dbhash) {
            return false;
        }

        $snapshothash = $DB->get_manager()->snapshot_get_config_value($framework . 'test');
        if ($hash !== $snapshothash) {
            return false;
        }

        return true;
    }

    /**
     * Stores the status of the database
     */
    protected static function store_database_state() {
        global $DB;
        $DB->get_manager()->snapshot_create();
    }

    /**
     * Stores the version hash in both database and dataroot
     */
    protected static function store_versions_hash() {
        global $CFG;

        $framework = self::get_framework();
        $hash = core_component::get_all_versions_hash();

        // add test db flag
        set_config($framework . 'test', $hash);

        // hash all plugin versions - helps with very fast detection of db structure changes
        $hashfile = $CFG->dataroot . '/' . $framework . '/versionshash.txt';
        file_put_contents($hashfile, $hash);
        testing_fix_file_permissions($hashfile);
    }

    /**
     * Reset all database tables to default values.
     * @static
     * @return bool true if reset done, false if skipped
     */
    public static function reset_database() {
        global $DB;

        $DB->get_manager()->snapshot_rollback();

        return true;
    }

    /**
     * Purge dataroot directory
     * @static
     * @return void
     */
    public static function reset_dataroot() {
        global $CFG;

        // Totara: do not clear stat cache here, we do not want to slow down phpunit.

        $framework = self::get_framework();

        $datarootskiponreset = array('.', '..', '.htaccess', 'filedir', 'trashdir', 'temp', 'cache', 'localcache');
        $datarootskiponreset[] = $framework;
        $datarootskiponreset[] = $framework . 'testdir.txt';

        // Clean up the dataroot folder.
        $files = scandir($CFG->dataroot);
        foreach ($files as $item) {
            if (in_array($item, $datarootskiponreset)) {
                continue;
            }
            if (is_dir("$CFG->dataroot/$item")) {
                remove_dir("$CFG->dataroot/$item", false);
            } else {
                unlink("$CFG->dataroot/$item");
            }
        }

        // Totara: there is no need to purge the file dir during tests!

        // Reset the cache and temp dirs if not empty.
        if (!file_exists("$CFG->dataroot/temp")) {
            make_temp_directory('');
        } else if (count(scandir("$CFG->dataroot/temp")) > 2) {
            remove_dir("$CFG->dataroot/temp", true);
        }
        if (!file_exists("$CFG->dataroot/cache")) {
            make_cache_directory('');
        } else if (count(scandir("$CFG->dataroot/cache")) > 2) {
            remove_dir("$CFG->dataroot/cache", true);
        }
        if (!file_exists("$CFG->dataroot/localcache")) {
            make_localcache_directory('');
        } else if (count(scandir("$CFG->dataroot/cache")) > 2) {
            remove_dir("$CFG->dataroot/localcache", true);
        }
    }

    /**
     * Gets a text-based site version description.
     *
     * @return string The site info
     */
    public static function get_site_info() {
        global $CFG, $DB;

        $output = '';

        // All developers have to understand English, do not localise!
        $env = self::get_environment();

        $output .= "Totara ".$env['totararelease'];
        if ($hash = self::get_git_hash()) {
            $output .= ", $hash";
        }
        $output .= "\n";

        // Add php version.
        require_once($CFG->libdir.'/environmentlib.php');
        $output .= "PHP: ". normalize_version($env['phpversion']);

        // Add database type and version.
        $output .= ", " . $env['dbtype'] . ": " . $env['dbversion'];

        // Show collation to help devs identify known MySQL issues.
        if ($DB->get_dbfamily() === 'mysql') {
            $output .= '/' . $DB->get_dbcollation();
        }

        // OS details.
        $output .= ", OS: " . $env['os'] . "\n";

        // Time to that we can see how long it has been running for. Use r so that we can easily parse it if necessary.
        $output .= "Started " . date('r') . "\n";

        return $output;
    }

    /**
     * Try to get current git hash of the Moodle in $CFG->dirroot.
     * @return string null if unknown, sha1 hash if known
     */
    public static function get_git_hash() {
        global $CFG;

        // This is a bit naive, but it should mostly work for all platforms.

        if (!file_exists("$CFG->dirroot/.git/HEAD")) {
            return null;
        }

        $headcontent = file_get_contents("$CFG->dirroot/.git/HEAD");
        if ($headcontent === false) {
            return null;
        }

        $headcontent = trim($headcontent);

        // If it is pointing to a hash we return it directly.
        if (strlen($headcontent) === 40) {
            return $headcontent;
        }

        if (strpos($headcontent, 'ref: ') !== 0) {
            return null;
        }

        $ref = substr($headcontent, 5);

        if (!file_exists("$CFG->dirroot/.git/$ref")) {
            return null;
        }

        $hash = file_get_contents("$CFG->dirroot/.git/$ref");

        if ($hash === false) {
            return null;
        }

        $hash = trim($hash);

        if (strlen($hash) != 40) {
            return null;
        }

        return $hash;
    }

    /**
     * Drop the whole test database
     * @static
     * @param bool $displayprogress
     */
    protected static function drop_database($displayprogress = false) {
        global $DB, $CFG;

        $tables = $DB->get_tables(false);
        if (isset($tables['config'])) {
            // config always last to prevent problems with interrupted drops!
            unset($tables['config']);
            $tables['config'] = 'config';
        }

        if ($displayprogress) {
            echo "Dropping tables:\n";
        }

        // Totara: drop the snapshot stuff first.
        $DB->get_manager()->snapshot_drop();
        $dbfamily = $DB->get_dbfamily();
        $prefix = $DB->get_prefix();

        $dotsonline = 0;
        if ($dbfamily === 'mssql') {
            // Totara: MS SQL does not have DROP with CASCADE, so delete all foreign keys first.
            $sql = "SELECT constraint_name
                      FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                     WHERE table_name = :name AND constraint_name LIKE :fk ESCAPE '\\'";
            $params = ['fk' => str_replace('_', '\\_', $prefix) . '%' . '\\_fk'];
            foreach ($tables as $tablename) {
                $params['name'] = $prefix.$tablename;
                $fks = $DB->get_fieldset_sql($sql, $params);
                foreach ($fks as $fk) {
                    $DB->change_database_structure("ALTER TABLE \"{$prefix}{$tablename}\" DROP CONSTRAINT {$fk}");
                }
            }
        }
        if ($dbfamily === 'mysql') {
            // Totara: MySQL does not have DROP with CASCADE, so delete all foreign keys first.
            $sql = "SELECT constraint_name
                      FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                     WHERE table_name = :name AND unique_constraint_schema = :database AND constraint_name LIKE :fk ESCAPE '\\\\'";
            $params = ['fk' => str_replace('_', '\\_', $prefix) . '%' . '\\_fk', 'database' => $CFG->dbname];
            foreach ($tables as $tablename) {
                $params['name'] = $prefix.$tablename;
                $fks = $DB->get_fieldset_sql($sql, $params);
                foreach ($fks as $fk) {
                    $DB->change_database_structure("ALTER TABLE \"{$prefix}{$tablename}\" DROP FOREIGN KEY {$fk}");
                }
            }
        }
        foreach ($tables as $tablename) {
            // Totara: do not use DDL here, we need to get rid of circular foreign keys and potentially other stuff.
            if ($dbfamily === 'mssql') {
                $DB->change_database_structure("DROP TABLE \"{$prefix}{$tablename}\"", [$tablename]);
            } else {
                $DB->change_database_structure("DROP TABLE \"{$prefix}{$tablename}\" CASCADE", [$tablename]);
            }

            if ($dotsonline == 60) {
                if ($displayprogress) {
                    echo "\n";
                }
                $dotsonline = 0;
            }
            if ($displayprogress) {
                echo '.';
            }
            $dotsonline += 1;
        }
        if ($displayprogress) {
            echo "\n";
        }
    }

    /**
     * Drops the test framework dataroot
     * @static
     */
    protected static function drop_dataroot() {
        global $CFG;

        remove_dir($CFG->dataroot, true);
    }

    /**
     * Return list of environment versions on which tests will run.
     * Environment includes:
     * - moodleversion
     * - phpversion
     * - dbtype
     * - dbversion
     * - os
     *
     * @return array
     */
    public static function get_environment() {
        global $CFG, $DB;

        $env = array();

        // Add moodle version.
        $release = null;
        $TOTARA = null;
        require("$CFG->dirroot/version.php");
        $env['moodleversion'] = $release;
        $env['totararelease'] = $TOTARA->release;

        // Add php version.
        $phpversion = phpversion();
        $env['phpversion'] = $phpversion;

        // Add database type and version.
        $dbtype = $CFG->dbtype;
        $dbinfo = $DB->get_server_info();
        $dbversion = $dbinfo['version'];
        $env['dbtype'] = $dbtype;
        $env['dbversion'] = $dbversion;

        // OS details.
        $osdetails = php_uname('s') . " " . php_uname('r') . " " . php_uname('m');
        $env['os'] = $osdetails;

        return $env;
    }
}
