<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package core_phpunit
 */

namespace core_phpunit;

use DOMNode, DOMDocument, DOMNodeList;
use Throwable;
use coding_exception;

/**
 * PHPUnit test case customised for Totara.
 */
abstract class testcase extends \PHPUnit\Framework\TestCase {
    // @codingStandardsIgnoreStart

    /** @var float tracks the total time waiting for the next second */
    private static $totalwaitforsecond;

    /** @var int last start of class testing */
    private static $lastclassstarttime = null;

    /** @var int timestamp used for current time asserts */
    private static $currenttimestart;

    /**
     * Constructs a test case with the given name.
     *
     * Note: use setUp() or setUpBeforeClass() in your test cases.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    final public function __construct($name = null, array $data = array(), $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->setBackupGlobals(false);
        $this->setBackupStaticAttributes(false);
        $this->setRunTestInSeparateProcess(false);
    }


    /**
     * @internal
     * @return string
     */
    private static function get_profiling_filepath_method() {
        global $DB;
        return __DIR__ . '/../../../../phpunit_profile_methods_' . $DB->get_dbfamily() . '.csv';
    }

    /**
     * @internal
     * @return string
     */
    private static function get_profiling_filepath_class() {
        global $DB;
        return __DIR__ . '/../../../../phpunit_profile_classes_' . $DB->get_dbfamily() . '.csv';
    }

    /**
     * Runs the bare test sequence.
     * @return void
     */
    final public function runBare(): void {
        global $DB;

        try {
            static::setCurrentTimeStart();

            if (defined('PHPUNIT_PROFILING')) {
                static $fp = null;
                if ($fp === null) {
                    $filepath = self::get_profiling_filepath_method();
                    @unlink($filepath);
                    $fp = fopen($filepath, 'w+');
                    // The file handle will get closed automatically at the end when phpunit terminates.
                    fputcsv($fp, array('execution time', 'waiting time', 'memory increase', 'method', 'class'));
                }

                $startmemory = memory_get_usage();

                self::$totalwaitforsecond = 0;
                $timestart = microtime(true);
                parent::runBare();
                $totaltime = microtime(true) - $timestart;

                $name = $this->getName(true);
                $classname = get_class($this);
                $memdiff = memory_get_usage() - $startmemory;

                fputcsv($fp, array(number_format($totaltime, 2), number_format(self::$totalwaitforsecond, 2), $memdiff, $name, $classname));
            } else {
                parent::runBare();
            }

            // set DB reference in case somebody mocked it in test
            $DB = internal_util::get_global_backup('DB');

            // Deal with any debugging messages.
            $debugerror = internal_util::display_debugging_messages(true);
            self::resetDebugging();
            if (!empty($debugerror)) {
                trigger_error('Unexpected debugging() call detected.'."\n".$debugerror, E_USER_NOTICE);
            }

        } catch (Throwable $ex) {
            $e = $ex;
        }

        if (isset($e)) {
            // cleanup after failed expectation
            self::resetAllData();

            if (TOTARA_DISTRIBUTION_TEST && is_a($e, \PHPUnit\Framework\Warning::class)) {
                // Totara: make all the warning(s) fail, instead of keeping the warning. This will
                // enforce the developer to upgrade their API usage.
                throw new coding_exception("Test {$this->getName()} has warning: {$e->getMessage()}");
            }

            throw $e;
        }

        self::resetAllData();

        if (!defined('PHPUNIT_DISABLE_UNRESET_PROPERTIES_CHECK') || !PHPUNIT_DISABLE_UNRESET_PROPERTIES_CHECK) {
            // Check for properties which are not reset on tearDown.
            $this->checkForUnresetProperties();
        }
    }


    /**
     * Creates a new data set with the given $xmlFile. (absolute path.)
     *
     * @param string $xmlFile
     * @return ArrayDataSet
     */
    final protected static function createFlatXMLDataSet($xmlFile) {
        return ArrayDataSet::createFromFlatXML($xmlFile);
    }

    /**
     * Creates a new data set with the given $xmlFile. (absolute path.)
     *
     * @param string $xmlFile
     * @return ArrayDataSet
     */
    final protected static function createXMLDataSet($xmlFile) {
        return ArrayDataSet::createFromXML($xmlFile);
    }

    /**
     * Creates a new data set from the given array of csv files. (absolute paths.)
     *
     * @param array $files array tablename=>cvsfile
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @return ArrayDataSet
     */
    final protected static function createCsvDataSet($files, $delimiter = ',', $enclosure = '"', $escape = '"') {
        return ArrayDataSet::createFromCSV($files, $delimiter, $enclosure, $escape);
    }

    /**
     * Creates new ArrayDataSet from given array
     *
     * @param array $data array of tables, first row in each table is columns
     * @return ArrayDataSet
     */
    final protected static function createArrayDataSet(array $data) {
        return new ArrayDataSet($data);
    }

    /**
     * Load date into moodle database tables from data set.
     *
     * Note: it is usually better to use data generators
     *
     * @param ArrayDataSet
     * @return void
     */
    final protected static function loadDataSet(ArrayDataSet $dataset) {
        global $DB;

        foreach ($dataset as $tablename => $rows) {
            $imported = false;
            foreach ($rows as $record) {
                $record = (object)$record;
                if (isset($record->id)) {
                    $DB->import_record($tablename, $record);
                    $imported = true;
                } else {
                    if ($imported) {
                        $DB->get_manager()->reset_sequence(new \xmldb_table($tablename));
                        $imported = false;
                    }
                    $DB->insert_record($tablename, $record);
                }
            }
            if ($imported) {
                $DB->get_manager()->reset_sequence(new \xmldb_table($tablename));
            }
        }
    }

