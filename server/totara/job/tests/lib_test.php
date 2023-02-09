<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_job
 */

use core_user\access_controller;
use totara_core\advanced_feature;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/job/lib.php');

class totara_job_lib_testcase extends advanced_testcase {

    /** @var  \core\testing\generator */
    private $data_generator;

    protected function tearDown(): void {
        $this->data_generator = null;
        parent::tearDown();
    }

    private function disable_engage_features() {
        advanced_feature::disable('engage_resources');
        access_controller::clear_instance_cache();
    }

    public function setUp(): void {
        parent::setup();

        // Engage allows several properties of users to become visible to all other users. To test that user
        // properties are hidden when appropritate, we need to disable engage.
        $this->disable_engage_features();

        $this->data_generator = $this->getDataGenerator();
    }

    public function test_totara_job_display_user_job() {
        $this->setAdminUser();

        // For $user1, we control the names and email so that we can test for them more accurately.
        // For $user2, we allow the generator to create these so that non-latin characters will also
        // be used in many of the tests.
        $userrecord = array(
            'firstname' => 'John',
            'lastname' => 'Smith',
            'email' => 'john@example.com'
        );
        $user1 = $this->data_generator->create_user($userrecord);
        $user2 = $this->data_generator->create_user();

        $jobdata11 = array(
            'userid' => $user1->id,
            'idnumber' => '1a',
            'fullname' => 'Developer'
        );
        $jobassignment_withname1 = job_assignment::create($jobdata11);

        $jobdata12 = array(
            'userid' => $user2->id,
            'idnumber' => '1b',
            'fullname' => 'Tester'
        );
        $jobassignment_withname2 = job_assignment::create($jobdata12);

        $jobdata21 = array(
            'userid' => $user1->id,
            'idnumber' => 2
        );
        $jobassignment_noname1 = job_assignment::create($jobdata21);

        $jobdata22 = array(
            'userid' => $user2->id,
            'idnumber' => '2a'
        );
        $jobassignment_noname2 = job_assignment::create($jobdata22);

        // Use job that does have fullname, can view email address.
        $returnedstring1 = totara_job_display_user_job($user1, $jobassignment_withname1);
        $this->assertEquals('John Smith (john@example.com) - Developer', $returnedstring1);

        $returnedstring2 = totara_job_display_user_job($user2, $jobassignment_withname2,);
        $this->assertEquals(fullname($user2) . ' (' .$user2->email . ') - Tester', $returnedstring2);

        // Use job that does not have fullname, can view email address.
        $returnedstring = totara_job_display_user_job($user1, $jobassignment_noname1);
        $this->assertEquals('John Smith (john@example.com) - Unnamed job assignment (ID: 2)', $returnedstring);

        $returnedstring2 = totara_job_display_user_job($user2, $jobassignment_noname2);
        $this->assertEquals(fullname($user2) . ' (' .$user2->email . ') - Unnamed job assignment (ID: 2a)', $returnedstring2);

        // If the $createjob argument is set to true, the corresponding string will be returned regardless of
        // the value of the job object.

        // Use job that does have fullname, can view email address.
        $returnedstring = totara_job_display_user_job($user1, $jobassignment_withname1, null, true);
        $this->assertEquals('John Smith (john@example.com) - create empty job assignment', $returnedstring);

        $returnedstring2 = totara_job_display_user_job($user2, $jobassignment_withname2, null, true);
        $this->assertEquals(fullname($user2) . ' (' .$user2->email . ') - create empty job assignment', $returnedstring2);

        // If no $jobassignment is supplied and $createjob is false, we get a string advising a job assignment
        // needs to be created.
        $returnedstring = totara_job_display_user_job($user1, null);
        $this->assertEquals('John Smith (john@example.com) - requires job assignment entry', $returnedstring);

        $returnedstring2 = totara_job_display_user_job($user2, null);
        $this->assertEquals(fullname($user2) . ' (' .$user2->email . ') - requires job assignment entry', $returnedstring2);

        // Check whether $CFG->showuseridentity is used to determine whether an email adress is shown or not
        set_config('showuseridentity', '');

        // Use job that does have fullname, cannot view email address.
        $returnedstring = totara_job_display_user_job($user1, $jobassignment_withname1);
        $this->assertEquals('John Smith - Developer', $returnedstring);

        $returnedstring2 = totara_job_display_user_job($user2, $jobassignment_withname2);
        $this->assertEquals(fullname($user2) . ' - Tester', $returnedstring2);

        // Use job that does not have fullname, cannot view email address.
        $returnedstring = totara_job_display_user_job($user1, $jobassignment_noname1);
        $this->assertEquals('John Smith - Unnamed job assignment (ID: 2)', $returnedstring);

        $returnedstring2 = totara_job_display_user_job($user2, $jobassignment_noname2);
        $this->assertEquals(fullname($user2) . ' - Unnamed job assignment (ID: 2a)', $returnedstring2);

        // Use job that does not have fullname, cannot view email address.
        $returnedstring = totara_job_display_user_job($user1, null);
        $this->assertEquals('John Smith - requires job assignment entry', $returnedstring);

        $returnedstring2 = totara_job_display_user_job($user2, null);
        $this->assertEquals(fullname($user2) . ' - requires job assignment entry', $returnedstring2);

        // Do not use any job, cannot view email address.
        $returnedstring = totara_job_display_user_job($user1, null, null, true);
        $this->assertEquals('John Smith - create empty job assignment', $returnedstring);

        $returnedstring2 = totara_job_display_user_job($user2, null, null, true);
        $this->assertEquals(fullname($user2) . ' - create empty job assignment', $returnedstring2);
    }

