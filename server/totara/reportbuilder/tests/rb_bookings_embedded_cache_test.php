<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 *
 * Unit/functional tests to check Bookings reports caching
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * @group totara_reportbuilder
 * @group mod_facetoface
 */
class totara_reportbuilder_rb_bookings_embedded_cache_testcase extends reportcache_advanced_testcase {
    // testcase data
    protected $report_builder_data = array(
        'id' => 8, 'fullname' => 'My Bookings', 'shortname' => 'bookings',
        'source' => 'facetoface_sessions', 'hidden' => 1, 'embedded' => 1, 'contentmode' => 2
    );

    protected $report_builder_columns_data = array(
        array('id' => 31, 'reportid' => 8, 'type' => 'course', 'value' => 'courselink',
              'heading' => 'A', 'sortorder' => 1),
        array('id' => 32, 'reportid' => 8, 'type' => 'facetoface', 'value' => 'name',
              'heading' => 'B', 'sortorder' => 2),
        array('id' => 33, 'reportid' => 8, 'type' => 'date', 'value' => 'sessiondate',
              'heading' => 'C', 'sortorder' => 3),
        array('id' => 34, 'reportid' => 8, 'type' => 'date', 'value' => 'timestart',
              'heading' => 'D', 'sortorder' => 4),
        array('id' => 35, 'reportid' => 8, 'type' => 'date', 'value' => 'timefinish',
              'heading' => 'E', 'sortorder' => 5),
        array('id' => 36, 'reportid' => 8, 'type' => 'status', 'value' => 'statuscode',
              'heading' => 'F', 'sortorder' => 6),
        array('id' => 37, 'reportid' => 8, 'type' => 'ctx', 'value' => 'id',
            'heading' => 'G', 'sortorder' => 7)
    );

    protected $report_builder_settings_data = array(
        array('id' => 1, 'reportid' => 8, 'type' => 'date_content', 'name' => 'enable', 'value' => '1'),
        array('id' => 2, 'reportid' => 8, 'type' => 'date_content',  'name' => 'when', 'value' => 'future')
    );

    protected $delta = 3600;

    // Work data
    protected $user1 = null;
    protected $user2 = null;
    protected $user3 = null;
    protected $user4 = null;
    protected $course1 = null;
    protected $course2 = null;
    protected static $ind = 0;

    protected function tearDown(): void {
        $this->report_builder_data = null;
        $this->report_builder_columns_data = null;
        $this->report_builder_settings_data = null;
        $this->delta = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->user4 = null;
        $this->course1 = null;
        $this->course2 = null;
        parent::tearDown();
    }

    /**
     * Prepare mock data for testing
     *
     * Common part of all test cases:
     * - Add four users
     * - Add two courses
     * - Enrol first three users to course1
     * - Enrol user3 and user4 to course2
     * - Create two bookings to user1, one for to user2, and one for user3
     * - Create four bookings (for each users in inverted time
     */
    protected function setUp(): void {
        parent::setup();
        set_config('facetoface_allow_legacy_notifications', 1);
        $this->setAdminUser();
        // Common parts of test cases:
        // Create report record in database
        $this->loadDataSet($this->createArrayDataSet(array('report_builder' => array($this->report_builder_data),
                                                           'report_builder_columns' => $this->report_builder_columns_data,
                                                           'report_builder_settings' => $this->report_builder_settings_data)));
        // Create four users
        $job_generator = \totara_job\testing\generator::instance();
        $this->user1 = $job_generator->create_user_and_job()[0];
        $this->user2 = $job_generator->create_user_and_job()[0];
        $this->user3 = $job_generator->create_user_and_job()[0];
        $this->user4 = $job_generator->create_user_and_job()[0];

        // Create two courses
        $this->course1 = $this->getDataGenerator()->create_course(array(), array('createsections' => true));
        $this->course2 = $this->getDataGenerator()->create_course(array(), array('createsections' => true));

        // Enrol first three users to course1
        // Enrol user3 and user4 to course2
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user3->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user3->id, $this->course2->id);
        $this->getDataGenerator()->enrol_user($this->user4->id, $this->course2->id);

        // Create two bookings to user1, one for to user2, and one for user3
        $this->create_booking($this->user1, $this->user2, $this->course1);
        $this->create_booking($this->user1, $this->user3, $this->course1);