    /**
     * Do not call this method any more, transactions are not
     * used for test environment rollback any more.
     *
     * @deprecated since Totara 10
     *
     * @return void
     */
    public function preventResetByRollback() {
    }

    /**
     * Totara: Do not use, advanced testcase always resets state after each test,
     * this is required for parallel test execution. Also tests should not be
     * used as data provider because they would be executed repeatedly.
     *
     * @deprecated since Totara 13, 12.8, 11.17
     *
     * @param bool $reset
     * @return void
     */
    public function resetAfterTest($reset = true) {
        if (!$reset) {
            debugging('Do not use resetAfterTest(false) any more, reset is mandatory after every test now', DEBUG_DEVELOPER);
        }
    }

    /**
     * Return debugging messages from the current test.
     * @return array with instances having 'message', 'level' and 'stacktrace' property.
     */
    final public static function getDebuggingMessages() {
        return internal_util::get_debugging_messages();
    }

    /**
     * Clear all previous debugging messages in current test
     * and revert to default DEVELOPER_DEBUG level.
     */
    final public static function resetDebugging() {
        internal_util::reset_debugging();
    }

    /**
     * Assert that exactly debugging was just called once.
     *
     * Discards the debugging message if successful.
     *
     * @param null|string|array $debugmessages null means any
     * @param null|string $debuglevel null means any
     * @param string $message
     */
    public static function assertDebuggingCalled($debugmessages = null, $debuglevel = null, $message = '') {
        $debugging = static::getDebuggingMessages();
        $debugdisplaymessage = "\n".internal_util::display_debugging_messages(true);
        static::resetDebugging();

        $expectedmessages = $debugmessages;
        if (!is_array($expectedmessages)) {
            $expectedmessages = [$expectedmessages];
        }
        $expectedcount = count($expectedmessages);
        $count = count($debugging);

        if ($count === 0) {
            if ($message === '') {
                $message = 'Expectation failed, debugging() not triggered.';
            }
            static::fail($message);
        }
        if ($count !== $expectedcount) {
            if ($message === '') {
                $message = 'Expectation failed, debugging() triggered '.$count.' times, expected '.$expectedcount.' times.'.$debugdisplaymessage;
            }
            static::fail($message);
        }
        static::assertEquals($expectedcount, $count);

        if ($debugmessages !== null) {
            // Compare messages.
            $actual = [];
            foreach ($debugging as $debug) {
                $actual[] = $debug->message;
            }

            static::assertEquals($expectedmessages, $actual, $message . $debugdisplaymessage);
        }

        if ($debuglevel !== null) {
            foreach ($debugging as $debug) {
                static::assertSame($debuglevel, $debug->level, $message);
            }
        }
    }

    /**
     * Asserts how many times debugging has been called.
     *
     * @param int $expectedcount The expected number of times
     * @param array $debugmessages Expected debugging messages, one for each expected message.
     * @param array $debuglevels Expected debugging levels, one for each expected message.
     * @param string $message
     * @return void
     */
    public static function assertDebuggingCalledCount($expectedcount, $debugmessages = array(), $debuglevels = array(), $message = '') {
        if (!is_int($expectedcount)) {
            throw new coding_exception('assertDebuggingCalledCount $expectedcount argument should be an integer.');
        }

        $debugging = static::getDebuggingMessages();
        $message .= "\n".internal_util::display_debugging_messages(true);
        static::resetDebugging();

        static::assertEquals($expectedcount, count($debugging), $message);

        if ($debugmessages) {
            if (!is_array($debugmessages) || count($debugmessages) != $expectedcount) {
                throw new coding_exception('assertDebuggingCalledCount $debugmessages should contain ' . $expectedcount . ' messages');
            }
            foreach ($debugmessages as $key => $debugmessage) {
                static::assertSame($debugmessage, $debugging[$key]->message, $message);
            }
        }

        if ($debuglevels) {
            if (!is_array($debuglevels) || count($debuglevels) != $expectedcount) {
                throw new coding_exception('assertDebuggingCalledCount $debuglevels should contain ' . $expectedcount . ' messages');
            }
            foreach ($debuglevels as $key => $debuglevel) {
                static::assertSame($debuglevel, $debugging[$key]->level, $message);
            }
        }
    }

    /**
     * Call when no debugging() messages expected.
     * @param string $message
     */
    public static function assertDebuggingNotCalled($message = '') {
        $debugging = static::getDebuggingMessages();
        $count = count($debugging);

        if ($message === '') {
            $message = 'Expectation failed, debugging() was triggered.';
        }
        $message .= "\n".internal_util::display_debugging_messages(true);
        static::resetDebugging();
        static::assertEquals(0, $count, $message);
    }

