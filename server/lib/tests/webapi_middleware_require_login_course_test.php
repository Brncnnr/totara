<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 * @category test
 */

use core\webapi\middleware\require_login_course;
use core\webapi\execution_context;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use totara_core\advanced_feature;

/**
 * @coversDefaultClass \core\webapi\middleware\require_login_course
 *
 * @group core_webapi
 */
class core_webapi_middleware_require_login_course_testcase extends advanced_testcase {

    /**
     * Setup a program with courses to test the totara_program enrolment plugin.
     *
     * @param bool $enable - optionally enable the plugin
     */
    private function setup_prog_auto_enrolments(bool $enable = true) {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        if ($enable) {
            // Make sure the totara_program enrolment
            $enabled = enrol_get_plugins(true);
            $enabled['totara_program'] = true;
            $enabled = array_keys($enabled);
            set_config('enrol_plugins_enabled', implode(',', $enabled));
        }

        // First up is the program enrolment plugin.
        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();

        $program = $prog_gen->create_program(['shortname' => 'prg1', 'fullname' => 'program1', 'summary' => 'first program']);
        $prog_gen->add_courses_and_courseset_to_program($program, [[$c1], [$c2]], CERTIFPATH_STD);
        $prog_gen->assign_program($program->id, [$user->id]);

        return [$user, $program, $c1, $c2];
    }

    /**
     * Setup a learning plan with courses to test the totara_program enrolment plugin.
     *
     * @param bool $enable - optionally enable the plugin
     */
    private function setup_plan_auto_enrolments(bool $enable = true) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/development_plan.class.php');

        $user = $this->getDataGenerator()->create_user();

        if ($enable) {
            // Make sure the totara_program enrolment
            $enabled = enrol_get_plugins(true);
            $enabled['totara_learningplan'] = true;
            $enabled = array_keys($enabled);
            set_config('enrol_plugins_enabled', implode(',', $enabled));
        }

        $plan_gen = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();

        $planrecord = $plan_gen->create_learning_plan(array('userid' => $user->id));

        $plan = new development_plan($planrecord->id);

        $this->setAdminUser(); // This course is approved.
        $plan_gen->add_learning_plan_course($plan->id, $c1->id);

        $plan->set_status(DP_PLAN_STATUS_APPROVED);


        $this->setUser($user); // This course is pending approval.
        $plan_gen->add_learning_plan_course($plan->id, $c2->id);