    public function test_totara_job_can_edit_job_assignments() {
        global $USER, $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Null and empty userid
        $this->setUser($user1);
        $this->assertFalse(totara_job_can_edit_job_assignments(null));
        $this->assertFalse(totara_job_can_edit_job_assignments(0));

        // Not logged in
        $this->setUser();
        $this->assertFalse(totara_job_can_edit_job_assignments($user2->id));

        // Guest user
        $guest = get_guest_role();
        $this->setUser($guest);
        $this->assertFalse(totara_job_can_edit_job_assignments($user2->id));

        $systemcontext = context_system::instance();
        $user2context = context_user::instance($user2->id);
        $user3context = context_user::instance($user3->id);

        // Editing own job assignments
        $this->setUser($user1);
        $this->assertFalse(totara_job_can_edit_job_assignments($user1->id));
        $user_role = $DB->get_record('role', array('shortname'=>'user'), '*', MUST_EXIST);
        assign_capability('totara/hierarchy:assignselfposition', CAP_ALLOW, $user_role->id, $systemcontext->id, true);
        $this->assertTrue(totara_job_can_edit_job_assignments($user1->id));

        // totara/hierarchy:assignuserposition capability
        $sm_role = $DB->get_record('role', array('shortname'=>'staffmanager'), '*', MUST_EXIST);
        role_assign($sm_role->id, $user1->id, $systemcontext->id);
        role_assign($sm_role->id, $user1->id, $user2context->id);
        role_assign($sm_role->id, $user1->id, $user3context->id);

        // systemcontext not taken into consideration anymore (TL-10680)
        assign_capability('totara/hierarchy:assignuserposition', CAP_ALLOW, $sm_role->id, $systemcontext->id, true);
        assign_capability('totara/hierarchy:assignuserposition', CAP_ALLOW, $sm_role->id, $user2context->id, true);
        assign_capability('totara/hierarchy:assignuserposition', CAP_PROHIBIT, $sm_role->id, $user3context->id, true);

        $this->setUser($user1);
        $this->assertTrue(totara_job_can_edit_job_assignments($user2->id));
        $this->assertFalse(totara_job_can_edit_job_assignments($user3->id));

        // Deleted user
        $user2->deleted = 1;
        $user2 = $this->getDataGenerator()->create_user($user2);
        $this->assertFalse(totara_job_can_edit_job_assignments($user2->id));
    }

    public function test_totara_job_can_view_job_assignments() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $invuser = new stdClass();
        $deluser = $this->getDataGenerator()->create_user(array('deleted'=>1));
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Null and empty userid
        $this->setUser($user1);
        $this->assertFalse(totara_job_can_view_job_assignments($invuser));
        $invuser->id = '';
        $this->assertFalse(totara_job_can_view_job_assignments($invuser));

        // Not logged in
        $this->setUser();
        $this->assertFalse(totara_job_can_view_job_assignments($user2));

        // Guest user
        $guest = get_guest_role();
        $this->setUser($guest);
        $this->assertFalse(totara_job_can_view_job_assignments($user2));

        // Deleted user
        $this->setUser($user1);
        $this->assertFalse(totara_job_can_view_job_assignments($deluser));

        $systemcontext = context_system::instance();
        $user2context = context_user::instance($user2->id);
        $roleid = $this->getDataGenerator()->create_role([]);
        $coursecontext = context_course::instance($course->id);
        role_assign($roleid, $user1->id, $coursecontext);

        // Course access
        $this->assertFalse(totara_job_can_view_job_assignments($user2, $course));

        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $coursecontext->id, true);
        $this->assertTrue(totara_job_can_view_job_assignments($user2, $course));

        $roleidx = $this->getDataGenerator()->create_role([]);
        role_assign($roleidx, $user1->id, $user2context);
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $roleidx, $user2context->id, true);
        assign_capability('moodle/user:viewdetails', CAP_INHERIT, $roleid, $coursecontext->id, true);
        $this->assertTrue(totara_job_can_view_job_assignments($user2, $course));

        // Can view outside of a course, as they have a course in common still.
        $this->assertTrue(totara_job_can_view_job_assignments($user2));

        // Reset the caps.
        assign_capability('moodle/user:viewalldetails', CAP_INHERIT, $roleidx, $user2context->id, true);
        $this->assertFalse(totara_job_can_view_job_assignments($user2));
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleidx, $user2context->id, true);
        $this->assertTrue(totara_job_can_view_job_assignments($user2));
    }
}