    /**
     * Call when debugging() messages may be expected.
     *
     * @param string|array $debugmessages
     * @param string $message
     * @param string $expectedmessage
     * @return boolean true if debugging() expected.
     */
    public static function assertDebuggingMayBeCalled($debugmessages, $message = '', $expectedmessage = '') {
        $debugging = static::getDebuggingMessages();
        $debugdisplaymessage = "\n".internal_util::display_debugging_messages(true);
        static::resetDebugging();

        if (!is_array($debugmessages)) {
            $debugmessages = [$debugmessages];
        }
        if (count($debugging) === 0) {
            return false;
        }

        // Remove expected messages.
        $debuggingcalled = false;
        foreach ($debugging as $i => $debug) {
            foreach ($debugmessages as $expected) {
                if (preg_match($expected, $debug->message)) {
                    $debuggingcalled = true;
                    unset($debugging[$i]);
                    continue 2;
                }
            }
        }
        if ($message === '') {
            $message = 'Expectation failed, debugging() was triggered.';
        }
        static::resetDebugging();
        static::assertEquals(0, count($debugging), $message . $debugdisplaymessage);

        return $debuggingcalled;
    }

    /**
     * Assert that an event legacy data is equal to the expected value.
     *
     * @param mixed $expected expected data.
     * @param \core\event\base $event the event object.
     * @param string $message
     * @return void
     */
    public static function assertEventLegacyData($expected, \core\event\base $event, $message = '') {
        $legacydata = self::callInternalMethod($event, 'get_legacy_eventdata', []);
        if ($message === '') {
            $message = 'Event legacy data does not match expected value.';
        }
        static::assertEquals($expected, $legacydata, $message);
    }

    /**
     * Assert that an event legacy log data is equal to the expected value.
     *
     * @param mixed $expected expected data.
     * @param \core\event\base $event the event object.
     * @param string $message
     * @return void
     */
    public static function assertEventLegacyLogData($expected, \core\event\base $event, $message = '') {
        $legacydata = self::callInternalMethod($event, 'get_legacy_logdata', []);
        if ($message === '') {
            $message = 'Event legacy log data does not match expected value.';
        }
        static::assertEquals($expected, $legacydata, $message);
    }

    /**
     * Assert that an event is not using event->context.
     * While restoring context might not be valid and it should not be used by event url
     * or description methods.
     *
     * @param \core\event\base $event the event object.
     * @param string $message
     * @return void
     */
    public static function assertEventContextNotUsed(\core\event\base $event, $message = '') {
        // Save current event->context and set it to false.
        $reflection = new \ReflectionClass(get_class($event));
        $property = $reflection->getProperty('context');
        $property->setAccessible(true);
        $eventcontext = $property->getValue($event);
        $property->setValue($event, false);

        // Test event methods should not use event->context.
        $event->get_url();
        $desc = $event->get_description();
        $len = $event->get_legacy_eventname();
        self::callInternalMethod($event, 'get_legacy_eventdata', []);
        self::callInternalMethod($event, 'get_legacy_logdata', []);

        // Restore event->context.
        $property->setValue($event, $eventcontext);
    }

    /**
     * Stores current time as the base for assertTimeCurrent().
     *
     * Note: this is called automatically before calling individual test methods.
     *
     * @param int|null $start_time defaults to time() if not supplied
     * @return int current time
     */
    public static function setCurrentTimeStart(?int $start_time = null) {
        self::$currenttimestart = $start_time ?? time();
        return self::$currenttimestart;
    }

    /**
     * Assert that: start < $time < time()
     * @param int $time
     * @param string $message
     * @return void
     */
    public static function assertTimeCurrent($time, $message = '') {
        $msg =  ($message === '') ? 'Time is lower that allowed start value' : $message;
        static::assertGreaterThanOrEqual(self::$currenttimestart, $time, $msg);
        $msg =  ($message === '') ? 'Time is in the future' : $message;
        static::assertLessThanOrEqual(time(), $time, $msg);
    }

    /**
     * Starts message redirection.
     *
     * You can verify if messages were sent or not by inspecting the messages
     * array in the returned messaging sink instance. The redirection
     * can be stopped by calling $sink->close();
     *
     * @return message_sink
     */
    final public static function redirectMessages() {
        return internal_util::start_message_redirection();
    }

    /**
     * Starts email redirection.
     *
     * You can verify if email were sent or not by inspecting the email
     * array in the returned phpmailer sink instance. The redirection
     * can be stopped by calling $sink->close();
     *
     * @return phpmailer_sink
     */
    final public static function redirectEmails() {
        return internal_util::start_phpmailer_redirection();
    }

    /**
     * Starts event redirection.
     *
     * You can verify if events were triggered or not by inspecting the events
     * array in the returned event sink instance. The redirection
     * can be stopped by calling $sink->close();
     *
     * @return event_sink
     */
    final public static function redirectEvents() {
        return internal_util::start_event_redirection();
    }

    /**
     * Starts hook redirection.
     *
     * You can verify if hooks were executed or not by inspecting the hooks
     * array in the returned hook sink instance. The redirection
     * can be stopped by calling $sink->close();
     *
     * @return hook_sink
     */
    final public static function redirectHooks() {
        return internal_util::start_hook_redirection();
    }

    /**
     * Cleanup after all tests are executed.
     *
     * Note: do not forget to call this if overridden...
     *
     * @static
     * @return void
     */
    public static function tearDownAfterClass(): void {
        if (defined('PHPUNIT_PROFILING')) {
            if (self::$lastclassstarttime === null) {
                @unlink(self::get_profiling_filepath_class());
                // Static variables do not work here, let's use static property instead.
                self::$lastclassstarttime = filectime(self::get_profiling_filepath_method());
            }

            $totaltime = microtime(true) - self::$lastclassstarttime;
            $classname = get_called_class();

            $fp = fopen(self::get_profiling_filepath_class(), 'a+');
            fputcsv($fp, array(number_format($totaltime, 2), $classname));
            fclose($fp);

            self::$lastclassstarttime = microtime(true);
        }
        parent::tearDownAfterClass();
    }