        return [$user, $plan, $c1, $c2];
    }

    /**
     * @covers ::handle
     */
    public function test_require_login_course(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course);
        $this->setUser($user);

        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };

        $context = execution_context::create("dev");

        // Test with single key.
        $id_key = 'abc';
        $single_key_args = [$id_key => $course->id];
        $single_key_payload = payload::create($single_key_args, $context);

        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');

        // Test with wrong key.
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Invalid course');
        $require = new require_login_course('foo', false);
        $require->handle($single_key_payload, $next);
    }

    /**
     * There is no auto enrol plugin associated with this, like programs or learning plans,  so we expect it to fail.
     */
    public function test_course_auto_enrolment() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($user);

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $course->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

    /**
     * Check that a user is auto-enrolled in a course if they are enrolled on a program
     * with an accessible courseset containing the course.
     */
    public function test_program_successful_auto_enrolment () {
        global $DB;
        list($user, $program, $c1, $c2) = $this->setup_prog_auto_enrolments();

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->assertEmpty($DB->get_records('user_enrolments'));

        $single_key_args = [$id_key => $c1->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);

        // There should now be 1 enrolment.
        $enrolments = $DB->get_records('user_enrolments');
        $this->assertCount(1, $enrolments);

        // Just double check that enrolment is for the right user in the right course
        $enrolment = array_shift($enrolments);
        $this->assertEquals($user->id, $enrolment->userid);
        $this->assertEquals($c1->id, $DB->get_field('enrol', 'courseid', ['id' => $enrolment->enrolid]));
    }

    /**
     * Set up the program, then disable the feature for the course and attempt to auto enrol.
     */
    public function test_program_disabled_feature_auto_enrolment() {
        list($user, $program, $c1, $c2) = $this->setup_prog_auto_enrolments();

        set_config('enableprograms', advanced_feature::DISABLED);
        set_config('enablecertifications', advanced_feature::DISABLED);

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $c1->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

    /**
     * Set up the program, but make sure the enrolment plugin is disabled for the course and attempt to auto enrol.
     */
    public function test_program_disabled_plugin_auto_enrolment() {
        list($user, $program, $c1, $c2) = $this->setup_prog_auto_enrolments(false);

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $c1->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

    /**
     * Set up the program, then attempt to auto enrol in an unassociated course.
     */
    public function test_program_unassociated_auto_enrolment() {
        list($user, $program, $c1, $c2) = $this->setup_prog_auto_enrolments();
        $course = $this->getDataGenerator()->create_course();

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $course->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

    /**
     * Set up the program, then attempt to auto enrol as a user not assigned to that program.
     */
    public function test_program_unassigned_auto_enrolment() {
        list($user, $program, $c1, $c2) = $this->setup_prog_auto_enrolments();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $c1->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

    /**
     * Set up the program, then as the assigned user attempt to auto enrol in a course contained
     * in an unavailable (future) courseset.
     */
    public function test_program_unavailable_auto_enrolment() {
        list($user, $program, $c1, $c2) = $this->setup_prog_auto_enrolments();

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $c2->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

    /**
     * Check that a user is auto-enrolled in a course if they have an approved learning plan
     * containing the course.
     */
    public function test_plan_successful_auto_enrolment () {
        global $DB;
        list($user, $plan, $c1, $c2) = $this->setup_plan_auto_enrolments();

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->assertEmpty($DB->get_records('user_enrolments'));

        $single_key_args = [$id_key => $c1->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);

        // There should now be 1 enrolment.
        $enrolments = $DB->get_records('user_enrolments');
        $this->assertCount(1, $enrolments);

        // Just double check that enrolment is for the right user in the right course
        $enrolment = array_shift($enrolments);
        $this->assertEquals($user->id, $enrolment->userid);
        $this->assertEquals($c1->id, $DB->get_field('enrol', 'courseid', ['id' => $enrolment->enrolid]));
    }

    /**
     * Set up the plan, then disable the feature for the course and attempt to auto enrol.
     */
    public function test_plan_disabled_feature_auto_enrolment() {
        list($user, $plan, $c1, $c2) = $this->setup_plan_auto_enrolments();

        set_config('enablelearningplans', advanced_feature::DISABLED);

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $c1->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

    /**
     * Set up the plan, but make sure the enrolment plugin is disabled for the course and attempt to auto enrol.
     */
    public function test_plan_disabled_plugin_auto_enrolment() {
        list($user, $plan, $c1, $c2) = $this->setup_plan_auto_enrolments(false);

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $c1->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

    /**
     * Set up the plan, then attempt to auto enrol in an unassociated course.
     */
    public function test_plan_unassociated_auto_enrolment() {
        list($user, $plan, $c1, $c2) = $this->setup_plan_auto_enrolments();
        $course = $this->getDataGenerator()->create_course();

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $course->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

    /**
     * Set up the plan, then attempt to auto enrol as a different user.
     */
    public function test_plan_unassigned_auto_enrolment() {
        list($user, $plan, $c1, $c2) = $this->setup_plan_auto_enrolments();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $c1->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

    /**
     * Set up the plan, then as the assigned user attempt to auto enrol in an unapproved course
     */
    public function test_plan_unapproved_auto_enrolment() {
        list($user, $plan, $c1, $c2) = $this->setup_plan_auto_enrolments();

        $id_key = 'courseid';
        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create("dev");

        $this->expectException(\require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $single_key_args = [$id_key => $c2->id];
        $single_key_payload = payload::create($single_key_args, $context);
        $require = new require_login_course($id_key, false);
        $result = $require->handle($single_key_payload, $next);
    }

}