        // Assign user2 to be user1's manager.
        $managerja = \totara_job\job_assignment::create_default($this->user2->id);
        \totara_job\job_assignment::create_default($this->user1->id, array('managerjaid' => $managerja->id));
    }

     /**
     * Create mock booking
     *
     * @param stdClass $initiator First attendee
     * @param stdClass $attender Second attendee
     * @param stdClass $course Course record
     * @param bool $timeinverse Invert future time to past
     */
    protected function create_booking($initiator, $attender, $course, $timeinverse = false) {
        global $DB;

        self::$ind++;

        // Create activity
        $facetoface = new stdClass();
        $facetoface->course = $course->id;
        $facetoface->name = 'Name '.self::$ind;
        $facetoface->display = 6;
        $facetoface->reminderperiod = 2;
        $facetoface->multiplesessions = 1;
        $facetoface->timecreated = time();
        $facetoface->showoncal = 1;

        $f2fgenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface1 = $f2fgenerator->create_instance($facetoface);

        // Add session
        $sessiondata = new stdClass();
        $sessiondata->facetoface = $facetoface1->id;
        $sessiondata->capacity = 10;
        $sessiondata->allowoverbook = 0;
        $sessiondata->normalcost = 0;
        $sessiondata->discountcost = 0;
        $sessiondata->usermodified = 2;

        // Dates time will be adjusted afte sign ups
        $sessiondate = new stdClass();
        $sessiondate->sessionid = self::$ind;
        $sessiondate->roomids = [];
        $sessiondate->timestart = time() + HOURSECS * self::$ind;
        $sessiondate->timefinish = time() + 2 * HOURSECS * self::$ind - 1;
        $sessiondate->sessiontimezone = 'Europe/London';
        $sessiondates = array($sessiondate);
        $sessiondata->sessiondates = $sessiondates;

        $sessionid = $f2fgenerator->add_session($sessiondata);

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $seminarevent = new \mod_facetoface\seminar_event($sessionid);
        $session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);

        // Signup for session
        $sink = $this->redirectMessages();
        $initsignup = \mod_facetoface\signup::create($initiator->id, new \mod_facetoface\seminar_event($session->id))->set_skipusernotification();
        \mod_facetoface\signup_helper::signup($initsignup);
        \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($attender->id, new \mod_facetoface\seminar_event($session->id))->set_skipusernotification());

        $delta = ($timeinverse) ? -1 * $this->delta : $this->delta;
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + $delta;
        $sessiondate->timefinish = time() + $delta + abs($delta) * 0.5;
        $seminarevent = new \mod_facetoface\seminar_event($session->id);
        \mod_facetoface\seminar_event_helper::merge_sessions($seminarevent, [$sessiondate]);

        $this->executeAdhocTasks();
        $this->assertCount(2, $sink->get_messages());
        $sink->close();
    }

    /**
     * Test bookings with/without using cache
     * Test case:
     * - Common part (@see: self::setUp() )
     * - Check bookings for first user (2)
     * - Check bookings for second user (1)
     * - Check bookings for fourth user (0)
     */
    public function test_bookings() {
        $usecache = false; // No content caching if content options present.
        $courseidalias = reportbuilder_get_extrafield_alias('course', 'courselink', 'course_id');
        $result = $this->get_report_result($this->report_builder_data['shortname'], array('userid' => $this->user1->id), $usecache);
        $this->assertCount(2, $result);
        foreach ($result as $r) {
            $this->assertEquals($this->course1->id, $r->$courseidalias);
        }
        $result = $this->get_report_result($this->report_builder_data['shortname'], array('userid' => $this->user2->id), $usecache);
        $this->assertCount(1, $result);
        foreach ($result as $r) {
            $this->assertEquals($this->course1->id, $r->$courseidalias);
        }
        $result = $this->get_report_result($this->report_builder_data['shortname'], array('userid' => $this->user4->id), $usecache);
        $this->assertCount(0, $result);
    }

    public function test_is_capable() {

        // Set up report and embedded object for is_capable checks.
        $shortname = $this->report_builder_data['shortname'];
        $config = (new rb_config())->set_embeddata(array('userid' => $this->user1->id));
        $report = reportbuilder::create_embedded($shortname, $config);
        $embeddedobject = $report->embedobj;

        // Test admin can access report.
        $this->assertTrue($embeddedobject->is_capable(2, $report),
                'admin cannot access report');

        // Test user1 can access report for self.
        $this->assertTrue($embeddedobject->is_capable($this->user1->id, $report),
                'user cannot access their own report');

        // Test user1's manager can access report.
        $this->assertTrue($embeddedobject->is_capable($this->user2->id, $report),
                'manager cannot access report');

        // Test that user3 cannot access the report for another user.
        $this->assertFalse($embeddedobject->is_capable($this->user3->id, $report),
                'user should not be able to access another user\'s report');
    }
}