    /**
     * Reset all database tables, restore global state and clear caches and optionally purge dataroot dir.
     *
     * @param bool $detectchanges
     *      true  - changes in global state and database are reported as errors
     *      false - no errors reported
     *      null  - only critical problems are reported as errors
     * @return void
     */
    final public static function resetAllData($detectchanges = false) {
        internal_util::reset_all_data($detectchanges);
    }

    /**
     * Check for properties which are not reset on tearDown.
     *
     * @return void
     */
    final public function checkForUnresetProperties() {
        $reflectionclass = new \ReflectionClass($this);
        $defaultproperties = $reflectionclass->getDefaultProperties();
        foreach ($reflectionclass->getProperties() as $property) {
            if ($property->isStatic()
                || $property->getDeclaringClass()->getName() != get_class($this)
            ) {
                continue;
            }
            $property->setAccessible(true);
            // If property was defined with a value and value did not change don't complain.
            if (isset($defaultproperties[$property->getName()])
                && $defaultproperties[$property->getName()] == $property->getValue($this)){
                continue;
            }
            // Otherwise if property was not set to null fail the build
            if ($property->getValue($this) !== null) {
                $message = sprintf("Property '%s' defined in '%s' was not reset after the test!\n".
                    "Please either find a way to avoid using a class variable or make sure it get's unset ".
                    "in the tearDown method to avoid creating memory leaks.", $property->getName(), get_class($this));
                static::fail($message);
            }
        }
    }

    /**
     * Overrides one lang string in current language.
     *
     * @since Totara 12
     *
     * @param string $string
     * @param string $component
     * @param string value
     * @param boolean $acceptnonexistentstring
     */
    public static function overrideLangString($string, $component, $value, $acceptnonexistentstring = false) {
        $lang = current_language(); // No point specifying lang in tests, we only have the 'en' here.
        $sm = get_string_manager();

        $rc = new \ReflectionClass('core_string_manager_standard');
        $get_key_suffix = $rc->getMethod('get_key_suffix');
        $get_key_suffix->setAccessible(true);
        $rccache = $rc->getProperty('cache');
        $rccache->setAccessible(true);

        // Normalise data the same way as \core_string_manager_standard::load_component_strings().
        [$plugintype, $pluginname] = \core_component::normalize_component($component);
        if ($plugintype === 'core' and is_null($pluginname)) {
            $component = 'core';
        } else {
            $component = $plugintype . '_' . $pluginname;
        }

        if (!$acceptnonexistentstring && !$sm->string_exists($string, $component)) {
            throw new coding_exception('Cannot override non-existent string');
        }

        // Override the cache, that should be enough to fool regular code.
        $strings = $sm->load_component_strings($component, $lang);
        $strings[$string] = $value;

        $cachekey = $lang.'_'.$component.'_'.$get_key_suffix->invokeArgs($sm, array());

        $cache = $rccache->getValue($sm);
        $cache->set($cachekey, $strings);
    }

    /**
     * Set current $USER, reset access cache.
     * @static
     * @param null|int|\stdClass $user user record, null or 0 means non-logged-in, positive integer means userid
     * @return void
     */
    final public static function setUser($user = null) {
        global $CFG, $DB;

        if ($user instanceof \core\entity\user) {
            $user = $user->to_record();
        } else if (is_object($user)) {
            $user = clone($user);
        } else if (!$user) {
            $user = new \stdClass();
            $user->id = 0;
        } else {
            $user = $DB->get_record('user', array('id'=>$user));
        }
        unset($user->description);
        unset($user->access);
        unset($user->preference);

        // Enusre session is empty, as it may contain caches and user specific info.
        \core\session\manager::init_empty_session();

        \core\session\manager::set_user($user);
    }

    /**
     * Set current $USER to admin account, reset access cache.
     * @static
     * @return void
     */
    final public static function setAdminUser() {
        self::setUser(2);
    }

    /**
     * Set current $USER to guest account, reset access cache.
     * @static
     * @return void
     */
    final public static function setGuestUser() {
        self::setUser(1);
    }

    /**
     * Change server and default php timezones.
     *
     * @param string $servertimezone timezone to set in $CFG->timezone (not validated)
     * @param string $defaultphptimezone timezone to fake default php timezone (must be valid)
     */
    public static function setTimezone($servertimezone = 'Australia/Perth', $defaultphptimezone = 'Australia/Perth') {
        global $CFG;
        $CFG->timezone = $servertimezone;
        \core_date::phpunit_override_default_php_timezone($defaultphptimezone);
        \core_date::set_default_server_timezone();
    }

    /**
     * Get data generator
     * @return \core\testing\generator;
     */
    public static function getDataGenerator() {
        return \core\testing\generator::instance();
    }

