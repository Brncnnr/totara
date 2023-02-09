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
 * Utility class.
 *
 * @package    core_phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_phpunit;

/**
 * Collection of utility methods.
 *
 * @package    core_phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class internal_util {
    /**
     * @var int last value of db writes counter, used for db resetting
     */
    public static $lastdbwrites = null;

    /** @var array An array of original globals, restored after each test */
    protected static $globals = array();

    /** @var array list of debugging messages triggered during the last test execution */
    protected static $debuggings = array();

    /** @var message_sink alternative target for moodle messaging */
    protected static $messagesink = null;

    /** @var phpmailer_sink alternative target for phpmailer messaging */
    protected static $phpmailersink = null;

    /** @var event_sink alternative target for moodle messaging */
    protected static $eventsink = null;

    /** @var hook_sink alternative target for hooks */
    protected static $hooksink = null;

    /**
     * @var array Files to skip when dropping dataroot folder
     */
    protected static $datarootskipondrop = array('.', '..', 'lock');

    /** @var cache_factory $cachefactory */
    protected static $cachefactory;

    /** @var string|null $request_origin */
    protected static $request_origin = null;

    /**
     * Does this site (db and dataroot) appear to be used for production?
     * We try very hard to prevent accidental damage done to production servers!!
     *
     * @static
     * @return bool
     */
    public static function is_test_site() {
        global $DB, $CFG;

        if (!file_exists($CFG->dataroot . '/phpunittestdir.txt')) {
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
            $dbhash = $DB->get_field('config', 'value', array('name' => 'phpunittest'));
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

        $versionshashfile = $CFG->dataroot . '/phpunit/versionshash.txt';

        if (!file_exists($versionshashfile)) {
            return false;
        }

        $hash = \core_component::get_all_versions_hash();
        $oldhash = file_get_contents($versionshashfile);

        if ($hash !== $oldhash) {
            return false;
        }

        // A direct database request must be used to avoid any possible caching of an older value.
        $dbhash = $DB->get_field('config', 'value', array('name' => 'phpunittest'));
        if ($hash !== $dbhash) {
            return false;
        }

        $snapshothash = $DB->get_manager()->snapshot_get_config_value('phpunittest');
        if ($hash !== $snapshothash) {
            return false;
        }

        return true;
    }

    /**
     * Stores the version hash in both database and dataroot
     */
    protected static function store_versions_hash() {
        global $CFG;

        $hash = \core_component::get_all_versions_hash();

        // add test db flag
        set_config('phpunittest', $hash);

        // hash all plugin versions - helps with very fast detection of db structure changes
        $hashfile = $CFG->dataroot . '/phpunit/versionshash.txt';
        file_put_contents($hashfile, $hash);
        testing_fix_file_permissions($hashfile);
    }

    /**
     * Purge dataroot directory
     * @static
     * @return void
     */
    public static function reset_dataroot() {
        global $CFG;

        // Totara: do not clear stat cache here, we do not want to slow down phpunit.

        $datarootskiponreset = array('.', '..', '.htaccess', 'filedir', 'trashdir', 'temp', 'cache', 'localcache');
        $datarootskiponreset[] = 'phpunit';
        $datarootskiponreset[] = 'phpunittestdir.txt';

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

    public static function acquire_lock(): void {
        require_once(__DIR__ . '/../../testing/classes/test_lock.php');
        \test_lock::acquire('phpunit');
    }

    /**
     * Reset contents of all database tables to initial values, reset caches, etc.
     *
     * Note: this is relatively slow (cca 2 seconds for pg and 7 for mysql) - please use with care!
     *
     * @static
     * @param bool $detectchanges
     *      true  - changes in global state and database are reported as errors
     *      false - no errors reported
     *      null  - only critical problems are reported as errors
     * @return void
     */
    public static function reset_all_data($detectchanges = false) {
        global $DB, $CFG, $USER, $SITE, $COURSE, $PAGE, $OUTPUT, $SESSION, $FULLME;
        global $ME, $SCRIPT; // Totara: fix global resets.

        // Stop any message redirection.
        self::stop_message_redirection();

        // Stop any message redirection.
        self::stop_event_redirection();
        self::stop_hook_redirection();

        // Start a new email redirection.
        // This will clear any existing phpmailer redirection.
        // We redirect all phpmailer output to this message sink which is
        // called instead of phpmailer actually sending the message.
        self::start_phpmailer_redirection();

        // We used to call gc_collect_cycles here to ensure desctructors were called between tests.
        // This accounted for 25% of the total time running phpunit - so we removed it.

        // Show any unhandled debugging messages, the runbare() could already reset it.
        self::display_debugging_messages();
        self::reset_debugging();

        // reset global $DB in case somebody mocked it
        $DB = self::get_global_backup('DB');

        if ($DB->is_transaction_started()) {
            // we can not reset inside transaction
            $DB->force_transaction_rollback();
        }

        $resetdb = self::reset_database();
        $localename = self::get_locale_name();
        $warnings = array();

        if ($detectchanges === true) {
            if ($resetdb) {
                $warnings[] = 'Warning: unexpected database modification, resetting DB state';
            }

            $oldcfg = self::get_global_backup('CFG');
            $oldsite = self::get_global_backup('SITE');
            foreach($CFG as $k=>$v) {
                if (!property_exists($oldcfg, $k)) {
                    $warnings[] = 'Warning: unexpected new $CFG->'.$k.' value';
                } else if ($oldcfg->$k !== $CFG->$k) {
                    $warnings[] = 'Warning: unexpected change of $CFG->'.$k.' value';
                }
                unset($oldcfg->$k);

            }
            if ($oldcfg) {
                foreach($oldcfg as $k=>$v) {
                    $warnings[] = 'Warning: unexpected removal of $CFG->'.$k;
                }
            }

            if ($USER->id != 0) {
                $warnings[] = 'Warning: unexpected change of $USER';
            }

            if ($COURSE->id != $oldsite->id) {
                $warnings[] = 'Warning: unexpected change of $COURSE';
            }

            if ($FULLME !== self::get_global_backup('FULLME')) {
                $warnings[] = 'Warning: unexpected change of $FULLME';
            }

            if (setlocale(LC_TIME, 0) !== $localename) {
                $warnings[] = 'Warning: unexpected change of locale';
            }
        }

        if (ini_get('max_execution_time') != 0) {
            // This is special warning for all resets because we do not want any
            // libraries to mess with timeouts unintentionally.
            // Our PHPUnit integration is not supposed to change it either.

            if ($detectchanges !== false) {
                $warnings[] = 'Warning: max_execution_time was changed to '.ini_get('max_execution_time');
            }
            set_time_limit(0);
        }

        // restore original globals
        $_SERVER = self::get_global_backup('_SERVER');
        $CFG = self::get_global_backup('CFG');
        $SITE = self::get_global_backup('SITE');
        $FULLME = self::get_global_backup('FULLME');
        $_GET = array();
        $_POST = array();
        $_FILES = array();
        $_REQUEST = array();
        $COURSE = $SITE;

        // reinitialise following globals
        $OUTPUT = new \bootstrap_renderer();
        $PAGE = new \moodle_page();
        $FULLME = null;
        $ME = null;
        $SCRIPT = null;

        // Empty sessison and set fresh new not-logged-in user.
        \core\session\manager::init_empty_session();

        // reset all static caches
        \core\event\manager::phpunit_reset();
        accesslib_clear_all_caches_for_unit_testing();
        get_string_manager()->reset_caches(true);
        reset_text_filters_cache(true);
        events_get_handlers('reset');
        \core_text::reset_caches();
        get_message_processors(false, true, true);
        \filter_manager::reset_caches();
        \core_filetypes::reset_caches();
        \core\orm\entity\buffer::clear();
        \core_useragent::phpunit_reset(); // Totara: Make sure useragent tests are properly isolated.
        if (class_exists('prog_messages_manager', false)) {
            // Program messages exists, reset its caches just in case they have been used.
            \prog_messages_manager::reset_cache();
        }
        if (class_exists('rb_source_appraisal_detail', false)) {
            // Appraisal detail report source class exists, reset its caches just in case they have been used.
            \rb_source_appraisal_detail::reset_cache();
        }
        \totara_catalog\cache_handler::reset_all_caches();

        \core_search\manager::clear_static();
        \core_user::reset_caches();
        if (class_exists('core_media_manager', false)) {
            \core_media_manager::reset_caches();
        }

        // Reset static unit test options.
        if (class_exists('\availability_date\condition', false)) {
            \availability_date\condition::set_current_time_for_test(0);
        }

        // Reset internal users.
        \core_user::reset_internal_users();

        // Totara specific resets.
        \totara_core\hook\manager::phpunit_reset();
        if (class_exists('totara_core\jsend', false)) {
            \totara_core\jsend::set_phpunit_testdata(null);
        }
        if (session_id() !== '') {
            // Totara Connect fakes the sid in tests.
            if (session_status() == PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            session_id('');
        }

        // Check if report builder has been loaded and if so reset the source object cache.
        // Don't autoload here - it won't work.
        if (class_exists('reportbuilder', false)) {
            \reportbuilder::reset_caches();
            \reportbuilder::reset_source_object_cache();

            // Reset source object helpers, these cache data used to create columms and filters.
            \totara_customfield\report_builder_field_loader::reset();
            \core_tag\report_builder_tag_loader::reset();
        }

        // Reset course and module caches.
        if (class_exists('format_base')) {
            // If file containing class is not loaded, there is no cache there anyway.
            \format_base::reset_course_cache(0);
        }
        get_fast_modinfo(0, 0, true);

        // Reset other singletons.
        if (class_exists('core_plugin_manager')) {
            \core_plugin_manager::reset_caches(true);
        }
        if (class_exists('\core\update\checker')) {
            \core\update\checker::reset_caches(true);
        }

        // Clear static cache within restore.
        if (class_exists('restore_section_structure_step')) {
            \restore_section_structure_step::reset_caches();
        }

        // Clear static cache within restore.
        if (class_exists('restore_section_structure_step')) {
            \restore_section_structure_step::reset_caches();
        }

        // Clear core_link mock url
        if (class_exists('http_mock_request')) {
            \http_mock_request::clear();
        }

        // purge dataroot directory
        self::reset_dataroot();

        if (self::$cachefactory) {
            // Totara: switch back to fast phpunit caches.
            self::$cachefactory->phpunit_reset();
        } else {
            // Purge all data from the caches. This is required for consistency between tests.
            // Any file caches that happened to be within the data root will have already been clearer (because we just deleted cache)
            // and now we will purge any other caches as well.  This must be done before the cache_factory::reset() as that
            // removes all definitions of caches and purge does not have valid caches to operate on.
            \cache_helper::purge_all();
            // Reset the cache API so that it recreates it's required directories as well.
            \cache_factory::reset();
        }

        // restore original config once more in case resetting of caches changed CFG
        $CFG = self::get_global_backup('CFG');

        // inform data generator
        \core\testing\generator::instance()->reset();

        // fix PHP settings
        error_reporting($CFG->debug);

        // Reset the date/time class.
        \core_date::phpunit_reset();

        // Make sure the time locale is consistent - that is Australian English.
        setlocale(LC_TIME, $localename);

        // Reset the log manager cache.
        get_log_manager(true);

        // verify db writes just in case something goes wrong in reset
        if (self::$lastdbwrites != $DB->perf_get_writes()) {
            error_log('Unexpected DB writes in \core_phpunit\internal_util::reset_all_data()');
            self::$lastdbwrites = $DB->perf_get_writes();
        }

        // Reset the container factory's cache.
        \core_container\factory::reset();
        \core_container\factory::reset_containers_map();

        // Reset the user access controller.
        \core_user\access_controller::clear_instance_cache();

        (new \core\hook\phpunit_reset())->execute();

        if ($warnings) {
            $warnings = implode("\n", $warnings);
            trigger_error($warnings, E_USER_WARNING);
        }
    }

    /**
     * Reset all database tables to default values.
     * @static
     * @return bool true if reset done, false if skipped
     */
    public static function reset_database() {
        global $DB;

        if (!is_null(self::$lastdbwrites) and self::$lastdbwrites == $DB->perf_get_writes()) {
            return false;
        }

        $DB->get_manager()->snapshot_rollback();

        self::$lastdbwrites = $DB->perf_get_writes();

        return true;
    }

    /**
     * Called during bootstrap only!
     * @internal
     * @static
     * @return void
     */
    public static function bootstrap_init() {
        global $CFG, $SITE, $DB, $FULLME;

        // backup the globals
        self::$globals['_SERVER'] = $_SERVER;
        self::$globals['CFG'] = clone($CFG);
        self::$globals['SITE'] = clone($SITE);
        self::$globals['DB'] = $DB;
        self::$globals['FULLME'] = $FULLME;

        if (empty($CFG->altcacheconfigpath) and !defined('TEST_CACHE_USING_ALT_CACHE_CONFIG_PATH')) {
            require_once(__DIR__ . '/cache_factory.php');
            self::$cachefactory = new cache_factory();
        }

        // refresh data in all tables, clear caches, etc.
        self::$lastdbwrites = null;
        self::reset_all_data();

        if (self::$cachefactory) {
            self::$cachefactory->prime_caches();
            self::reset_all_data();
        }
    }

    /**
     * Print some Moodle related info to console.
     * @internal
     * @static
     * @return void
     */
    public static function bootstrap_moodle_info() {
        if (defined('PHPUNIT_PARATEST') and PHPUNIT_PARATEST) {
            return;
        }
        require_once(__DIR__ . '/../../testing/classes/util.php');

        echo \testing_util::get_site_info();
    }

    /**
     * Returns original state of global variable.
     * @static
     * @param string $name
     * @return mixed
     */
    public static function get_global_backup($name) {
        if ($name === 'DB') {
            // no cloning of database object,
            // we just need the original reference, not original state
            return self::$globals['DB'];
        }
        if (isset(self::$globals[$name])) {
            if (is_object(self::$globals[$name])) {
                $return = clone(self::$globals[$name]);
                return $return;
            } else {
                return self::$globals[$name];
            }
        }
        return null;
    }

    /**
     * Is this site initialised to run unit tests?
     *
     * @static
     * @return array errorcode=>message, 0 means ok
     */
    public static function testing_ready_problem() {
        global $DB;

        $localename = self::get_locale_name();
        if (setlocale(LC_TIME, $localename) === false) {
            return array(PHPUNIT_EXITCODE_CONFIGERROR, "Required locale '$localename' is not installed.");
        }

        if (!self::is_test_site()) {
            // dataroot was verified in bootstrap, so it must be DB
            return array(PHPUNIT_EXITCODE_CONFIGERROR, 'Can not use database for testing, try different prefix');
        }

        $tables = $DB->get_tables(false);
        if (empty($tables)) {
            return array(PHPUNIT_EXITCODE_INSTALL, '');
        }

        if (!self::is_test_data_updated()) {
            return array(PHPUNIT_EXITCODE_REINSTALL, '');
        }

        return array(0, '');
    }

    /**
     * Drop all test site data.
     *
     * Note: To be used from CLI scripts only.
     *
     * @static
     * @param bool $displayprogress if true, this method will echo progress information.
     * @return void may terminate execution with exit code
     */
    public static function drop_site($displayprogress = false) {
        global $CFG;

        if (!self::is_test_site()) {
            phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'Can not drop non-test site!!');
        }

        // Purge dataroot
        if ($displayprogress) {
            echo "Purging dataroot:\n";
        }

        // Drop all tables.
        self::drop_database($displayprogress);

        // Purge dataroot only, but keep the directory.
        self::drop_dataroot();
        testing_initdataroot($CFG->dataroot, 'phpunit');
    }

    /**
     * Perform a fresh test site installation
     *
     * Note: To be used from CLI scripts only.
     *
     * @static
     * @return void may terminate execution with exit code
     */
    public static function install_site() {
        global $DB, $CFG;

        if (!self::is_test_site()) {
            phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'Can not install on non-test site!!');
        }

        if ($DB->get_tables()) {
            list($errorcode, $message) = self::testing_ready_problem();
            if ($errorcode) {
                phpunit_bootstrap_error(PHPUNIT_EXITCODE_REINSTALL, 'Database tables already present, Moodle PHPUnit test environment can not be initialised');
            } else {
                phpunit_bootstrap_error(0, 'Moodle PHPUnit test environment is already initialised');
            }
        }

        self::bootstrap_moodle_info();

        $options = array();
        $options['adminpass'] = 'admin';
        $options['shortname'] = 'phpunit';
        $options['fullname'] = 'PHPUnit test site';

        // Torara: Empty dataroot and initialise it.
        self::drop_dataroot();
        testing_initdataroot($CFG->dataroot, 'phpunit');
        self::reset_dataroot();

        install_cli_database($options, false);

        // Set the admin email address.
        $DB->set_field('user', 'email', 'admin@example.com', array('username' => 'admin'));

        // Sets maximum debug level.
        set_config('debug', DEBUG_DEVELOPER);
        set_config('debugdisplay', 1);

        // Disable all logging for performance and sanity reasons.
        set_config('enabled_stores', '', 'tool_log');

        // Disable Totara registrations.
        set_config('registrationenabled', 0);
        set_config('sitetype', 'development');
        set_config('registrationcode', '');

        // Undo Totara changed defaults to allow upstream testing without hacks.
        // NOTE: completion is automatically enabled since Moodle 3.1
        set_config('forcelogin', 0);
        set_config('enrol_plugins_enabled', 'manual,guest,self,cohort');
        set_config('enableblogs', 1);
        $DB->delete_records('user_preferences', array()); // Totara admin site page default.

        // Totara: purge log tables to speed up DB resets.
        $DB->delete_records('config_log');
        $DB->delete_records('log_display');
        $DB->delete_records('upgrade_log');

        // Need to enable all product features so they don't need to be turned on to test.
        // TL-26867 improvements to enforcing flavour defaults would avoid the need to specify every feature here.
        $disabled_features = [
            'appraisals',  // Legacy - replaced by performance_activities.
            'feedback360', // Legacy - replaced by performance_activities.
        ];
        foreach (\totara_core\advanced_feature::get_available() as $advanced_feature) {
            if (!in_array($advanced_feature, $disabled_features)) {
                \totara_core\advanced_feature::enable($advanced_feature);
            }
        }

        // Totara: there is no need to save filedir files, we do not delete them in tests!

        // Store version hash in the database and in a file.
        self::store_versions_hash();

        // Reset the sequences so that insert in each table returns different 'id' values.
        if (defined('PHPUNIT_SEQUENCE_START')) {
            // NOTE: this constant can only be defined in config.php, not in phpunit.xml!
            $offsetstart = (int)PHPUNIT_SEQUENCE_START;
        } else {
            // Start a sequence between 100000 and 199000 to ensure each call to init produces
            // different ids in the database.  This reduces the risk that hard coded values will
            // end up being placed in phpunit test code.
            $offsetstart = 100000 + mt_rand(0, 99) * 1000;
        }
        $DB->get_manager()->reset_all_sequences($offsetstart, 1000);

        // Store database data and structure.
        $DB->get_manager()->snapshot_create();
    }

    /**
     * Builds srcroot/phpunit.xml files using defaults from /phpunit.xml.dist
     * @static
     * @return bool true means main config file created, false means only dataroot file created
     */
    public static function build_config_file(string $path_xml_dist = null, string $path_xml = null) {
        global $CFG;

        if ($path_xml_dist === null) {
            $path_xml_dist = $CFG->srcroot . '/test/phpunit/phpunit.xml.dist';
        }
        if ($path_xml === null) {
            $path_xml = $CFG->srcroot . '/test/phpunit/phpunit.xml';
        }

        if (!file_exists($path_xml_dist) || !is_readable($path_xml_dist)) {
            phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, "Can not read phpunit.xml.dist file");
        }

        $template = '
        <testsuite name="@component@_testsuite">
            <directory suffix="_test.php">@dir@</directory>
        </testsuite>';
        $data = file_get_contents($path_xml_dist);

        // fix link to schema and other hardcoded paths
        $data = str_replace('xsi:noNamespaceSchemaLocation="../../server/', 'xsi:noNamespaceSchemaLocation="file:' . $CFG->srcroot . '/server/', $data);
        $data = str_replace('../../server/', $CFG->srcroot . '/server/', $data);

        $suites = '';
        $subsystems = \core_component::get_core_subsystems();
        ksort($subsystems);
        $subsystems = array_merge(
            ['core' => $CFG->libdir],
            $subsystems
        );

        foreach ($subsystems as $subsystem => $directory) {
            if (!file_exists("$directory/tests/")) {
                continue;
            }
            $dir = "$directory/tests/";

            $suite = str_replace('@component@', $subsystem, $template);
            $suite = str_replace('@dir@', $dir, $suite);

            $suites .= $suite;
        }
        $data = preg_replace('|<!--@subsystem_suites_start@-->.*<!--@subsystem_suites_end@-->|s', $suites, $data, 1);

        $suites = '';

        $plugintypes = \core_component::get_plugin_types();
        ksort($plugintypes);
        foreach ($plugintypes as $type=>$unused) {
            $plugs = \core_component::get_plugin_list($type);
            ksort($plugs);
            foreach ($plugs as $plug=>$fullplug) {
                if (!file_exists("$fullplug/tests/")) {
                    continue;
                }
                $dir = "$fullplug/tests/";
                $component = $type.'_'.$plug;

                $suite = str_replace('@component@', $component, $template);
                $suite = str_replace('@dir@', $dir, $suite);

                $suites .= $suite;
            }
        }

        $data = preg_replace('|<!--@plugin_suites_start@-->.*<!--@plugin_suites_end@-->|s', $suites, $data, 1);

        if (is_writable(dirname($path_xml))) {
            $result = file_put_contents($path_xml, $data);
            if ($result) {
                testing_fix_file_permissions($path_xml);
            } else {
                phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, "Unable to write phpunit.xml file");
            }
        } else {
            phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, "Unable to write phpunit.xml file, srcroot directory is read only");
        }

        return (bool)$result;
    }

    /**
     * Builds phpunit.xml files for all components using defaults from /phpunit.xml.dist
     *
     * @static
     * @return void, stops if can not write files
     */
    public static function build_component_config_files() {
        global $CFG;
        require_once(__DIR__ . '/../../testing/classes/tests_finder.php');

        $template = '
        <testsuites>
            <testsuite name="@component@_testsuite">
                <directory suffix="_test.php">.</directory>
            </testsuite>
        </testsuites>
        <filter>
            <whitelist processUncoveredFilesFromWhitelist="false">
                <directory suffix=".php">.</directory>
                <exclude>
                    <directory suffix="_test.php">.</directory>
                </exclude>
            </whitelist>
        </filter>';

        // Start a sequence between 100000 and 199000 to ensure each call to init produces
        // different ids in the database.  This reduces the risk that hard coded values will
        // end up being placed in phpunit or behat test code.
        $sequencestart = 100000 + mt_rand(0, 99) * 1000;

        // Use the upstream file as source for the distributed configurations
        $ftemplate = file_get_contents("$CFG->srcroot/phpunit.xml.dist");
        $ftemplate = preg_replace('|<!--All core suites.*</testsuites>|s', '<!--@component_suite@-->', $ftemplate);

        // Gets all the components with tests
        $components = \tests_finder::get_components_with_tests('phpunit');

        // Create the corresponding phpunit.xml file for each component
        foreach ($components as $cname => $cpath) {
            // Calculate the component suite
            $ctemplate = $template;
            $ctemplate = str_replace('@component@', $cname, $ctemplate);

            // Apply it to the file template
            $fcontents = str_replace('<!--@component_suite@-->', $ctemplate, $ftemplate);
            $fcontents = str_replace(
                '<const name="PHPUNIT_SEQUENCE_START" value=""/>',
                '<const name="PHPUNIT_SEQUENCE_START" value="' . $sequencestart . '"/>',
                $fcontents);

            // fix link to schema
            $level = substr_count(str_replace('\\', '/', $cpath), '/') - substr_count(str_replace('\\', '/', $CFG->srcroot), '/');
            $fcontents = str_replace('lib/phpunit/', str_repeat('../', $level).'lib/phpunit/', $fcontents);

            // Write the file
            $result = false;
            if (is_writable($cpath)) {
                if ($result = (bool)file_put_contents("$cpath/phpunit.xml", $fcontents)) {
                    testing_fix_file_permissions("$cpath/phpunit.xml");
                }
            }
            // Problems writing file, throw error
            if (!$result) {
                phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGWARNING, "Can not create $cpath/phpunit.xml configuration file, verify dir permissions");
            }
        }
    }

    /**
     * To be called from debugging() only.
     * @param string $message
     * @param int $level
     * @param string $from
     */
    public static function debugging_triggered($message, $level, $from) {
        // Store only if debugging triggered from actual test,
        // we need normal debugging outside of tests to find problems in our phpunit integration.
        $backtrace = debug_backtrace();

        foreach ($backtrace as $bt) {
            if (isset($bt['object']) and is_object($bt['object'])
                    && $bt['object'] instanceof \PHPUnit\Framework\TestCase) {
                $debug = new \stdClass();
                $debug->message = $message;
                $debug->level   = $level;
                $debug->from    = $from;

                self::$debuggings[] = $debug;

                return true;
            }
        }
        return false;
    }

    /**
     * Resets the list of debugging messages.
     */
    public static function reset_debugging() {
        self::$debuggings = array();
        set_debugging(DEBUG_DEVELOPER);
    }

    /**
     * Returns all debugging messages triggered during test.
     * @return array with instances having message, level and stacktrace property.
     */
    public static function get_debugging_messages() {
        return self::$debuggings;
    }

    /**
     * Prints out any debug messages accumulated during test execution.
     *
     * @param bool $return true to return the messages or false to print them directly. Default false.
     * @return bool|string false if no debug messages, true if debug triggered or string of messages
     */
    public static function display_debugging_messages($return = false) {
        if (empty(self::$debuggings)) {
            return false;
        }

        $debugstring = '';
        foreach(self::$debuggings as $debug) {
            $debugstring .= 'Debugging: ' . $debug->message . "\n" . trim($debug->from) . "\n";
        }

        if ($return) {
            return $debugstring;
        }
        echo $debugstring;
        return true;
    }

    /**
     * Start message redirection.
     *
     * Note: Do not call directly from tests,
     *       use $sink = $this->redirectMessages() instead.
     *
     * @return message_sink
     */
    public static function start_message_redirection() {
        if (self::$messagesink) {
            self::stop_message_redirection();
        }
        self::$messagesink = new message_sink();
        return self::$messagesink;
    }

    /**
     * End message redirection.
     *
     * Note: Do not call directly from tests,
     *       use $sink->close() instead.
     */
    public static function stop_message_redirection() {
        self::$messagesink = null;
    }

    /**
     * Are messages redirected to some sink?
     *
     * Note: to be called from messagelib.php only!
     *
     * @return bool
     */
    public static function is_redirecting_messages() {
        return !empty(self::$messagesink);
    }

    /**
     * To be called from messagelib.php only!
     *
     * @param \stdClass $message record from messages table
     */
    public static function message_sent($message) {
        if (self::$messagesink) {
            self::$messagesink->add_message($message);
        }
    }

    /**
     * Start phpmailer redirection.
     *
     * Note: Do not call directly from tests,
     *       use $sink = $this->redirectEmails() instead.
     *
     * @return phpmailer_sink
     */
    public static function start_phpmailer_redirection() {
        if (self::$phpmailersink) {
            // If an existing mailer sink is active, just clear it.
            self::$phpmailersink->clear();
        } else {
            self::$phpmailersink = new phpmailer_sink();
        }
        return self::$phpmailersink;
    }

    /**
     * End phpmailer redirection.
     *
     * Note: Do not call directly from tests,
     *       use $sink->close() instead.
     */
    public static function stop_phpmailer_redirection() {
        self::$phpmailersink = null;
    }

    /**
     * Are messages for phpmailer redirected to some sink?
     *
     * Note: to be called from moodle_phpmailer.php only!
     *
     * @return bool
     */
    public static function is_redirecting_phpmailer() {
        return !empty(self::$phpmailersink);
    }

    /**
     * To be called from messagelib.php only!
     *
     * @param \stdClass $message record from messages table
     */
    public static function phpmailer_sent($message) {
        if (self::$phpmailersink) {
            self::$phpmailersink->add_message($message);
        }
    }

    /**
     * Start event redirection.
     *
     * @private
     * Note: Do not call directly from tests,
     *       use $sink = $this->redirectEvents() instead.
     *
     * @return event_sink
     */
    public static function start_event_redirection() {
        if (self::$eventsink) {
            self::stop_event_redirection();
        }
        self::$eventsink = new event_sink();
        return self::$eventsink;
    }

    /**
     * End event redirection.
     *
     * @private
     * Note: Do not call directly from tests,
     *       use $sink->close() instead.
     */
    public static function stop_event_redirection() {
        self::$eventsink = null;
    }

    /**
     * Are events redirected to some sink?
     *
     * Note: to be called from \core\event\base only!
     *
     * @private
     * @return bool
     */
    public static function is_redirecting_events() {
        return !empty(self::$eventsink);
    }

    /**
     * To be called from \core\event\base only!
     *
     * @private
     * @param \core\event\base $event record from event_read table
     */
    public static function event_triggered(\core\event\base $event) {
        if (self::$eventsink) {
            self::$eventsink->add_event($event);
        }
    }

    /**
     * Start hook redirection.
     *
     * @private
     * Note: Do not call directly from tests,
     *       use $sink = $this->redirectHooks() instead.
     *
     * @return hook_sink
     */
    public static function start_hook_redirection() {
        if (self::$hooksink) {
            self::stop_hook_redirection();
        }
        self::$hooksink = new hook_sink();
        return self::$hooksink;
    }

    /**
     * End event redirection.
     *
     * @private
     * Note: Do not call directly from tests,
     *       use $sink->close() instead.
     */
    public static function stop_hook_redirection() {
        self::$hooksink = null;
    }

    /**
     * Are hooks redirected to some sink?
     *
     * Note: to be called from \totara_core\hook\base only!
     *
     * @private
     * @return bool
     */
    public static function is_redirecting_hooks() {
        return !empty(self::$hooksink);
    }

    /**
     * To be called from \totara_core\hook\base only!
     *
     * @private
     * @param \totara_core\hook\base $hook
     */
    public static function hook_executed(\totara_core\hook\base $hook) {
        if (self::$hooksink) {
            self::$hooksink->add_hook($hook);
        }
    }

    /**
     * Gets the name of the locale for testing environment (Australian English)
     * depending on platform environment.
     *
     * @return string the locale name.
     */
    protected static function get_locale_name() {
        global $CFG;
        if ($CFG->ostype === 'WINDOWS') {
            return 'English_Australia.1252';
        } else {
            return 'en_AU.UTF-8';
        }
    }

    /**
     * Helper function to call a protected/private method of an object using reflection.
     *
     * Example 1. Calling a protected object method:
     *   $result = call_internal_method($myobject, 'method_name', [$param1, $param2], '\my\namespace\myobjectclassname');
     *
     * Example 2. Calling a protected static method:
     *   $result = call_internal_method(null, 'method_name', [$param1, $param2], '\my\namespace\myclassname');
     *
     * @param object|null $object the object on which to call the method, or null if calling a static method.
     * @param string $methodname the name of the protected/private method.
     * @param array $params the array of function params to pass to the method.
     * @param string $classname the fully namespaced name of the class the object was created from (base in the case of mocks),
     *        or the name of the static class when calling a static method.
     * @return mixed the respective return value of the method.
     */
    public static function call_internal_method($object, $methodname, array $params, $classname) {
        $reflection = new \ReflectionClass($classname);
        $method = $reflection->getMethod($methodname);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $params);
    }

    /**
     * @param string|null $origin
     *
     * @return void
     */
    public static function set_request_origin(?string $origin): void {
        self::$request_origin = $origin;
    }

    /**
     * @return string|null
     */
    public static function get_request_origin(): ?string {
        return self::$request_origin;
    }

    /**
     * @return bool
     */
    public static function is_external_api(): bool {
        return self::$request_origin === 'EXTERNAL_API';
    }

}
