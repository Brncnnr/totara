<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_criteria
 * @subpackage test
 */

use core\webapi\execution_context;
use core\webapi\query_resolver;
use core_course\user_learning\item;
use totara_criteria\criterion;
use totara_criteria\criterion_not_found_exception;
use totara_program\task\completions_task;

defined('MOODLE_INTERNAL') || die();


/**
 * Tests the query to fetch data for a coursecompletion criteria
 *
 * @group totara_competency
 */
abstract class totara_criteria_course_achievements_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/completion/completion_completion.php');
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return execution_context::create($type, $operation);
    }

    /**
     * @return criterion
     */
    abstract public function get_criterion(): criterion;

    /**
     * @return string|query_resolver
     */
    abstract public function get_resolver_classname(): string;

    public function test_non_existing_instance() {
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();

        $args = ['instance_id' => 999, 'user_id' => $user->id];

        $this->expectException(criterion_not_found_exception::class);
        $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
    }

    public function test_existing_instance_with_non_existing_courses() {
        $this->setAdminUser();

        $data = $this->create_data();

        $criteria = $this->get_criterion()
            ->set_aggregation_method(criterion::AGGREGATE_ALL)
            ->set_item_ids([987, $data->course2->id, 897])
            ->save();

        $args = ['instance_id' => $criteria->get_id(), 'user_id' => $data->user->id];

        $result = $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
        $this->assertIsArray($result);
        $this->assertEquals(criterion::AGGREGATE_ALL, $result['aggregation_method']);
        $this->assertEquals(1, $result['required_items']);
        $items = $result['items'] ?? [];
        // Only one course really exists the rest should not be there
        $this->assertCount(1, $items);
        $this->assert_course_is_visible($data->course2->fullname, $result);
    }


    public function test_courses_without_user_being_enrolled() {
        $this->setAdminUser();

        $data = $this->create_data();

        $aggregation_method = criterion::AGGREGATE_ALL;

        $criteria = $this->get_criterion()
            ->set_aggregation_method($aggregation_method)
            ->set_item_ids([$data->course1->id, $data->course2->id, $data->course3->id])
            ->save();

        $args = ['instance_id' => $criteria->get_id(), 'user_id' => $data->user->id];

        $result = $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
        $this->assertIsArray($result);
        $this->assertEquals($aggregation_method, $result['aggregation_method']);
        $items = $result['items'] ?? [];
        // Make sure we get the correct amount
        $this->assertCount(3, $items);

        // Make sure the data structure matches our expectation
        foreach ($items as $item) {
            $this->assertArrayHasKey('course', $item);
            /** @var item $course */
            $course = $item['course'];
            $this->assertEquals(0, $course->get_progress_percentage());
            $this->assertNotEmpty($course->fullname);
            $this->assertNotEmpty($course->description);
            $this->assertStringStartsWith('http', $course->url_view);
        }
        $this->assert_course_is_visible($data->course1->fullname, $result);
        $this->assert_course_is_visible($data->course2->fullname, $result);
        $this->assert_course_is_visible($data->course3->fullname, $result);
    }

    public function test_required_items() {
        $this->setAdminUser();

        $data = $this->create_data();

        $criteria = $this->get_criterion()
            ->set_aggregation_method(criterion::AGGREGATE_ANY_N)
            ->set_item_ids([$data->course1->id, $data->course2->id, $data->course3->id])
            ->save();

        for ($req_items = 1; $req_items < 5; $req_items++) {
            $criteria->set_aggregation_params(['req_items' => $req_items])
                ->save();

            $args = ['instance_id' => $criteria->get_id(), 'user_id' => $data->user->id];

            $result = $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
            $this->assertEquals(criterion::AGGREGATE_ANY_N, $result['aggregation_method']);
            $this->assertEquals($req_items, $result['required_items']);
        }
    }

    public function test_course_progress_is_returned_correctly() {
        $this->setAdminUser();

        $data = $this->create_data();

        $user = $this->getDataGenerator()->create_user();
        $control_user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $data->course2->id);
        $this->getDataGenerator()->enrol_user($control_user->id, $data->course2->id);

        $completion = new completion_completion(['userid' => $user->id, 'course' => $data->course2->id]);
        $completion->status = COMPLETION_STATUS_COMPLETE;
        $completion->mark_complete(time() - 10000);

        // Make sure all our completions are up to date.
        $task3 = new completions_task();
        $task3->execute();

        $criteria = $this->get_criterion()
            ->set_item_ids([$data->course1->id, $data->course2->id, $data->course3->id])
            ->save();

        $args = ['instance_id' => $criteria->get_id(), 'user_id' => $user->id];

        $result = $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
        // Course 2 should come out as completed
        $this->assert_course_has_progress($data->course1->fullname, 0, $result);
        $this->assert_course_has_progress($data->course2->fullname, 100, $result);
        $this->assert_course_has_progress($data->course3->fullname, 0, $result);

        // Control user should not have completed the course
        $args = ['instance_id' => $criteria->get_id(), 'user_id' => $control_user->id];

        $result = $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
        $this->assert_course_has_progress($data->course1->fullname, 0, $result);
        $this->assert_course_has_progress($data->course2->fullname, 0, $result);
        $this->assert_course_has_progress($data->course3->fullname, 0, $result);
    }

    public function test_criteria_validity_is_returned_correctly() {
        $this->setAdminUser();

        $data = $this->create_data();

        // criterion with all courses completable should be valid
        $valid_criteria = $this->get_criterion()
            ->set_aggregation_method(criterion::AGGREGATE_ANY_N)
            ->set_aggregation_params(['req_items' => 1])
            ->set_item_ids([$data->course1->id, $data->course2->id])
            ->save();

        $args = ['instance_id' => $valid_criteria->get_id(), 'user_id' => $data->user->id];
        $result = $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
        $this->assertTrue($result['is_valid']);

        // criterion with any not completable course should be invalid
        $invalid_criteria = $this->get_criterion()
            ->set_aggregation_method(criterion::AGGREGATE_ANY_N)
            ->set_aggregation_params(['req_items' => 1])
            ->set_item_ids([$data->course1->id, $data->course4->id])
            ->save();
        $args = ['instance_id' => $invalid_criteria->get_id(), 'user_id' => $data->user->id];
        $result = $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
        $this->assertNotTrue($result['is_valid']);
    }

    public function test_hidden_course_is_not_returned() {
        global $DB;

        $data = $this->create_data();

        // Hide course 2
        $data->course2->visible = 0;
        $DB->update_record('course', $data->course2);

        $user = $this->getDataGenerator()->create_user();

        $criteria = $this->get_criterion()
            ->set_item_ids([$data->course1->id, $data->course2->id, $data->course3->id])
            ->save();

        $this->setAdminUser();

        $args = ['instance_id' => $criteria->get_id(), 'user_id' => $user->id];

        $result = $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
        // The user should only see course 1 and 3
        $this->assert_course_is_visible($data->course1->fullname, $result);
        $this->assert_course_is_visible($data->course2->fullname, $result);
        $this->assert_course_is_visible($data->course3->fullname, $result);

        // User needs to be able to access the profile
        $user_role = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        assign_capability(
            'totara/competency:view_own_profile',
            CAP_ALLOW,
            $user_role->id,
            context_user::instance($user->id)->id,
            true
        );

        // Now query as a normal user for whom the course 2 should not be visible
        $this->setUser($user);

        $args = ['instance_id' => $criteria->get_id(), 'user_id' => $user->id];

        $result = $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
        // The user should only see course 1 and 3
        $this->assert_course_is_visible($data->course1->fullname, $result);
        $this->assert_course_is_not_visible($data->course2->fullname, $result);
        $this->assert_course_is_visible($data->course3->fullname, $result);
    }

    public function test_profile_capability_is_checked() {
        global $DB;

        $data = $this->create_data();

        $user_role = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        unassign_capability('totara/competency:view_own_profile', $user_role->id);

        // Now query as a normal user for whom the course 2 should not be visible
        $this->setUser($data->user);

        $criteria = $this->get_criterion()
            ->set_item_ids([$data->course1->id, $data->course2->id, $data->course3->id])
            ->save();

        $args = ['instance_id' => $criteria->get_id(), 'user_id' => $data->user->id];

        try {
            $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
            $this->fail('Expected required_capability_exception');
        } catch (Exception $exception) {
            $this->assertInstanceOf(moodle_exception::class, $exception);
        }

        // User needs to be able to access the profile
        assign_capability('totara/competency:view_own_profile',
            CAP_ALLOW,
            $user_role->id,
            context_user::instance($data->user->id)->id,
            true
        );

        $args = ['instance_id' => $criteria->get_id(), 'user_id' => $data->user->id];

        $result = $this->get_resolver_classname()::resolve($args, $this->get_execution_context());
        $this->assertNotEmpty($result);
    }

    /**
     * Assert that the given course has the expected progress in the result
     *
     * @param string $course_name
     * @param int $progress
     * @param array $result
     */
    private function assert_course_has_progress(string $course_name, int $progress, array $result) {
        $checked = false;
        foreach ($result['items'] as $item) {
            /** @var item $course */
            $course = $item['course'];
            if ($course->fullname == $course_name) {
                $this->assertEquals($progress, $course->get_progress_percentage(), 'Progress: Course does not have expected progress');
                $checked = true;
            }
        }
        $this->assertTrue($checked, 'Progress: Course not found!');
    }

    /**
     * Assert that the given course is visible in the result
     *
     * @param string $course_name
     * @param array $result
     */
    private function assert_course_is_visible(string $course_name, array $result) {
        $visible_courses = [];
        foreach ($result['items'] as $item) {
            if (!empty($item['course'])) {
                $visible_courses[] = $item['course']->fullname;
            }
        }
        $this->assertContains($course_name, $visible_courses);
    }

    /**
     * Assert that the given course is NOT visible in the result
     *
     * @param string $course_name
     * @param array $result
     */
    private function assert_course_is_not_visible(string $course_name, array $result) {
        $visible_courses = [];
        foreach ($result['items'] as $item) {
            if (!empty($item['course'])) {
                $visible_courses[] = $item['course']->fullname;
            }
        }
        $this->assertNotContainsEquals($course_name, $visible_courses);
    }

    protected function create_data() {
        $data = new class() {
            public $fw1;
            public $comp1;
            public $course1;
            public $course2;
            public $course3;
            public $course4;
            public $user;
        };

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator =  $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $data->fw1 = $hierarchy_generator->create_comp_frame([]);
        $data->comp1 = $hierarchy_generator->create_comp([
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
            'frameworkid' => $data->fw1->id
        ]);

        // Enable completion before creating modules, otherwise the completion data is not written in DB.
        set_config('enablecompletion', 1);

        $data->course1 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $this->getDataGenerator()->create_module('forum', array('course' => $data->course1->id));
        $this->getDataGenerator()->create_module('forum', array('course' => $data->course1->id));

        $data->course2 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $this->getDataGenerator()->create_module('forum', array('course' => $data->course2->id));
        $this->getDataGenerator()->create_module('forum', array('course' => $data->course2->id));

        $data->course3 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $this->getDataGenerator()->create_module('forum', array('course' => $data->course3->id));
        $this->getDataGenerator()->create_module('forum', array('course' => $data->course3->id));

        $data->course4 = $this->getDataGenerator()->create_course(['enablecompletion' => false]);
        $this->getDataGenerator()->create_module('forum', ['course' => $data->course4->id]);
        $this->getDataGenerator()->create_module('forum', ['course' => $data->course4->id]);

        $data->user = $this->getDataGenerator()->create_user();

        return $data;
    }

}