    /**
     * Returns UTL of the external test file.
     *
     * The result depends on the value of following constants:
     *  - TEST_EXTERNAL_FILES_HTTP_URL
     *  - TEST_EXTERNAL_FILES_HTTPS_URL
     *
     * They should point to standard external test files repository,
     * it defaults to 'http://test.totaralms.com/exttests'.
     *
     * False value means skip tests that require external files.
     *
     * @param string $path
     * @param bool $https true if https required
     * @param bool $include_port true if ports are required in the url
     * @return string url
     */
    public static function getExternalTestFileUrl($path, $https = false, bool $include_port = false) {
        $path = ltrim($path, '/');
        if ($path) {
            $path = '/'.$path;
        }
        if ($https) {
            if (defined('TEST_EXTERNAL_FILES_HTTPS_URL')) {
                if (!TEST_EXTERNAL_FILES_HTTPS_URL) {
                    static::markTestSkipped('Tests using external https test files are disabled');
                }
                return TEST_EXTERNAL_FILES_HTTPS_URL.$path;
            }
            $port = $include_port ? ':443' : '';
            return 'https://test.totaralms.com'.$port.'/exttests'.$path;
        }

        if (defined('TEST_EXTERNAL_FILES_HTTP_URL')) {
            if (!TEST_EXTERNAL_FILES_HTTP_URL) {
                static::markTestSkipped('Tests using external http test files are disabled');
            }
            return TEST_EXTERNAL_FILES_HTTP_URL.$path;
        }
        $port = $include_port ? ':80' : '';
        return 'http://test.totaralms.com'.$port.'/exttests'.$path;
    }

    /**
     * Recursively visit all the files in the source tree. Calls the callback
     * function with the pathname of each file found.
     *
     * @param string $path the folder to start searching from.
     * @param string $callback the method of this class to call with the name of each file found.
     * @param string $fileregexp a regexp used to filter the search (optional).
     * @param bool $exclude If true, pathnames that match the regexp will be ignored. If false,
     *     only files that match the regexp will be included. (default false).
     * @param array $ignorefolders will not go into any of these folders (optional).
     * @return void
     */
    public function recurseFolders($path, $callback, $fileregexp = '/.*/', $exclude = false, $ignorefolders = array()) {
        $files = scandir($path);

        foreach ($files as $file) {
            $filepath = $path .'/'. $file;
            if (strpos($file, '.') === 0) {
                /// Don't check hidden files.
                continue;
            } else if (is_dir($filepath)) {
                if (!in_array($filepath, $ignorefolders)) {
                    $this->recurseFolders($filepath, $callback, $fileregexp, $exclude, $ignorefolders);
                }
            } else if ($exclude xor preg_match($fileregexp, $filepath)) {
                $this->$callback($filepath);
            }
        }
    }

    /**
     * Wait for a second to roll over, ensures future calls to time() return a different result.
     *
     * This is implemented instead of sleep() as we do not need to wait a full second. In some cases
     * due to calls we may wait more than sleep() would have, on average it will be less.
     */
    public static function waitForSecond() {
        $microstart = microtime(true);
        $start = time();
        while (time() == $start) {
            // The while loop is necessary because the sleeping may get interrupted.
            @time_sleep_until($start + 1);
        }

        self::$totalwaitforsecond += (microtime(true) - $microstart);
    }

    /**
     * Execute all adhoc tasks in queue
     */
    final public static function executeAdhocTasks() {
        $now = time();
        while (($task = \core\task\manager::get_next_adhoc_task($now)) !== null) {
            try {
                $task->execute();
                \core\task\manager::adhoc_task_complete($task);
            } catch (\Exception $e) {
                \core\task\manager::adhoc_task_failed($task);
            }
        }
    }

    /**
     * @deprecated since Totara 13.5
     */
    public function execute_adhoc_tasks() {
        // The rule is to use camel case here.
        self::executeAdhocTasks();
    }

    /**
     * Skip test unless TOTARA_DISTRIBUTION_TEST is enabled in config.php
     */
    public static function markTestSkippedIfNotTotaraDistribution() {
        if (!TOTARA_DISTRIBUTION_TEST) {
            static::markTestSkipped('Totara distribution test skipped');
        }
    }

    /**
     * Helper function to call a protected/private method of an object using reflection.
     *
     * Example 1. Calling a protected object method:
     *   $result = $this->call_internal_method($myobject, 'method_name', [$param1, $param2]);
     *
     * Example 2. Calling a protected static method:
     *   $result = $this->call_internal_method('my_plugin\namespace\myclassname', 'method_name', [$param1, $param2]);
     *
     * @param object|string $object_or_classname the object on which to call the method, or name of class if calling a static method.
     * @param string $methodname the name of the protected/private method.
     * @param array $params the array of function params to pass to the method.
     * @return mixed the respective return value of the method.
     */
    public static function callInternalMethod($object_or_classname, string $methodname, array $params) {
        if (is_object($object_or_classname)) {
            return internal_util::call_internal_method($object_or_classname, $methodname, $params, get_class($object_or_classname));
        } else {
            return internal_util::call_internal_method(null, $methodname, $params, (string)$object_or_classname);
        }
    }

