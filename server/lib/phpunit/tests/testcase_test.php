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
 * PHPUnit integration tests
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Test testcase features.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_phpunit_testcase_testcase extends \core_phpunit\testcase {

    /**
     * Tests that bootstrapping has occurred correctly
     * @return void
     */
    public function test_bootstrap() {
        global $CFG;
        // The use of httpswwwroot is deprecated, but we are still setting it for backwards compatibility.
        $this->assertTrue(isset($CFG->httpswwwroot));
        $this->assertEquals($CFG->httpswwwroot, $CFG->wwwroot);
        $this->assertEquals('https://www.example.com/moodle', $CFG->wwwroot);
        // Totara: test instance separation
        $this->assertEquals(PHPUNIT_INSTANCE, substr($CFG->prefix, -(strlen(PHPUNIT_INSTANCE))));
        $this->assertEquals(PHPUNIT_INSTANCE, substr($CFG->dataroot, -(strlen(PHPUNIT_INSTANCE))));
    }

    /**
     * This is just a verification if I understand the PHPUnit assert docs right --skodak
     * @return void
     */
    public function test_assert_behaviour() {
        // Arrays.
        $a = array('a', 'b', 'c');
        $b = array('a', 'c', 'b');
        $c = array('a', 'b', 'c');
        $d = array('a', 'b', 'C');
        $this->assertNotEquals($a, $b);
        $this->assertNotEquals($a, $d);
        $this->assertEquals($a, $c);
        $this->assertEqualsCanonicalizing($a, $b);

        // Objects.
        $a = new stdClass();
        $a->x = 'x';
        $a->y = 'y';
        $b = new stdClass(); // Switched order.
        $b->y = 'y';
        $b->x = 'x';
        $c = $a;
        $d = new stdClass();
        $d->x = 'x';
        $d->y = 'y';
        $d->z = 'z';
        $this->assertEquals($a, $b);
        $this->assertNotSame($a, $b);
        $this->assertEquals($a, $c);
        $this->assertSame($a, $c);
        $this->assertNotEquals($a, $d);

        // String comparison.
        $this->assertEquals(1, '1');
        $this->assertEquals(null, '');

        if (version_compare(PHP_VERSION, '8.0', '<')) {
            $this->assertNotEquals(1, '1 ');
        } else {
            $this->assertEquals(1, '1 ');
        }
        $this->assertNotEquals(0, '');
        $this->assertNotEquals(null, '0');
        $this->assertNotEquals(array(), '');

        // Other comparison.
        $this->assertEquals(null, null);
        $this->assertEquals(false, null);
        $this->assertEquals(0, null);

        // Emptiness.
        $this->assertEmpty(0);
        $this->assertEmpty(0.0);
        $this->assertEmpty('');
        $this->assertEmpty('0');
        $this->assertEmpty(false);
        $this->assertEmpty(null);
        $this->assertEmpty(array());

        $this->assertNotEmpty(1);
        $this->assertNotEmpty(0.1);
        $this->assertNotEmpty(-1);
        $this->assertNotEmpty(' ');
        $this->assertNotEmpty('0 ');
        $this->assertNotEmpty(true);
        $this->assertNotEmpty(array(null));
        $this->assertNotEmpty(new stdClass());
    }

    /**
     * Make sure there are no sloppy Windows line endings
     * that would break our tests.
     */
    public function test_lineendings() {
        $string = <<<STRING
a
b
STRING;
        $this->assertSame("a\nb", $string, 'Make sure all project files are checked out with unix line endings.');

    }

    public function test_debugging() {
        global $CFG;

        debugging('hokus');
        $this->assertDebuggingCalled();
        debugging('pokus');
        $this->assertDebuggingCalled('pokus');
        debugging('pokus', DEBUG_MINIMAL);
        $this->assertDebuggingCalled('pokus', DEBUG_MINIMAL);
        $this->assertDebuggingNotCalled();

        debugging('a');
        debugging('b', DEBUG_MINIMAL);
        debugging('c', DEBUG_DEVELOPER);
        $debuggings = $this->getDebuggingMessages();
        $this->assertCount(3, $debuggings);
        $this->assertSame('a', $debuggings[0]->message);
        $this->assertSame(DEBUG_NORMAL, $debuggings[0]->level);
        $this->assertSame('b', $debuggings[1]->message);
        $this->assertSame(DEBUG_MINIMAL, $debuggings[1]->level);
        $this->assertSame('c', $debuggings[2]->message);
        $this->assertSame(DEBUG_DEVELOPER, $debuggings[2]->level);

        $this->resetDebugging();
        $this->assertDebuggingNotCalled();
        $debuggings = $this->getDebuggingMessages();
        $this->assertCount(0, $debuggings);

        set_debugging(DEBUG_NONE);
        debugging('hokus');
        $this->assertDebuggingNotCalled();
        set_debugging(DEBUG_DEVELOPER);
    }

    /**
     * @test
     *
     * Annotations are a valid PHPUnit method for running tests.  Debugging needs to support them.
     */
    public function debugging_called_with_annotation() {
        debugging('pokus', DEBUG_MINIMAL);
        $this->assertDebuggingCalled('pokus', DEBUG_MINIMAL);
    }

    public function test_set_user() {
        global $USER, $DB, $SESSION;

        $this->assertEquals(0, $USER->id);
        $this->assertSame($_SESSION['USER'], $USER);
        $this->assertSame($GLOBALS['USER'], $USER);

        $user = $DB->get_record('user', array('id'=>2));
        $this->assertNotEmpty($user);
        $this->setUser($user);
        $this->assertEquals(2, $USER->id);
        $this->assertEquals(2, $_SESSION['USER']->id);
        $this->assertSame($_SESSION['USER'], $USER);
        $this->assertSame($GLOBALS['USER'], $USER);

        $USER->id = 3;
        $this->assertEquals(3, $USER->id);
        $this->assertEquals(3, $_SESSION['USER']->id);
        $this->assertSame($_SESSION['USER'], $USER);
        $this->assertSame($GLOBALS['USER'], $USER);

        \core\session\manager::set_user($user);
        $this->assertEquals(2, $USER->id);
        $this->assertEquals(2, $_SESSION['USER']->id);
        $this->assertSame($_SESSION['USER'], $USER);
        $this->assertSame($GLOBALS['USER'], $USER);

        $USER = $DB->get_record('user', array('id'=>1));
        $this->assertNotEmpty($USER);
        $this->assertEquals(1, $USER->id);
        $this->assertEquals(1, $_SESSION['USER']->id);
        $this->assertSame($_SESSION['USER'], $USER);
        $this->assertSame($GLOBALS['USER'], $USER);

        $this->setUser(null);
        $this->assertEquals(0, $USER->id);
        $this->assertSame($_SESSION['USER'], $USER);
        $this->assertSame($GLOBALS['USER'], $USER);

        // Ensure session is reset after setUser, as it may contain extra info.
        $SESSION->sometestvalue = true;
        $this->setUser($user);
        $this->assertObjectNotHasAttribute('sometestvalue', $SESSION);
    }

    public function test_set_admin_user() {
        global $USER;

        $this->setAdminUser();
        $this->assertEquals($USER->id, 2);
        $this->assertTrue(is_siteadmin());
    }

    public function test_set_guest_user() {
        global $USER;

        $this->setGuestUser();
        $this->assertEquals($USER->id, 1);
        $this->assertTrue(isguestuser());
    }

    public function test_database_reset() {
        global $DB;

        $this->assertEquals(1, $DB->count_records('course')); // Only frontpage in new site.

        // Totara: we use real temporary table for context_temp since Totara 12,
        //         there are no problematic tables with non-incrementing ids any more.

        $this->assertEquals(0, $DB->count_records('user_preferences'));
        $originaldisplayid = $DB->insert_record('user_preferences', array('userid'=>2, 'name'=> 'phpunittest', 'value'=>'x'));
        $this->assertEquals(1, $DB->count_records('user_preferences'));

        $numcourses = $DB->count_records('course');
        $course = $this->getDataGenerator()->create_course();
        $this->assertEquals($numcourses + 1, $DB->count_records('course'));

        $this->assertEquals(2, $DB->count_records('user'));
        $DB->delete_records('user', array('id'=>1));
        $this->assertEquals(1, $DB->count_records('user'));

        $this->resetAllData();

        $this->assertEquals(1, $DB->count_records('course')); // Only frontpage in new site.

        $numcourses = $DB->count_records('course');
        $course = $this->getDataGenerator()->create_course();
        $this->assertEquals($numcourses + 1, $DB->count_records('course'));

        $displayid = $DB->insert_record('user_preferences', array('userid'=>2, 'name'=> 'phpunittest', 'value'=>'x'));
        $this->assertEquals($originaldisplayid, $displayid);

        $this->assertEquals(2, $DB->count_records('user'));
        $DB->delete_records('user', array('id'=>2));
        $user = $this->getDataGenerator()->create_user();
        $this->assertEquals(2, $DB->count_records('user'));
        $this->assertGreaterThan(2, $user->id);

        $this->resetAllData();

        $numcourses = $DB->count_records('course');
        $course = $this->getDataGenerator()->create_course();
        $this->assertEquals($numcourses + 1, $DB->count_records('course'));

        $this->assertEquals(2, $DB->count_records('user'));
        $DB->delete_records('user', array('id'=>2));

        $this->resetAllData();

        $numcourses = $DB->count_records('course');
        $course = $this->getDataGenerator()->create_course();
        $this->assertEquals($numcourses + 1, $DB->count_records('course'));

        $this->assertEquals(2, $DB->count_records('user'));
    }

    public function test_change_detection() {
        global $DB, $CFG, $COURSE, $SITE, $USER;

        self::resetAllData(true);

        // Database change.
        $this->assertEquals(1, $DB->get_field('user', 'confirmed', array('id'=>2)));
        $DB->set_field('user', 'confirmed', 0, array('id'=>2));
        try {
            self::resetAllData(true);
            $this->fail('Exception expected!');
        } catch (Exception $e) {
            $this->assertInstanceOf('\PHPUnit\Framework\Error\Warning', $e);
        }
        $this->assertEquals(1, $DB->get_field('user', 'confirmed', array('id'=>2)));

        // Config change.
        $CFG->xx = 'yy';
        unset($CFG->admin);
        $CFG->rolesactive = 0;
        try {
            self::resetAllData(true);
            $this->fail('Exception expected!');
        } catch (Exception $e) {
            $this->assertInstanceOf('\PHPUnit\Framework\Error\Warning', $e);
            $this->assertStringContainsString('xx', $e->getMessage());
            $this->assertStringContainsString('admin', $e->getMessage());
            $this->assertStringContainsString('rolesactive', $e->getMessage());
        }
        $this->assertFalse(isset($CFG->xx));
        $this->assertTrue(isset($CFG->admin));
        $this->assertEquals(1, $CFG->rolesactive);

        // _GET change.
        $_GET['__somethingthatwillnotnormallybepresent__'] = 'yy';
        self::resetAllData(true);

        $this->assertEquals(array(), $_GET);

        // _POST change.
        $_POST['__somethingthatwillnotnormallybepresent2__'] = 'yy';
        self::resetAllData(true);
        $this->assertEquals(array(), $_POST);

        // _FILES change.
        $_FILES['__somethingthatwillnotnormallybepresent3__'] = 'yy';
        self::resetAllData(true);
        $this->assertEquals(array(), $_FILES);

        // _REQUEST change.
        $_REQUEST['__somethingthatwillnotnormallybepresent4__'] = 'yy';
        self::resetAllData(true);
        $this->assertEquals(array(), $_REQUEST);

        // Silent changes.
        $_SERVER['xx'] = 'yy';
        self::resetAllData(true);
        $this->assertFalse(isset($_SERVER['xx']));

        // COURSE change.
        $SITE->id = 10;
        $COURSE = new stdClass();
        $COURSE->id = 7;
        try {
            self::resetAllData(true);
            $this->fail('Exception expected!');
        } catch (Exception $e) {
            $this->assertInstanceOf('\PHPUnit\Framework\Error\Warning', $e);
            $this->assertEquals(1, $SITE->id);
            $this->assertSame($SITE, $COURSE);
            $this->assertSame($SITE, $COURSE);
        }

        // USER change.
        $this->setUser(2);
        try {
            self::resetAllData(true);
            $this->fail('Exception expected!');
        } catch (Exception $e) {
            $this->assertInstanceOf('\PHPUnit\Framework\Error\Warning', $e);
            $this->assertEquals(0, $USER->id);
        }
    }

    public function test_getDataGenerator() {
        $generator = $this->getDataGenerator();
        $this->assertInstanceOf('core\testing\generator', $generator);
    }

    public function test_database_mock1() {
        global $DB;

        try {
            $DB->get_record('pokus', array());
            $this->fail('Exception expected when accessing non existent table');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('dml_exception', $e);
        }
        $DB = $this->createMock(get_class($DB));
        $this->assertNull($DB->get_record('pokus', array()));
        // Rest continues after reset.
    }

    public function test_database_mock2() {
        global $DB;

        // Now the database should be back to normal.
        $this->assertFalse($DB->get_record('user', array('id'=>9999)));
    }

    public function test_load_dataset() {
        global $DB;

        $this->assertFalse($DB->record_exists('user', array('id'=>5)));
        $this->assertFalse($DB->record_exists('user', array('id'=>7)));
        $dataset = $this->createXMLDataSet(__DIR__.'/fixtures/sample_dataset.xml');
        $this->loadDataSet($dataset);
        $this->assertTrue($DB->record_exists('user', array('id'=>5)));
        $this->assertTrue($DB->record_exists('user', array('id'=>7)));
        $user5 = $DB->get_record('user', array('id'=>5));
        $user7 = $DB->get_record('user', array('id'=>7));
        $this->assertSame('john.doe', $user5->username);
        $this->assertSame('jane.doe', $user7->username);

        $this->assertSame(['user'], $dataset->getTableNames());
        $this->assertSame(['id', 'username', 'email'], $dataset->getTableMetaData('user')->getColumns());
        $this->assertSame(2, $dataset->getTable('user')->getRowCount());
        $this->assertSame(['id' => '5', 'username' => 'john.doe', 'email' => 'john@example.com'], $dataset->getTable('user')->getRow(0));
        $this->assertSame('john.doe', $dataset->getTable('user')->getValue(0, 1));

        $dataset = $this->createCsvDataSet(array('user'=>__DIR__.'/fixtures/sample_dataset.csv'));
        $this->loadDataSet($dataset);
        $this->assertEquals(8, $DB->get_field('user', 'id', array('username'=>'pepa.novak')));
        $this->assertEquals(9, $DB->get_field('user', 'id', array('username'=>'bozka.novakova')));

        $data = array(
            'user' => array(
                array('username', 'email'),
                array('top.secret', 'top@example.com'),
                array('low.secret', 'low@example.com'),
            ),
        );
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
        $this->assertTrue($DB->record_exists('user', array('email'=>'top@example.com')));
        $this->assertTrue($DB->record_exists('user', array('email'=>'low@example.com')));

        $data = array(
            'user' => array(
                array('username'=>'noidea', 'email'=>'noidea@example.com'),
                array('username'=>'onemore', 'email'=>'onemore@example.com'),
            ),
        );
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
        $this->assertTrue($DB->record_exists('user', array('username'=>'noidea')));
        $this->assertTrue($DB->record_exists('user', array('username'=>'onemore')));

        $this->assertFalse($DB->record_exists('user', array('id'=>15)));
        $this->assertFalse($DB->record_exists('user', array('id'=>17)));
        $dataset = $this->createFlatXMLDataSet(__DIR__.'/fixtures/sample_dataset_flat.xml');
        $this->loadDataSet($dataset);
        $this->assertTrue($DB->record_exists('user', array('id'=>15)));
        $this->assertTrue($DB->record_exists('user', array('id'=>17)));
        $user15 = $DB->get_record('user', array('id'=>15));
        $user17 = $DB->get_record('user', array('id'=>17));
        $this->assertSame('nobody', $user15->username);
        $this->assertSame('somebody', $user17->username);
    }

    public function test_assert_time_current() {
        $this->assertTimeCurrent(time());

        $this->setCurrentTimeStart();
        $this->assertTimeCurrent(time());
        $this->waitForSecond();
        $this->assertTimeCurrent(time());
        $this->assertTimeCurrent(time()-1);

        try {
            $this->setCurrentTimeStart();
            $this->assertTimeCurrent(time()+10);
            $this->fail('Failed assert expected');
        } catch (Exception $e) {
            $this->assertInstanceOf('\PHPUnit\Framework\ExpectationFailedException', $e);
        }

        try {
            $this->setCurrentTimeStart();
            $this->assertTimeCurrent(time()-10);
            $this->fail('Failed assert expected');
        } catch (Exception $e) {
            $this->assertInstanceOf('\PHPUnit\Framework\ExpectationFailedException', $e);
        }
    }

    public function test_message_processors_reset() {
        global $DB;

        // Get all processors first.
        $processors1 = get_message_processors();

        // Add a new message processor and get all processors again.
        $processor = new stdClass();
        $processor->name = 'test_processor';
        $processor->enabled = 1;
        $DB->insert_record('message_processors', $processor);

        $processors2 = get_message_processors();

        // Assert that new processor still haven't been added to the list.
        $this->assertSame($processors1, $processors2);

        // Reset message processors data.
        $processors3 = get_message_processors(false, true);
        // Now, list of processors should not be the same any more,
        // And we should have one more message processor in the list.
        $this->assertNotSame($processors1, $processors3);
        $this->assertEquals(count($processors1) + 1, count($processors3));
    }

    public function test_message_redirection() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Any core message will do here.
        $message1 = new \core\message\message();
        $message1->courseid          = 1;
        $message1->component         = 'moodle';
        $message1->name              = 'instantmessage';
        $message1->userfrom          = $user1;
        $message1->userto            = $user2;
        $message1->subject           = 'message subject 1';
        $message1->fullmessage       = 'message body';
        $message1->fullmessageformat = FORMAT_MARKDOWN;
        $message1->fullmessagehtml   = '<p>message body</p>';
        $message1->smallmessage      = 'small message';
        $message1->notification      = 0;

        $message2 = new \core\message\message();
        $message2->courseid          = 1;
        $message2->component         = 'moodle';
        $message2->name              = 'instantmessage';
        $message2->userfrom          = $user2;
        $message2->userto            = $user1;
        $message2->subject           = 'message subject 2';
        $message2->fullmessage       = 'message body';
        $message2->fullmessageformat = FORMAT_MARKDOWN;
        $message2->fullmessagehtml   = '<p>message body</p>';
        $message2->smallmessage      = 'small message';
        $message2->notification      = 0;

        // There should be debugging message without redirection.
        $mailsink = $this->redirectEmails();
        $mailsink->close();
        message_send($message1);
        $this->assertDebuggingCalled(null, null, 'message_send() must print debug message that messaging is disabled in phpunit tests.');

        // Sink should catch messages.
        $sink = $this->redirectMessages();
        $mid1 = message_send($message1);
        $mid2 = message_send($message2);

        $this->assertDebuggingNotCalled('message redirection must prevent debug messages from the message_send()');
        $this->assertEquals(2, $sink->count());
        $this->assertGreaterThanOrEqual(1, $mid1);
        $this->assertGreaterThanOrEqual($mid1, $mid2);

        $messages = $sink->get_messages();
        $this->assertIsArray($messages);
        $this->assertCount(2, $messages);
        $this->assertEquals($mid1, $messages[0]->id);
        $this->assertEquals($message1->userto->id, $messages[0]->useridto);
        $this->assertEquals($message1->userfrom->id, $messages[0]->useridfrom);
        $this->assertEquals($message1->smallmessage, $messages[0]->smallmessage);
        $this->assertEquals($mid2, $messages[1]->id);
        $this->assertEquals($message2->userto->id, $messages[1]->useridto);
        $this->assertEquals($message2->userfrom->id, $messages[1]->useridfrom);
        $this->assertEquals($message2->smallmessage, $messages[1]->smallmessage);

        // Test resetting.
        $sink->clear();
        $messages = $sink->get_messages();
        $this->assertIsArray($messages);
        $this->assertCount(0, $messages);

        message_send($message1);
        $messages = $sink->get_messages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);

        // Test closing.
        $sink->close();
        $messages = $sink->get_messages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages, 'Messages in sink are supposed to stay there after close');

        // Test debugging is enabled again.
        message_send($message1);
        $this->assertDebuggingCalled(null, null, 'message_send() must print debug message that messaging is disabled in phpunit tests.');

        // Test invalid names and components.

        $sink = $this->redirectMessages();

        $message3 = new \core\message\message();
        $message3->courseid          = 1;
        $message3->component         = 'xxxx_yyyyy';
        $message3->name              = 'instantmessage';
        $message3->userfrom          = $user2;
        $message3->userto            = $user1;
        $message3->subject           = 'message subject 2';
        $message3->fullmessage       = 'message body';
        $message3->fullmessageformat = FORMAT_MARKDOWN;
        $message3->fullmessagehtml   = '<p>message body</p>';
        $message3->smallmessage      = 'small message';
        $message3->notification      = 0;

        try {
            message_send($message3);
            $this->fail('coding expcetion expected if invalid component specified');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid component specified in message-send(): xxxx_yyyyy', $e->getMessage());
        }

        $message3->component = 'moodle';
        $message3->name      = 'yyyyyy';
        try {
            message_send($message3);
            $this->fail('coding expcetion expected if invalid name specified');
        } catch (moodle_exception $e) {
            $this->assertEquals("Coding error detected, it must be fixed by a programmer: Missing messaging defaults for event 'yyyyyy' in 'moodle' messages.php file", $e->getMessage());
            $this->assertInstanceOf('coding_exception', $e);
        }

        message_send($message1);
        $this->assertEquals(1, $sink->count());

        // Test if sink is terminated after reset.
        $this->assertTrue(\core_phpunit\internal_util::is_redirecting_messages());

        self::resetAllData();
        $this->assertFalse(\core_phpunit\internal_util::is_redirecting_messages());
    }

    public function test_set_timezone() {
        global $CFG;

        $this->assertSame('Australia/Perth', $CFG->timezone);
        $this->assertSame('Australia/Perth', date_default_timezone_get());

        $this->setTimezone('Pacific/Auckland', 'Europe/Prague');
        $this->assertSame('Pacific/Auckland', $CFG->timezone);
        $this->assertSame('Pacific/Auckland', date_default_timezone_get());

        $this->setTimezone('99', 'Europe/Prague');
        $this->assertSame('99', $CFG->timezone);
        $this->assertSame('Europe/Prague', date_default_timezone_get());

        $this->setTimezone('xxx', 'Europe/Prague');
        $this->assertSame('xxx', $CFG->timezone);
        $this->assertSame('Europe/Prague', date_default_timezone_get());

        $this->setTimezone();
        $this->assertSame('Australia/Perth', $CFG->timezone);
        $this->assertSame('Australia/Perth', date_default_timezone_get());

        try {
            $this->setTimezone('Pacific/Auckland', '');
            $this->fail('Exception expected!');
        } catch (Exception $e) {
            $this->assertInstanceOf('\PHPUnit\Framework\Error\Warning', $e);
        }

        try {
            $this->setTimezone('Pacific/Auckland', 'xxxx');
            $this->fail('Exception expected!');
        } catch (Exception $e) {
            $this->assertInstanceOf('\PHPUnit\Framework\Error\Warning', $e);
        }

        try {
            $this->setTimezone('Pacific/Auckland', null);
            $this->fail('Exception expected!');
        } catch (Exception $e) {
            $this->assertInstanceOf('\PHPUnit\Framework\Error\Warning', $e);
        }

    }

    public function test_locale_reset() {
        global $CFG;

        // If this fails self::resetAllData(); must be updated.
        $this->assertSame('en_AU.UTF-8', get_string('locale', 'langconfig'));
        $this->assertSame('English_Australia.1252', get_string('localewin', 'langconfig'));

        if ($CFG->ostype === 'WINDOWS') {
            $this->assertSame('English_Australia.1252', setlocale(LC_TIME, 0));
            setlocale(LC_TIME, 'English_USA.1252');
        } else {
            $this->assertSame('en_AU.UTF-8', setlocale(LC_TIME, 0));
            setlocale(LC_TIME, 'en_US.UTF-8');
        }

        try {
            self::resetAllData(true);
            $this->fail('Exception expected!');
        } catch (Exception $e) {
            $this->assertInstanceOf('\PHPUnit\Framework\Error\Warning', $e);
        }

        if ($CFG->ostype === 'WINDOWS') {
            $this->assertSame('English_Australia.1252', setlocale(LC_TIME, 0));
        } else {
            $this->assertSame('en_AU.UTF-8', setlocale(LC_TIME, 0));
        }

        if ($CFG->ostype === 'WINDOWS') {
            $this->assertSame('English_Australia.1252', setlocale(LC_TIME, 0));
            setlocale(LC_TIME, 'English_USA.1252');
        } else {
            $this->assertSame('en_AU.UTF-8', setlocale(LC_TIME, 0));
            setlocale(LC_TIME, 'en_US.UTF-8');
        }

        self::resetAllData(false);

        if ($CFG->ostype === 'WINDOWS') {
            $this->assertSame('English_Australia.1252', setlocale(LC_TIME, 0));
        } else {
            $this->assertSame('en_AU.UTF-8', setlocale(LC_TIME, 0));
        }
    }

    /**
     * Test the two core user email addresses are as we expect them.
     */
    public function test_core_user_email_addresses() {
        global $USER;

        $this->setGuestUser();
        $this->assertSame('root@localhost', $USER->email);

        $this->setAdminUser();
        $this->assertSame('admin@example.com', $USER->email);
    }

    /**
     * Totara - allow admins to override lang string.
     */
    public function test_override_lang_string() {
        // Some system level tests first.
        $sm = get_string_manager();
        $this->assertInstanceOf('core_string_manager_standard', $sm);
        $this->assertSame('en', current_language());

        $en = '%A, %d %B %Y, %I:%M %p';
        $us = '%A, %B %d, %Y, %I:%M %p';

        $dateformat = get_string('strftimedaydatetime', 'langconfig');
        $this->assertSame($en, $dateformat);

        $this->assertNotSame($en, $us);
        $this->overrideLangString('strftimedaydatetime', 'langconfig', $us);
        $dateformat = get_string('strftimedaydatetime', 'langconfig');
        $this->assertSame($us, $dateformat);

        try {
            $this->overrideLangString('xxsddsdssd', 'langconfig', $us);
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Cannot override non-existent string', $ex->getMessage());
        }

        // Totara - make sure tha lang strings reset back.
        self::resetAllData();
        $en = '%A, %d %B %Y, %I:%M %p';
        $dateformat = get_string('strftimedaydatetime', 'langconfig');
        $this->assertSame($en, $dateformat);
    }

    public function test_default_environment() {
        global $CFG;
        $this->assertSame('https://www.example.com/moodle', $CFG->wwwroot);
        $this->assertSame('noreply@www.example.com', $CFG->noreplyaddress);
    }
}
