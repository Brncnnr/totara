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
 * Test non-plugin enrollib parts.
 *
 * @package    core_enrol
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Test non-plugin enrollib parts.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_enrollib_testcase extends advanced_testcase {

    public function test_enrol_get_all_users_courses() {
        global $DB, $CFG;


        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->assertNotEmpty($teacherrole);

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category(array('visible'=>0));
        $category2 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(array('category'=>$category1->id));
        $course2 = $this->getDataGenerator()->create_course(array('category'=>$category2->id));
        $course3 = $this->getDataGenerator()->create_course(array('category'=>$category2->id, 'visible'=>0));
        $course4 = $this->getDataGenerator()->create_course(array('category'=>$category2->id));

        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, array('id'=>$maninstance1->id));
        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid'=>$course2->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid'=>$course3->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance4 = $DB->get_record('enrol', array('courseid'=>$course4->id, 'enrol'=>'manual'), '*', MUST_EXIST);

        $manual = enrol_get_plugin('manual');
        $this->assertNotEmpty($manual);

        $manual->enrol_user($maninstance1, $user1->id, $teacherrole->id);
        $manual->enrol_user($maninstance1, $user2->id, $studentrole->id);
        $manual->enrol_user($maninstance1, $user4->id, $teacherrole->id, 0, 0, ENROL_USER_SUSPENDED);
        $manual->enrol_user($maninstance1, $admin->id, $studentrole->id);

        $manual->enrol_user($maninstance2, $user1->id);
        $manual->enrol_user($maninstance2, $user2->id);
        $manual->enrol_user($maninstance2, $user3->id, 0, 1, time()+(60*60));

        $manual->enrol_user($maninstance3, $user1->id);
        $manual->enrol_user($maninstance3, $user2->id);
        $manual->enrol_user($maninstance3, $user3->id, 0, 1, time()-(60*60));
        $manual->enrol_user($maninstance3, $user4->id, 0, 0, 0, ENROL_USER_SUSPENDED);


        $courses = enrol_get_all_users_courses($CFG->siteguest);
        $this->assertSame(array(), $courses);

        $courses = enrol_get_all_users_courses(0);
        $this->assertSame(array(), $courses);

        // Results are sorted by visibility, sortorder by default (in our case order of creation)

        $courses = enrol_get_all_users_courses($admin->id);
        $this->assertCount(1, $courses);
        $this->assertEquals(array($course1->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($admin->id, true);
        $this->assertCount(0, $courses);
        $this->assertEquals(array(), array_keys($courses));

        $courses = enrol_get_all_users_courses($user1->id);
        $this->assertCount(3, $courses);
        $this->assertEquals(array($course2->id, $course1->id, $course3->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user1->id, true);
        // Totara: TODO TL-8001 Moodle has 2 here, why?
        //$this->assertCount(2, $courses);
        //$this->assertEquals(array($course2->id, $course3->id), array_keys($courses));
        $this->assertCount(1, $courses);
        $this->assertEquals(array($course2->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user2->id);
        $this->assertCount(3, $courses);
        $this->assertEquals(array($course2->id, $course1->id, $course3->id), array_keys($courses));

        // Totara: TODO TL-8001 Moodle has 2 here, why?
        $courses = enrol_get_all_users_courses($user2->id, true);
        //$this->assertCount(2, $courses);
        //$this->assertEquals(array($course2->id, $course3->id), array_keys($courses));
        $this->assertCount(1, $courses);
        $this->assertEquals(array($course2->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user3->id);
        $this->assertCount(2, $courses);
        $this->assertEquals(array($course2->id, $course3->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user3->id, true);
        $this->assertCount(1, $courses);
        $this->assertEquals(array($course2->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user4->id);
        $this->assertCount(2, $courses);
        $this->assertEquals(array($course1->id, $course3->id), array_keys($courses));

        $courses = enrol_get_all_users_courses($user4->id, true);
        $this->assertCount(0, $courses);
        $this->assertEquals(array(), array_keys($courses));

        // Make sure sorting and columns work.

        $basefields = array('id', 'category', 'sortorder', 'shortname', 'fullname', 'idnumber',
            'startdate', 'visible', 'groupmode', 'groupmodeforce', 'defaultgroupingid');

        $courses = enrol_get_all_users_courses($user2->id, true);
        $course = reset($courses);
        context_helper::preload_from_record($course);
        $course = (array)$course;
        $this->assertEqualsCanonicalizing($basefields, array_keys($course));

        $courses = enrol_get_all_users_courses($user2->id, false, 'timecreated');
        $course = reset($courses);
        $this->assertTrue(property_exists($course, 'timecreated'));

        $courses = enrol_get_all_users_courses($user2->id, false, null, 'id DESC');
        $this->assertEquals(array($course3->id, $course2->id, $course1->id), array_keys($courses));
    }

    /**
     * Test enrol_course_delete() without passing a user id. When a value for user id is not present, the method
     * should delete all enrolment related data in the course.
     */
    public function test_enrol_course_delete_without_userid() {
        global $DB;

        $this->resetAfterTest();

        // Create users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $manual = enrol_get_plugin('manual');
        $manualinstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        // Enrol user1 as a student in the course using manual enrolment.
        $manual->enrol_user($manualinstance, $user1->id, $studentrole->id);

        $self = enrol_get_plugin('self');
        $selfinstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'self'], '*', MUST_EXIST);
        $self->update_status($selfinstance, ENROL_INSTANCE_ENABLED);
        // Enrol user2 as a student in the course using self enrolment.
        $self->enrol_user($selfinstance, $user2->id, $studentrole->id);

        // Delete all enrolment related records in the course.
        enrol_course_delete($course);

        // The course enrolment of user1 should not exists.
        $user1enrolment = $DB->get_record('user_enrolments',
            ['enrolid' => $manualinstance->id, 'userid' => $user1->id]);
        $this->assertFalse($user1enrolment);

        // The role assignment of user1 should not exists.
        $user1roleassignment = $DB->get_record('role_assignments',
            ['roleid' => $studentrole->id, 'userid'=> $user1->id, 'contextid' => $coursecontext->id]
        );
        $this->assertFalse($user1roleassignment);

        // The course enrolment of user2 should not exists.
        $user2enrolment = $DB->get_record('user_enrolments',
            ['enrolid' => $selfinstance->id, 'userid' => $user2->id]);
        $this->assertFalse($user2enrolment);

        // The role assignment of user2 should not exists.
        $user2roleassignment = $DB->get_record('role_assignments',
            ['roleid' => $studentrole->id, 'userid'=> $user2->id, 'contextid' => $coursecontext->id]);
        $this->assertFalse($user2roleassignment);

        // All existing course enrolment instances should not exists.
        $enrolmentinstances = enrol_get_instances($course->id, false);
        $this->assertCount(0, $enrolmentinstances);
    }

    /**
     * Test enrol_course_delete() when user id is present.
     * When a value for user id is present, the method should make sure the user has the proper capability to
     * un-enrol users before removing the enrolment data. If the capabilities are missing the data should not be removed.
     *
     * @dataProvider enrol_course_delete_with_userid_provider
     * @param array $excludedcapabilities The capabilities that should be excluded from the user's role
     * @param bool $expected The expected results
     */
    public function test_enrol_course_delete_with_userid($excludedcapabilities, $expected) {
        global $DB;

        $this->resetAfterTest();
        // Create users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $manual = enrol_get_plugin('manual');
        $manualinstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'],
            '*', MUST_EXIST);
        // Enrol user1 as a student in the course using manual enrolment.
        $manual->enrol_user($manualinstance, $user1->id, $studentrole->id);
        // Enrol user3 as an editing teacher in the course using manual enrolment.
        // By default, the editing teacher role has the capability to un-enroll users which have been enrolled using
        // the existing enrolment methods.
        $manual->enrol_user($manualinstance, $user3->id, $editingteacherrole->id);

        $self = enrol_get_plugin('self');
        $selfinstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'self'],
            '*', MUST_EXIST);
        $self->update_status($selfinstance, ENROL_INSTANCE_ENABLED);
        // Enrol user2 as a student in the course using self enrolment.
        $self->enrol_user($selfinstance, $user2->id, $studentrole->id);

        foreach($excludedcapabilities as $capability) {
            // Un-assign the given capability from the editing teacher role.
            unassign_capability($capability, $editingteacherrole->id);
        }

        // Delete only enrolment related records in the course where user3 has the required capability.
        enrol_course_delete($course, $user3->id);

        // Check the existence of the course enrolment of user1.
        $user1enrolmentexists = (bool) $DB->count_records('user_enrolments',
            ['enrolid' => $manualinstance->id, 'userid' => $user1->id]);
        $this->assertEquals($expected['User 1 course enrolment exists'], $user1enrolmentexists);

        // Check the existence of the role assignment of user1 in the course.
        $user1roleassignmentexists = (bool) $DB->count_records('role_assignments',
            ['roleid' => $studentrole->id, 'userid' => $user1->id, 'contextid' => $coursecontext->id]);
        $this->assertEquals($expected['User 1 role assignment exists'], $user1roleassignmentexists);

        // Check the existence of the course enrolment of user2.
        $user2enrolmentexists = (bool) $DB->count_records('user_enrolments',
            ['enrolid' => $selfinstance->id, 'userid' => $user2->id]);
        $this->assertEquals($expected['User 2 course enrolment exists'], $user2enrolmentexists);

        // Check the existence of the role assignment of user2 in the course.
        $user2roleassignmentexists = (bool) $DB->count_records('role_assignments',
            ['roleid' => $studentrole->id, 'userid' => $user2->id, 'contextid' => $coursecontext->id]);
        $this->assertEquals($expected['User 2 role assignment exists'], $user2roleassignmentexists);

        // Check the existence of the course enrolment of user3.
        $user3enrolmentexists = (bool) $DB->count_records('user_enrolments',
            ['enrolid' => $manualinstance->id, 'userid' => $user3->id]);
        $this->assertEquals($expected['User 3 course enrolment exists'], $user3enrolmentexists);

        // Check the existence of the role assignment of user3 in the course.
        $user3roleassignmentexists = (bool) $DB->count_records('role_assignments',
            ['roleid' => $editingteacherrole->id, 'userid' => $user3->id, 'contextid' => $coursecontext->id]);
        $this->assertEquals($expected['User 3 role assignment exists'], $user3roleassignmentexists);

        // Check the existence of the manual enrolment instance in the course.
        $manualinstance = (bool) $DB->count_records('enrol', ['enrol' => 'manual', 'courseid' => $course->id]);
        $this->assertEquals($expected['Manual course enrolment instance exists'], $manualinstance);

        // Check existence of the self enrolment instance in the course.
        $selfinstance = (bool) $DB->count_records('enrol', ['enrol' => 'self', 'courseid' => $course->id]);
        $this->assertEquals($expected['Self course enrolment instance exists'], $selfinstance);
    }

    /**
     * Data provider for test_enrol_course_delete_with_userid().
     *
     * @return array
     */
    public function enrol_course_delete_with_userid_provider() {
        return [
            'The teacher can un-enrol users in a course' =>
                [
                    'excludedcapabilities' => [],
                    'results' => [
                        // Whether certain enrolment related data still exists in the course after the deletion.
                        // When the user has the capabilities to un-enrol users and the enrolment plugins allow manual
                        // unenerolment than all course enrolment data should be removed.
                        'Manual course enrolment instance exists' => false,
                        'Self course enrolment instance exists' => false,
                        'User 1 course enrolment exists' => false,
                        'User 1 role assignment exists' => false,
                        'User 2 course enrolment exists' => false,
                        'User 2 role assignment exists' => false,
                        'User 3 course enrolment exists' => false,
                        'User 3 role assignment exists' => false
                    ],
                ],
            'The teacher cannot un-enrol self enrolled users'  =>
                [
                    'excludedcapabilities' => [
                        // Exclude the following capabilities for the editing teacher.
                        'enrol/self:unenrol'
                    ],
                    'results' => [
                        // When the user does not have the capabilities to un-enrol self enrolled users, the data
                        // related to this enrolment method should not be removed. Everything else should be removed.
                        'Manual course enrolment instance exists' => false,
                        'Self course enrolment instance exists' => true,
                        'User 1 course enrolment exists' => false,
                        'User 1 role assignment exists' => false,
                        'User 2 course enrolment exists' => true,
                        'User 2 role assignment exists' => true,
                        'User 3 course enrolment exists' => false,
                        'User 3 role assignment exists' => false
                    ],
                ],
            'The teacher cannot un-enrol self and manually enrolled users' =>
                [
                    'excludedcapabilities' => [
                        // Exclude the following capabilities for the editing teacher.
                        'enrol/manual:unenrol',
                        'enrol/self:unenrol'
                    ],
                    'results' => [
                        // When the user does not have the capabilities to un-enrol self and manually enrolled users,
                        // the data related to these enrolment methods should not be removed.
                        'Manual course enrolment instance exists' => true,
                        'Self course enrolment instance exists' => true,
                        'User 1 course enrolment exists' => true,
                        'User 1 role assignment exists' => true,
                        'User 2 course enrolment exists' => true,
                        'User 2 role assignment exists' => true,
                        'User 3 course enrolment exists' => true,
                        'User 3 role assignment exists' => true
                    ],
                ],
        ];
    }

    public function test_enrol_user_sees_own_courses() {
        global $DB, $CFG;


        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->assertNotEmpty($teacherrole);

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category(array('visible'=>0));
        $category2 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(array('category'=>$category1->id));
        $course2 = $this->getDataGenerator()->create_course(array('category'=>$category2->id));
        $course3 = $this->getDataGenerator()->create_course(array('category'=>$category2->id, 'visible'=>0));
        $course4 = $this->getDataGenerator()->create_course(array('category'=>$category2->id));

        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, array('id'=>$maninstance1->id));
        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid'=>$course2->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid'=>$course3->id, 'enrol'=>'manual'), '*', MUST_EXIST);
        $maninstance4 = $DB->get_record('enrol', array('courseid'=>$course4->id, 'enrol'=>'manual'), '*', MUST_EXIST);

        $manual = enrol_get_plugin('manual');
        $this->assertNotEmpty($manual);

        $manual->enrol_user($maninstance1, $admin->id, $studentrole->id);

        $manual->enrol_user($maninstance3, $user1->id, $teacherrole->id);

        $manual->enrol_user($maninstance2, $user2->id, $studentrole->id);

        $manual->enrol_user($maninstance1, $user3->id, $studentrole->id, 1, time()+(60*60));
        $manual->enrol_user($maninstance2, $user3->id, 0, 1, time()-(60*60));
        $manual->enrol_user($maninstance3, $user2->id, $studentrole->id);
        $manual->enrol_user($maninstance4, $user2->id, 0, 0, 0, ENROL_USER_SUSPENDED);

        $manual->enrol_user($maninstance1, $user4->id, $teacherrole->id, 0, 0, ENROL_USER_SUSPENDED);
        $manual->enrol_user($maninstance3, $user4->id, 0, 0, 0, ENROL_USER_SUSPENDED);


        $this->assertFalse(enrol_user_sees_own_courses($CFG->siteguest));
        $this->assertFalse(enrol_user_sees_own_courses(0));
        $this->assertFalse(enrol_user_sees_own_courses($admin));
        $this->assertFalse(enrol_user_sees_own_courses(-222)); // Nonexistent user.

        $this->assertTrue(enrol_user_sees_own_courses($user1));
        $this->assertTrue(enrol_user_sees_own_courses($user2->id));
        $this->assertFalse(enrol_user_sees_own_courses($user3->id));
        $this->assertFalse(enrol_user_sees_own_courses($user4));
        $this->assertFalse(enrol_user_sees_own_courses($user5));

        $this->setAdminUser();
        $this->assertFalse(enrol_user_sees_own_courses());

        $this->setGuestUser();
        $this->assertFalse(enrol_user_sees_own_courses());

        $this->setUser(0);
        $this->assertFalse(enrol_user_sees_own_courses());

        $this->setUser($user1);
        $this->assertTrue(enrol_user_sees_own_courses());

        $this->setUser($user2);
        $this->assertTrue(enrol_user_sees_own_courses());

        $this->setUser($user3);
        $this->assertFalse(enrol_user_sees_own_courses());

        $this->setUser($user4);
        $this->assertFalse(enrol_user_sees_own_courses());

        $this->setUser($user5);
        $this->assertFalse(enrol_user_sees_own_courses());

        $user1 = $DB->get_record('user', array('id'=>$user1->id));
        $this->setUser($user1);
        $reads = $DB->perf_get_reads();
        $this->assertTrue(enrol_user_sees_own_courses());
        $this->assertGreaterThan($reads, $DB->perf_get_reads());

        $user1 = $DB->get_record('user', array('id'=>$user1->id));
        $this->setUser($user1);
        require_login($course3);
        $reads = $DB->perf_get_reads();
        $this->assertTrue(enrol_user_sees_own_courses());
        $this->assertEquals($reads, $DB->perf_get_reads());
    }

    public function test_enrol_get_shared_courses() {

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);

        // Test that user1 and user2 have courses in common.
        $this->assertTrue(enrol_get_shared_courses($user1, $user2, false, true));
        // Test that user1 and user3 have no courses in common.
        $this->assertFalse(enrol_get_shared_courses($user1, $user3, false, true));

        // Test retrieving the courses in common.
        $sharedcourses = enrol_get_shared_courses($user1, $user2, true);

        // Only should be one shared course.
        $this->assertCount(1, $sharedcourses);
        $sharedcourse = array_shift($sharedcourses);
        // It should be course 1.
        $this->assertEquals($sharedcourse->id, $course1->id);
    }

    public function test_enrol_get_shared_courses_different_methods() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/enrol/self/externallib.php');


        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();

        // Enrol user1 and user2 in course1 with a different enrolment methode.
        // Add self enrolment method for course1.
        $selfplugin = enrol_get_plugin('self');
        $this->assertNotEmpty($selfplugin);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);

        $instance1id = $selfplugin->add_instance($course1, array('status' => ENROL_INSTANCE_ENABLED,
                                                                 'name' => 'Test instance 1',
                                                                 'customint6' => 1,
                                                                 'roleid' => $studentrole->id));

        $instance1 = $DB->get_record('enrol', array('id' => $instance1id), '*', MUST_EXIST);

        self::setUser($user2);
        // Self enrol me (user2).
        $result = enrol_self_external::enrol_user($course1->id);

        // Enrol user1 manually.
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, null, 'manual');

        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);

        $course3 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user2->id, $course3->id);

        // Test that user1 and user2 have courses in common.
        $this->assertTrue(enrol_get_shared_courses($user1, $user2, false, true));
        // Test that user1 and user3 have no courses in common.
        $this->assertFalse(enrol_get_shared_courses($user1, $user3, false, true));

        // Test retrieving the courses in common.
        $sharedcourses = enrol_get_shared_courses($user1, $user2, true);

        // Only should be one shared course.
        $this->assertCount(1, $sharedcourses);
        $sharedcourse = array_shift($sharedcourses);
        // It should be course 1.
        $this->assertEquals($sharedcourse->id, $course1->id);
    }

    /**
     * Test user enrolment created event.
     */
    public function test_user_enrolment_created_event() {
        global $DB;


        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);

        $admin = get_admin();

        $course1 = $this->getDataGenerator()->create_course();

        $maninstance1 = $DB->get_record('enrol', array('courseid'=>$course1->id, 'enrol'=>'manual'), '*', MUST_EXIST);

        $manual = enrol_get_plugin('manual');
        $this->assertNotEmpty($manual);

        // Enrol user and capture event.
        $sink = $this->redirectEvents();
        $manual->enrol_user($maninstance1, $admin->id, $studentrole->id);
        $events = $sink->get_events();
        $sink->close();
        $event = array_shift($events);

        $dbuserenrolled = $DB->get_record('user_enrolments', array('userid' => $admin->id));
        $this->assertInstanceOf('\core\event\user_enrolment_created', $event);
        $this->assertEquals($dbuserenrolled->id, $event->objectid);
        $this->assertEquals(context_course::instance($course1->id), $event->get_context());
        $this->assertEquals('user_enrolled', $event->get_legacy_eventname());
        $expectedlegacyeventdata = $dbuserenrolled;
        $expectedlegacyeventdata->enrol = $manual->get_name();
        $expectedlegacyeventdata->courseid = $course1->id;
        $this->assertEventLegacyData($expectedlegacyeventdata, $event);
        $expected = array($course1->id, 'course', 'enrol', '../enrol/users.php?id=' . $course1->id, $course1->id);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test user_enrolment_deleted event.
     */
    public function test_user_enrolment_deleted_event() {
        global $DB;


        $manualplugin = enrol_get_plugin('manual');
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $student = $DB->get_record('role', array('shortname' => 'student'));

        $enrol = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'), '*', MUST_EXIST);

        // Enrol user.
        $manualplugin->enrol_user($enrol, $user->id, $student->id);

        // Get the user enrolment information, used to validate legacy event data.
        $dbuserenrolled = $DB->get_record('user_enrolments', array('userid' => $user->id));

        // Unenrol user and capture event.
        $sink = $this->redirectEvents();
        $manualplugin->unenrol_user($enrol, $user->id);
        $events = $sink->get_events();
        $sink->close();
        $event = array_pop($events);

        // Validate the event.
        $this->assertInstanceOf('\core\event\user_enrolment_deleted', $event);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());
        $this->assertEquals('user_unenrolled', $event->get_legacy_eventname());
        $expectedlegacyeventdata = $dbuserenrolled;
        $expectedlegacyeventdata->enrol = $manualplugin->get_name();
        $expectedlegacyeventdata->courseid = $course->id;
        $expectedlegacyeventdata->lastenrol = true;
        $this->assertEventLegacyData($expectedlegacyeventdata, $event);
        $expected = array($course->id, 'course', 'unenrol', '../enrol/users.php?id=' . $course->id, $course->id);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test enrol_instance_created, enrol_instance_updated and enrol_instance_deleted events.
     */
    public function test_instance_events() {
        global $DB;


        $selfplugin = enrol_get_plugin('self');
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $course = $this->getDataGenerator()->create_course();

        // Creating enrol instance.
        $sink = $this->redirectEvents();
        $instanceid = $selfplugin->add_instance($course, array('status' => ENROL_INSTANCE_ENABLED,
                                                                'name' => 'Test instance 1',
                                                                'customint6' => 1,
                                                                'roleid' => $studentrole->id));
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = array_pop($events);
        $this->assertInstanceOf('\core\event\enrol_instance_created', $event);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());
        $this->assertEquals('self', $event->other['enrol']);
        $this->assertEventContextNotUsed($event);

        // Updating enrol instance.
        $instance = $DB->get_record('enrol', array('id' => $instanceid));
        $sink = $this->redirectEvents();
        $selfplugin->update_status($instance, ENROL_INSTANCE_DISABLED);

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = array_pop($events);
        $this->assertInstanceOf('\core\event\enrol_instance_updated', $event);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());
        $this->assertEquals('self', $event->other['enrol']);
        $this->assertEventContextNotUsed($event);

        // Deleting enrol instance.
        $instance = $DB->get_record('enrol', array('id' => $instanceid));
        $sink = $this->redirectEvents();
        $selfplugin->delete_instance($instance);

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = array_pop($events);
        $this->assertInstanceOf('\core\event\enrol_instance_deleted', $event);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());
        $this->assertEquals('self', $event->other['enrol']);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test the core_enrol_get_all_user_enrolments_in_course function.
     */
    public function test_core_enrol_get_all_user_enrolments_in_course() {
        global $DB, $CFG;


        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Create users.
        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Create categories.
        $category1 = $this->getDataGenerator()->create_category();

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course(array('category' => $category1->id));
        $course2 = $this->getDataGenerator()->create_course(array('category' => $category1->id));
        $course3 = $this->getDataGenerator()->create_course(array('category' => $category1->id));
        $course4 = $this->getDataGenerator()->create_course(array('category' => $category1->id));
        $course5 = $this->getDataGenerator()->create_course(array('category' => $category1->id));

        // Manual enrol instances.
        $maninstance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance4 = $DB->get_record('enrol', array('courseid' => $course4->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance5 = $DB->get_record('enrol', array('courseid' => $course5->id, 'enrol' => 'manual'), '*', MUST_EXIST);

        // Self enrol instances.
        $selfinstance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'self'), '*', MUST_EXIST);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, array('id' => $selfinstance1->id));
        $selfinstance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'self'), '*', MUST_EXIST);

        // Enrol plugins.
        $manual = enrol_get_plugin('manual');
        $this->assertNotEmpty($manual);
        $self = enrol_get_plugin('self');
        $this->assertNotEmpty($self);

        // Users have no enrolments.
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($CFG->siteguest, $course1->id);
        $this->assertSame(array(), $user_enrolments);
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($admin->id, $course1->id);
        $this->assertSame(array(), $user_enrolments);
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user1->id, $course1->id);
        $this->assertSame(array(), $user_enrolments);
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user2->id, $course1->id);
        $this->assertSame(array(), $user_enrolments);

        // Users have manual enrolments.
        $manual->enrol_user($maninstance1, $user1->id, $studentrole->id);
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user1->id, $course1->id);
        $this->assertCount(1, $user_enrolments);
        $this->assertEquals($user1->id, current($user_enrolments)->userid);

        $manual->enrol_user($maninstance1, $user2->id, $studentrole->id);
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user2->id, $course1->id);
        $this->assertCount(1, $user_enrolments);
        $this->assertEquals($user2->id, current($user_enrolments)->userid);

        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user2->id, $course2->id);
        $this->assertSame(array(), $user_enrolments);

        // User1 has manual enrolment which is suspended.
        $manual->enrol_user($maninstance2, $user1->id, 0, 0, 0, ENROL_USER_SUSPENDED);
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user1->id, $course2->id);
        $this->assertSame(array(), $user_enrolments);

        // User1 has manual enrolment which has ended.
        $manual->enrol_user($maninstance3, $user1->id, 0, 1, time() - (60 * 60));
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user1->id, $course3->id);
        $this->assertSame(array(), $user_enrolments);

        // User1 has manual enrolment which is yet to start.
        $manual->enrol_user($maninstance4, $user1->id, 0, time() + (60 * 60), 0);
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user1->id, $course4->id);
        $this->assertSame(array(), $user_enrolments);

        // User1 has manual enrolment within start and end period.
        $manual->enrol_user($maninstance5, $user1->id, 0, time() - (60 * 60), time() + (60 * 60));
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user1->id, $course5->id);
        $this->assertCount(1, $user_enrolments);
        $this->assertEquals($user1->id, current($user_enrolments)->userid);
        $this->assertEquals($course5->id, current($user_enrolments)->courseid);

        // Disable manual enrol instance 1.
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, array('id' => $maninstance1->id));
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user1->id, $course1->id);
        $this->assertSame(array(), $user_enrolments);

        $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, array('id' => $maninstance1->id));

        // User 3 has manual and self enrolment.
        $manual->enrol_user($maninstance1, $user3->id, $studentrole->id);
        $self->enrol_user($selfinstance1, $user3->id, $studentrole->id);
        $user_enrolments = core_enrol_get_all_user_enrolments_in_course($user3->id, $course1->id);
        $user_enrolments = array_values($user_enrolments);
        $this->assertCount(2, $user_enrolments);
        $this->assertEquals($user3->id, $user_enrolments[0]->userid);
        $this->assertEquals($course1->id, $user_enrolments[0]->courseid);
        $this->assertEquals($user3->id, $user_enrolments[1]->userid);
        $this->assertEquals($course1->id, $user_enrolments[1]->courseid);
    }

    /**
     * Confirms that timemodified field was updated after modification of user enrollment
     */
    public function test_enrollment_update_timemodified() {
        global $DB;

        $datagen = $this->getDataGenerator();

        /** @var enrol_manual_plugin $manualplugin */
        $manualplugin = enrol_get_plugin('manual');
        $this->assertNotNull($manualplugin);

        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        $course = $datagen->create_course();
        $user = $datagen->create_user();

        $instanceid = null;
        $instances = enrol_get_instances($course->id, true);
        foreach ($instances as $inst) {
            if ($inst->enrol == 'manual') {
                $instanceid = (int)$inst->id;
                break;
            }
        }
        if (empty($instanceid)) {
            $instanceid = $manualplugin->add_default_instance($course);
            if (empty($instanceid)) {
                $instanceid = $manualplugin->add_instance($course);
            }
        }
        $this->assertNotNull($instanceid);

        $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $user->id, $studentroleid, 0, 0, ENROL_USER_ACTIVE);
        $userenrolorig = (int)$DB->get_field(
            'user_enrolments',
            'timemodified',
            ['enrolid' => $instance->id, 'userid' => $user->id],
            MUST_EXIST
        );
        $this->waitForSecond();
        $this->waitForSecond();
        $manualplugin->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
        $userenrolpost = (int)$DB->get_field(
            'user_enrolments',
            'timemodified',
            ['enrolid' => $instance->id, 'userid' => $user->id],
            MUST_EXIST
        );

        $this->assertGreaterThan($userenrolorig, $userenrolpost);
    }

    /**
     * Test to confirm that enrol_get_my_courses only return the courses that
     * the logged in user is enrolled in.
     */
    public function test_enrol_get_my_courses_only_enrolled_courses() {
        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user->id, $course3->id);
        $this->setUser($user);

        // By default this function should return all of the courses the user
        // is enrolled in.
        $courses = enrol_get_my_courses();

        $this->assertCount(3, $courses);
        $this->assertEquals($course1->id, $courses[$course1->id]->id);
        $this->assertEquals($course2->id, $courses[$course2->id]->id);
        $this->assertEquals($course3->id, $courses[$course3->id]->id);

        // If a set of course ids are provided then the result set will only contain
        // these courses.
        $courseids = [$course1->id, $course2->id];
        $courses = enrol_get_my_courses(['id'], 'visible DESC,sortorder ASC', 0, $courseids);

        $this->assertCount(2, $courses);
        $this->assertEquals($course1->id, $courses[$course1->id]->id);
        $this->assertEquals($course2->id, $courses[$course2->id]->id);

        // If the course ids list contains any ids for courses the user isn't enrolled in
        // then they will be ignored (in this case $course4).
        $courseids = [$course1->id, $course2->id, $course4->id];
        $courses = enrol_get_my_courses(['id'], 'visible DESC,sortorder ASC', 0, $courseids);

        $this->assertCount(2, $courses);
        $this->assertEquals($course1->id, $courses[$course1->id]->id);
        $this->assertEquals($course2->id, $courses[$course2->id]->id);
    }

    /**
     * Different scenarios for testing SQL injection vulnerability with the $sort attribute in enrollib functions.
     *
     * @return array
     */
    public function enrol_get_cleaned_order_by_sql_provider() {
        return array(
            array('id DESC', false), // Valid sort value
            array('c.id ASC', false), // Valid sort value
            array('c.id ASC, fullname DESC', false), // Valid sort value
            array('id FOOBAR', 'Invalid sort direction in $sort parameter in enrol_cleaned_order_by_sql()'), // Invalid sort direction
            array('id DESC foobar', 'Invalid $sort parameter in enrol_cleaned_order_by_sql()'), // Too many params
            array('password', 'Invalid $sort parameter in enrol_cleaned_order_by_sql()'), // Invalid column
            array('foo.bar.id', 'Invalid $sort parameter in enrol_cleaned_order_by_sql()'), // Too many periods
            array('id; SELECT password FROM {user}', 'Invalid $sort parameter in enrol_cleaned_order_by_sql()'), // SQL injection risk
        );
    }

    /**
     * Make sure there isn't any SQL injection vulnerabilities with the sort attribute
     *
     * @dataProvider enrol_get_cleaned_order_by_sql_provider
     * @param string $sort_sql The sort sql string to check
     * @param string|false $expected_exception The expected exception message, or false if there shouldn't be an exception.
     */
    public function test_enrol_get_my_courses_sort_sql_is_sanitized($sort_sql, $expected_exception) {
        $this->setAdminUser();
        if ($expected_exception) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage($expected_exception);
        }
        enrol_get_my_courses(array('id'), $sort_sql);
    }

    /**
     * Make sure there isn't any SQL injection vulnerabilities with the sort attribute
     *
     * @dataProvider enrol_get_cleaned_order_by_sql_provider
     * @param string $sort_sql The sort sql string to check
     * @param string|false $expected_exception The expected exception message, or false if there shouldn't be an exception.
     */
    public function test_enrol_get_all_users_courses_sort_sql_is_sanitized($sort_sql, $expected_exception) {
        $this->setAdminUser();
        if ($expected_exception) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage($expected_exception);
        }
        enrol_get_all_users_courses($this->getDataGenerator()->create_user()->id, false, array('id'), $sort_sql);
    }

    /**
     * Assert that deleted instances do not get returned by enrol_get_instances
     *
     * @return void
     */
    public function test_enrol_get_instances_excludes_deleted(): void {
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, null, 'self');

        $instances = enrol_get_instances($course1->id, false);
        $instance_by_keys = [];
        foreach ($instances as $instance) {
            $instance_by_keys[$instance->enrol] = $instance;
        }
        $this->assertArrayHasKey('self', $instance_by_keys);

        // Delete the self option
        $self_plugin = enrol_get_plugin('self');
        $self_plugin->update_status($instance_by_keys['self'], ENROL_INSTANCE_DELETED);

        $instances = enrol_get_instances($course1->id, false);
        $instance_by_keys = [];
        foreach ($instances as $instance) {
            $instance_by_keys[$instance->enrol] = $instance;
        }
        $this->assertArrayNotHasKey('self', $instance_by_keys);

        $instances = enrol_get_instances($course1->id, true);
        $instance_by_keys = [];
        foreach ($instances as $instance) {
            $instance_by_keys[$instance->enrol] = $instance;
        }
        $this->assertArrayNotHasKey('self', $instance_by_keys);
    }

    /**
     * @return void
     */
    public function test_enrol_get_non_completed_courses_by_userid(): void {
        global $CFG;

        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $program_plugin = enrol_get_plugin('totara_program');
        $program_plugin->add_instance($course1);
        $this->getDataGenerator()->enrol_user($user->id, $course1->id,null,'totara_program');
        $program_plugin->add_instance($course4);
        $this->getDataGenerator()->enrol_user($user->id, $course4->id,null,'totara_program');
        $program_plugin->add_instance($course5);
        $this->getDataGenerator()->enrol_user($user->id, $course5->id,null,'totara_program');

        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user->id, $course3->id);
        $this->getDataGenerator()->enrol_user($user->id, $course4->id);

        $completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $completion_generator->enable_completion_tracking($course1);
        $completion_generator->complete_course($course1, $user);

        $courses = enrol_get_all_users_courses($user->id);

        $ids = array_map(function ($course) {
            return $course->id;
        }, $courses);
        self::assertCount(5, $courses);
        self::assertTrue(in_array($course1->id, $ids));
        self::assertTrue(in_array($course2->id, $ids));
        self::assertTrue(in_array($course3->id, $ids));
        self::assertTrue(in_array($course4->id, $ids));
        self::assertTrue(in_array($course5->id, $ids));

        $non_completed_courses = enrol_get_non_completed_courses_by_userid(
            $user->id,
            true,
            ['id'],
            'visible DESC,sortorder ASC',
            0,
            0,
            'totara_program'
        );
        $ids = array_map(function ($non_completed_course) {
            return $non_completed_course->id;
        }, $non_completed_courses);

        // Course5 and course1 should not be returned.
        self::assertCount(3, $non_completed_courses);
        self::assertFalse(in_array($course1->id, $ids));
        self::assertFalse(in_array($course5->id, $ids));
        self::assertTrue(in_array($course2->id, $ids));
        self::assertTrue(in_array($course3->id, $ids));
        self::assertTrue(in_array($course4->id, $ids));

        // Disable completion on site level so the user has 4 courses.
        $CFG->enablecompletion = false;
        $courses = enrol_get_non_completed_courses_by_userid(
            $user->id,
            true,
            ['id'],
            'visible DESC,sortorder ASC',
            0,
            0,
            'totara_program'
        );

        $ids = array_map(function ($course) {
            return $course->id;
        }, $courses);

        self::assertCount(4, $courses);
        self::assertTrue(in_array($course1->id, $ids));
        self::assertFalse(in_array($course5->id, $ids));
        self::assertTrue(in_array($course2->id, $ids));
        self::assertTrue(in_array($course3->id, $ids));
        self::assertTrue(in_array($course4->id, $ids));

        // Disable completion on course level so the user has 4 courses.
        $CFG->enablecompletion = true;
        $completion_generator->disable_completion_tracking($course1);

        $courses = enrol_get_non_completed_courses_by_userid(
            $user->id,
            true,
            ['id'],
            'visible DESC,sortorder ASC',
            0,
            0,
            'totara_program'
        );

        $ids = array_map(function ($course) {
            return $course->id;
        }, $courses);

        self::assertCount(4, $courses);
        self::assertTrue(in_array($course1->id, $ids));
        self::assertFalse(in_array($course5->id, $ids));
        self::assertTrue(in_array($course2->id, $ids));
        self::assertTrue(in_array($course3->id, $ids));
        self::assertTrue(in_array($course4->id, $ids));
    }
}