    /**
     * Validate list of keys in the associative array.
     *
     * @param array $hash
     * @param array $validKeys
     *
     * @return array
     *
     * @throws \PHPUnit\Framework\Exception
     */
    public static function assertValidKeys(array $hash, array $validKeys) {
        $valids = array();

        // Normalize validation keys so that we can use both indexed and
        // associative arrays.
        foreach ($validKeys as $key => $val) {
            is_int($key) ? $valids[$val] = null : $valids[$key] = $val;
        }

        $validKeys = array_keys($valids);

        // Check for invalid keys.
        foreach ($hash as $key => $value) {
            if (!in_array($key, $validKeys)) {
                $unknown[] = $key;
            }
        }

        if (!empty($unknown)) {
            throw new \PHPUnit\Framework\Exception(
                'Unknown key(s): ' . implode(', ', $unknown)
            );
        }

        // Add default values for any valid keys that are empty.
        foreach ($valids as $key => $value) {
            if (!isset($hash[$key])) {
                $hash[$key] = $value;
            }
        }

        return $hash;
    }

    /**
     * Asserts that two file paths are identical.
     *
     * @param string|\totara_core\path $expected
     * @param string|\totara_core\path $actual
     * @param string $message
     * @return void
     */
    public static function assertSamePath($expected, $actual, string $message = ''): void {
        global $CFG;
        require_once($CFG->dirroot . '/totara/core/classes/path.php');
        $check = function ($index, $param, $message) {
            if (!(is_string($param) || $param instanceof \totara_core\path)) {
                if ($message !== '') {
                    $message .= "\n";
                }
                $message = "Argument {$index} must be string or path.";
                static::fail($message);
            }
        };
        $check(1, $expected, $message);
        $check(2, $actual, $message);
        static::assertThat($actual, new class($expected) extends \PHPUnit\Framework\Constraint\Constraint {
            /** @var \totara_core\path */
            private $path;

            public function __construct($path) {
                $this->path = new \totara_core\path($path);
            }

            /**
             * {@inheritdoc}
             */
            public function toString(): string {
                return "is identical to '{$this->path->out()}'";
            }

            /**
             * {@inheritdoc}
             */
            public function evaluate($other, string $description = '', bool $returnResult = false): ?bool {
                $success = $this->path->equals($other);
                if ($returnResult) {
                    return $success;
                }
                if (!$success) {
                    $path = $this->path->out(true);
                    if ($other instanceof \totara_core\path) {
                        $other = $other->out(true);
                    } else {
                        $other = str_replace('/', \totara_core\path::SEPARATOR, $other);
                    }
                    $failure = new \SebastianBergmann\Comparator\ComparisonFailure($path, $other, "'{$path}'", "'{$other}'");
                    $this->fail($other, $description, $failure);
                }
                return null;
            }

            /**
             * {@inheritdoc}
             */
            protected function failureDescription($other): string {
                return 'two file paths are identical';
            }
        }, $message);
    }


    // Following code is legacy code from phpunit to support assertTag
    // and assertNotTag.

    /**
     * Note: we are overriding this method to remove the deprecated error
     * @see https://tracker.moodle.org/browse/MDL-47129
     *
     * @param  array   $matcher
     * @param  string  $actual
     * @param  string  $message
     * @param  boolean $ishtml
     *
     * @deprecated 3.0
     */
    public static function assertTag($matcher, $actual, $message = '', $ishtml = true) {
        $loader = new \PHPUnit\Util\Xml\Loader();
        $dom = $loader->load($actual, $ishtml);
        $tags = self::findNodes($dom, $matcher, $ishtml);
        $matched = count($tags) > 0 && $tags[0] instanceof DOMNode;
        self::assertTrue($matched, $message);
    }

    /**
     * Note: we are overriding this method to remove the deprecated error
     * @see https://tracker.moodle.org/browse/MDL-47129
     *
     * @param  array   $matcher
     * @param  string  $actual
     * @param  string  $message
     * @param  boolean $ishtml
     *
     * @deprecated 3.0
     */
    public static function assertNotTag($matcher, $actual, $message = '', $ishtml = true) {
        $loader = new \PHPUnit\Util\Xml\Loader();
        $dom = $loader->load($actual, $ishtml);
        $tags = self::findNodes($dom, $matcher, $ishtml);
        $matched = isset($tags[0]) && $tags[0] instanceof DOMNode; // Totara: count is slow and cannot be used on false!
        self::assertFalse($matched, $message);
    }

    /**
     * @param string|null $origin
     *
     * @return void
     */
    public static function setRequestOrigin(?string $origin): void {
        internal_util::set_request_origin($origin);
    }

    /**
     * Parse out the options from the tag using DOM object tree.
     *
     * @param DOMDocument $dom
     * @param array       $options
     * @param bool        $isHtml
     *
     * @return array
     */
    private static function findNodes(DOMDocument $dom, array $options, $isHtml = true) {
        $valid = array(
            'id', 'class', 'tag', 'content', 'attributes', 'parent',
            'child', 'ancestor', 'descendant', 'children', 'adjacent-sibling'
        );

        $filtered = array();
        $options  = self::assertValidKeys($options, $valid);

        // find the element by id
        if ($options['id']) {
            $options['attributes']['id'] = $options['id'];
        }

        if ($options['class']) {
            $options['attributes']['class'] = $options['class'];
        }

        $nodes = array();

        // find the element by a tag type
        if ($options['tag']) {
            if ($isHtml) {
                $elements = self::getElementsByCaseInsensitiveTagName(
                    $dom,
                    $options['tag']
                );
            } else {
                $elements = $dom->getElementsByTagName($options['tag']);
            }

            foreach ($elements as $element) {
                $nodes[] = $element;
            }

            if (empty($nodes)) {
                return false;
            }
        } // no tag selected, get them all
        else {
            $tags = array(
                'a', 'abbr', 'acronym', 'address', 'area', 'b', 'base', 'bdo',
                'big', 'blockquote', 'body', 'br', 'button', 'caption', 'cite',
                'code', 'col', 'colgroup', 'dd', 'del', 'div', 'dfn', 'dl',
                'dt', 'em', 'fieldset', 'form', 'frame', 'frameset', 'h1', 'h2',
                'h3', 'h4', 'h5', 'h6', 'head', 'hr', 'html', 'i', 'iframe',
                'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li', 'link',
                'map', 'meta', 'noframes', 'noscript', 'object', 'ol', 'optgroup',
                'option', 'p', 'param', 'pre', 'q', 'samp', 'script', 'select',
                'small', 'span', 'strong', 'style', 'sub', 'sup', 'table',
                'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'title',
                'tr', 'tt', 'ul', 'var',
                // HTML5
                'article', 'aside', 'audio', 'bdi', 'canvas', 'command',
                'datalist', 'details', 'dialog', 'embed', 'figure', 'figcaption',
                'footer', 'header', 'hgroup', 'keygen', 'mark', 'meter', 'nav',
                'output', 'progress', 'ruby', 'rt', 'rp', 'track', 'section',
                'source', 'summary', 'time', 'video', 'wbr'
            );

            foreach ($tags as $tag) {
                if ($isHtml) {
                    $elements = self::getElementsByCaseInsensitiveTagName(
                        $dom,
                        $tag
                    );
                } else {
                    $elements = $dom->getElementsByTagName($tag);
                }

                foreach ($elements as $element) {
                    $nodes[] = $element;
                }
            }

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by attributes
        if ($options['attributes']) {
            foreach ($nodes as $node) {
                $invalid = false;

                foreach ($options['attributes'] as $name => $value) {
                    // match by regexp if like "regexp:/foo/i"
                    if (preg_match('/^regexp\s*:\s*(.*)/i', $value, $matches)) {
                        if (!preg_match($matches[1], $node->getAttribute($name))) {
                            $invalid = true;
                        }
                    } // class can match only a part
                    elseif ($name == 'class') {
                        // split to individual classes
                        $findClasses = explode(
                            ' ',
                            preg_replace("/\s+/", ' ', $value)
                        );

                        $allClasses = explode(
                            ' ',
                            preg_replace("/\s+/", ' ', $node->getAttribute($name))
                        );

                        // make sure each class given is in the actual node
                        foreach ($findClasses as $findClass) {
                            if (!in_array($findClass, $allClasses)) {
                                $invalid = true;
                            }
                        }
                    } // match by exact string
                    else {
                        if ($node->getAttribute($name) !== (string) $value) {
                            $invalid = true;
                        }
                    }
                }

                // if every attribute given matched
                if (!$invalid) {
                    $filtered[] = $node;
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by content
        if ($options['content'] !== null) {
            foreach ($nodes as $node) {
                $invalid = false;

                // match by regexp if like "regexp:/foo/i"
                if (preg_match('/^regexp\s*:\s*(.*)/i', $options['content'], $matches)) {
                    if (!preg_match($matches[1], self::getNodeText($node))) {
                        $invalid = true;
                    }
                } // match empty string
                elseif ($options['content'] === '') {
                    if (self::getNodeText($node) !== '') {
                        $invalid = true;
                    }
                } // match by exact string
                elseif (strstr(self::getNodeText($node), $options['content']) === false) {
                    $invalid = true;
                }

                if (!$invalid) {
                    $filtered[] = $node;
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by parent node
        if ($options['parent']) {
            $parentNodes = self::findNodes($dom, $options['parent'], $isHtml);
            $parentNode  = isset($parentNodes[0]) ? $parentNodes[0] : null;

            foreach ($nodes as $node) {
                if ($parentNode !== $node->parentNode) {
                    continue;
                }

                $filtered[] = $node;
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by child node
        if ($options['child']) {
            $childNodes = self::findNodes($dom, $options['child'], $isHtml);
            $childNodes = !empty($childNodes) ? $childNodes : array();

            foreach ($nodes as $node) {
                foreach ($node->childNodes as $child) {
                    foreach ($childNodes as $childNode) {
                        if ($childNode === $child) {
                            $filtered[] = $node;
                        }
                    }
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by adjacent-sibling
        if ($options['adjacent-sibling']) {
            $adjacentSiblingNodes = self::findNodes($dom, $options['adjacent-sibling'], $isHtml);
            $adjacentSiblingNodes = !empty($adjacentSiblingNodes) ? $adjacentSiblingNodes : array();

            foreach ($nodes as $node) {
                $sibling = $node;

                while ($sibling = $sibling->nextSibling) {
                    if ($sibling->nodeType !== XML_ELEMENT_NODE) {
                        continue;
                    }

                    foreach ($adjacentSiblingNodes as $adjacentSiblingNode) {
                        if ($sibling === $adjacentSiblingNode) {
                            $filtered[] = $node;
                            break;
                        }
                    }

                    break;
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by ancestor
        if ($options['ancestor']) {
            $ancestorNodes = self::findNodes($dom, $options['ancestor'], $isHtml);
            $ancestorNode  = isset($ancestorNodes[0]) ? $ancestorNodes[0] : null;

            foreach ($nodes as $node) {
                $parent = $node->parentNode;

                while ($parent && $parent->nodeType != XML_HTML_DOCUMENT_NODE) {
                    if ($parent === $ancestorNode) {
                        $filtered[] = $node;
                    }

                    $parent = $parent->parentNode;
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by descendant
        if ($options['descendant']) {
            $descendantNodes = self::findNodes($dom, $options['descendant'], $isHtml);
            $descendantNodes = !empty($descendantNodes) ? $descendantNodes : array();

            foreach ($nodes as $node) {
                foreach (self::getDescendants($node) as $descendant) {
                    foreach ($descendantNodes as $descendantNode) {
                        if ($descendantNode === $descendant) {
                            $filtered[] = $node;
                        }
                    }
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by children
        if ($options['children']) {
            $validChild   = array('count', 'greater_than', 'less_than', 'only');
            $childOptions = self::assertValidKeys(
                $options['children'],
                $validChild
            );

            foreach ($nodes as $node) {
                $childNodes = $node->childNodes;

                foreach ($childNodes as $childNode) {
                    if ($childNode->nodeType !== XML_CDATA_SECTION_NODE &&
                        $childNode->nodeType !== XML_TEXT_NODE) {
                        $children[] = $childNode;
                    }
                }

                // we must have children to pass this filter
                if (!empty($children)) {
                    // exact count of children
                    if ($childOptions['count'] !== null) {
                        if (count($children) !== $childOptions['count']) {
                            break;
                        }
                    } // range count of children
                    elseif ($childOptions['less_than']    !== null &&
                        $childOptions['greater_than'] !== null) {
                        if (count($children) >= $childOptions['less_than'] ||
                            count($children) <= $childOptions['greater_than']) {
                            break;
                        }
                    } // less than a given count
                    elseif ($childOptions['less_than'] !== null) {
                        if (count($children) >= $childOptions['less_than']) {
                            break;
                        }
                    } // more than a given count
                    elseif ($childOptions['greater_than'] !== null) {
                        if (count($children) <= $childOptions['greater_than']) {
                            break;
                        }
                    }

                    // match each child against a specific tag
                    if ($childOptions['only']) {
                        $onlyNodes = self::findNodes(
                            $dom,
                            $childOptions['only'],
                            $isHtml
                        );

                        // try to match each child to one of the 'only' nodes
                        foreach ($children as $child) {
                            $matched = false;

                            foreach ($onlyNodes as $onlyNode) {
                                if ($onlyNode === $child) {
                                    $matched = true;
                                }
                            }

                            if (!$matched) {
                                break 2;
                            }
                        }
                    }

                    $filtered[] = $node;
                }
            }

            $nodes = $filtered;

            if (empty($nodes)) {
                return;
            }
        }

        // return the first node that matches all criteria
        return !empty($nodes) ? $nodes : array();
    }

    /**
     * Recursively get flat array of all descendants of this node.
     *
     * @param DOMNode $node
     *
     * @return array
     */
    private static function getDescendants(DOMNode $node) {
        $allChildren = array();
        $childNodes  = $node->childNodes ? $node->childNodes : array();

        foreach ($childNodes as $child) {
            if ($child->nodeType === XML_CDATA_SECTION_NODE ||
                $child->nodeType === XML_TEXT_NODE) {
                continue;
            }

            $children    = self::getDescendants($child);
            $allChildren = array_merge($allChildren, $children, array($child));
        }

        return isset($allChildren) ? $allChildren : array();
    }

    /**
     * Gets elements by case insensitive tagname.
     *
     * @param DOMDocument $dom
     * @param string      $tag
     *
     * @return DOMNodeList
     */
    private static function getElementsByCaseInsensitiveTagName(DOMDocument $dom, $tag) {
        $elements = $dom->getElementsByTagName(strtolower($tag));

        if ($elements->length == 0) {
            $elements = $dom->getElementsByTagName(strtoupper($tag));
        }

        return $elements;
    }

    /**
     * Get the text value of this node's child text node.
     *
     * @param DOMNode $node
     *
     * @return string
     */
    private static function getNodeText(DOMNode $node) {
        if (!$node->childNodes instanceof DOMNodeList) {
            return '';
        }

        $result = '';

        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeType === XML_TEXT_NODE ||
                $childNode->nodeType === XML_CDATA_SECTION_NODE) {
                $result .= trim($childNode->data) . ' ';
            } else {
                $result .= self::getNodeText($childNode);
            }
        }

        return str_replace('  ', ' ', $result);
    }

    /**
     * Resolve centralised notifications that are scheduled between 2 times.
     *
     * @param string $resolver_class_name
     * @param int $min_time
     * @param int $max_time
     * @param array $expected
     */
    protected static function assert_scheduled_events(
        string $resolver_class_name,
        int $min_time,
        int $max_time,
        array $expected
    ): void {
        $events = call_user_func([$resolver_class_name, 'get_scheduled_events'], $min_time, $max_time);
        $actual = $events->to_array();
        $actual_to_array = array_map(static function (\stdClass $scheduled) {
            return (array)$scheduled;
        }, $actual);
        self::assertEqualsCanonicalizing(
            $expected,
            $actual_to_array,
            'Expected: ' . json_encode($expected) . ' but got: ' . json_encode($actual_to_array)
        );
    }


    // @codingStandardsIgnoreEnd
}
