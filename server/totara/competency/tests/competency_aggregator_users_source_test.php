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
 * @package totara_competency
 */

use core\orm\collection;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_competency\aggregation_users_table;
use totara_competency\competency_aggregator_user_source;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\expand_task;
use totara_competency\linked_courses;
use totara_hierarchy\entity\competency as competency_entity;
use totara_hierarchy\entity\scale as scale_entity;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class totara_competency_competency_aggregator_users_source_testcase extends testcase {

    public function test_get_users_to_reaggregate_perform() {
        \totara_core\advanced_feature::enable('competency_assignment');

        $data = $this->setup_data();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();
        $assignment1 = $assignment_generator->create_user_assignment($data->competency->id, $user1->id);
        $assignment3 = $assignment_generator->create_user_assignment($data->control_competency->id, $user3->id);
        (new expand_task($GLOBALS['DB']))->expand_all();

        $table = new aggregation_users_table();
        $source = new competency_aggregator_user_source($table);
        $source->set_competency_id($data->competency->id);

        // Nothing should be in the queue yet
        $users = $source->get_users_to_reaggregate($data->competency->id);
        $this->assertEmpty($users);

        // Adding a row with has_changed set to 0
        $this->queue($data->competency->id, $user1->id, 0);
        $this->queue($data->competency->id, $user2->id, 0);
        $this->queue($data->control_competency->id, $user3->id, 1);

        // We should have ignored the not changed row
        $users = $source->get_users_to_reaggregate($data->competency->id);
        $this->assertEmpty($users);

        // Now set has changed to 1
        builder::table($table->get_table_name())
            ->update([$table->get_has_changed_column() => 1]);

        // Now we should get one result as we have an assignment for user1 only
        // And we don't query for users of the control_competency
        $users = $source->get_users_to_reaggregate($data->competency->id);
        $expected = [
            [
                'user_id' => $user1->id,
                'assignment_id' => $assignment1->id,
                'achievement' => null
            ],
        ];
        $this->verify_reaggregate_users($expected, $users);

        $assignment2 = $assignment_generator->create_user_assignment($data->competency->id, $user2->id);
        (new expand_task($GLOBALS['DB']))->expand_all();

        // Let's see if we get the proper achievement back as well
        $achievement = $this->create_achievement(
            $data->competency->id,
            $user1->id,
            $assignment1->id,
            $data->scale->min_proficient_value
        );

        // We should get two results as we have an assignment for user1 and user2
        $users = $source->get_users_to_reaggregate($data->competency->id);
        $expected = [
            [
                'user_id' => $user1->id,
                'assignment_id' => $assignment1->id,
                'achievement' => $achievement
            ],
            [
                'user_id' => $user2->id,
                'assignment_id' => $assignment2->id,
                'achievement' => null
            ],
        ];
        $this->verify_reaggregate_users($expected, $users);
    }

    public function test_get_users_to_reaggregate_learn_only() {
        \totara_core\advanced_feature::disable('competency_assignment');

        $this->setAdminUser();

        $data = $this->setup_data();

        $sink = $this->redirectEvents();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // User 1 and 2 have course completions
        $completion = new completion_completion(['course' => $course1->id, 'userid' => $user1->id]);
        $completion->mark_complete();

        $completion = new completion_completion(['course' => $course2->id, 'userid' => $user3->id]);
        $completion->mark_complete();

        linked_courses::set_linked_courses(
            $data->competency->id,
            [
                [
                    'id' => $course1->id,
                    'linktype' => linked_courses::LINKTYPE_MANDATORY
                ]
            ]
        );

        linked_courses::set_linked_courses(
            $data->control_competency->id,
            [
                [
                    'id' => $course1->id,
                    'linktype' => linked_courses::LINKTYPE_MANDATORY
                ],
                [
                    'id' => $course2->id,
                    'linktype' => linked_courses::LINKTYPE_MANDATORY
                ]
            ]
        );

        $sink->close();

        $table = new aggregation_users_table();
        $source = new competency_aggregator_user_source($table);
        $source->set_competency_id($data->competency->id);

        // Now make sure we have legacy assignments for the users

        /** @var \totara_competency\testing\assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();
        $assignment1 = $assignment_generator->create_user_assignment(
            $data->competency->id,
            $user1->id,
            [
                'status' => assignment::STATUS_ARCHIVED,
                'type' => assignment::TYPE_LEGACY
            ]
        );
        $assignment2 = $assignment_generator->create_user_assignment(
            $data->competency->id,
            $user2->id,
            [
                'status' => assignment::STATUS_ARCHIVED,
                'type' => assignment::TYPE_LEGACY
            ]
        );

        // Nothing should be in the queue yet
        $users = $source->get_users_to_reaggregate($data->competency->id);
        $this->assertCount(0, $users);

        // Adding a row with has_changed set to 0
        $this->queue($data->competency->id, $user1->id, 0);
        $this->queue($data->competency->id, $user2->id, 0);
        $this->queue($data->competency->id, $user3->id, 0);
        $this->queue($data->control_competency->id, $user3->id, 1);

        // We should have ignored all rows with has_changed set to 0
        $users = $source->get_users_to_reaggregate($data->competency->id);
        $this->assertCount(0, $users);

        // Now set has changed to 1
        builder::table($table->get_table_name())
            ->update([$table->get_has_changed_column() => 1]);

        // Now we should get one result as we have an assignment for user1 only
        // And we don't query for users of the control_competency
        $users = $source->get_users_to_reaggregate($data->competency->id);
        $expected = [
            [
                'user_id' => $user1->id,
                'assignment_id' => $assignment1->id,
                'achievement' => null
            ],
        ];
        $this->verify_reaggregate_users($expected, $users);

        $sink = $this->redirectEvents();

        // Now set a value in a learning plan for User 2

        /** @var \totara_plan\testing\generator $plan_generator */
        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $plan = $plan_generator->create_learning_plan(['userid' => $user2->id]);
        $plan_generator->add_learning_plan_competency($plan->id, $data->competency->id);

        $development_plan = new development_plan($plan->id);
        /** @var dp_competency_component $component */
        $component = $development_plan->get_component('competency');
        $component->set_value($data->competency->id, $user2->id, $data->scale->minproficiencyid, new stdClass());

        $sink->close();

        // Let's see if we get the proper achievement back as well
        $achievement = $this->create_achievement(
            $data->competency->id,
            $user1->id,
            $assignment1->id,
            $data->scale->min_proficient_value
        );

        // We should get two results as both, user1 and user2, are "assigned" to the competency
        // either via course completion or via learning plan
        $users = $source->get_users_to_reaggregate($data->competency->id);
        $expected = [
            [
                'user_id' => $user1->id,
                'assignment_id' => $assignment1->id,
                'achievement' => $achievement
            ],
            [
                'user_id' => $user2->id,
                'assignment_id' => $assignment2->id,
                'achievement' => null
            ],
        ];
        $this->verify_reaggregate_users($expected, $users);
    }

    protected function queue(int $competency_id, int $user_id, int $has_changed) {
        $table = new aggregation_users_table();

        builder::table($table->get_table_name())
            ->insert([
                $table->get_user_id_column() => $user_id,
                $table->get_competency_id_column() => $competency_id,
                $table->get_has_changed_column() => $has_changed
            ]);
    }

    protected function create_achievement(int $competency_id, int $user_id, int $assignment_id, scale_value $scale_value) {
        $achievement = new competency_achievement();
        $achievement->competency_id = $competency_id;
        $achievement->user_id = $user_id;
        $achievement->assignment_id = $assignment_id;
        $achievement->scale_value_id = $scale_value->id;
        $achievement->proficient = 1;
        $achievement->status = competency_achievement::ACTIVE_ASSIGNMENT;
        $achievement->time_created = time();
        $achievement->time_status = time();
        $achievement->time_proficient = time();
        $achievement->time_scale_value = time();
        $achievement->last_aggregated = time();
        $achievement->save();

        return $achievement;
    }

    protected function setup_data() {
        $data = new class {
            /** @var scale_entity */
            public $scale;
            public $fw;
            /** @var competency_entity $competency */
            public $competency;
            /** @var competency_entity $contril_competency */
            public $control_competency;
        };

        // We don't want the create event fired here
        $sink = $this->redirectEvents();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator =  $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $scale = $hierarchy_generator->create_scale(
            'comp',
            ['name' => 'Test scale', 'description' => 'Test scale'],
            [
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );

        /** @var scale_entity $scale */
        $data->scale = scale_entity::repository()->find($scale->id);

        $fw = $hierarchy_generator->create_comp_frame(['fullname' => 'Framework one', 'idnumber' => 'f1', 'scale' => $scale->id]);
        $comp = $hierarchy_generator->create_comp([
            'frameworkid' => $fw->id,
            'idnumber' => 'c1',
            'parentid' => 0,
            'aggregationmethod' => \competency::AGGREGATION_METHOD_ANY
        ]);

        $data->competency = new competency_entity($comp);

        $comp2 = $hierarchy_generator->create_comp([
            'frameworkid' => $fw->id,
            'idnumber' => 'c1',
            'parentid' => 0,
            'aggregationmethod' => \competency::AGGREGATION_METHOD_ANY
        ]);
        $data->control_competency = new competency_entity($comp2);

        // Stop redirecting events from now
        $sink->close();

        return $data;
    }

    private function verify_reaggregate_users(array $expected, collection $actual): void {
        $this->assertCount(count($expected), $actual);
        foreach ($expected as $idx => $expected_item) {
            foreach ($actual as $actual_item) {
                if ($expected_item['user_id'] == $actual_item->user_id
                    && $expected_item['assignment_id'] == $actual_item->assignment->id
                    && $expected_item['achievement'] == $actual_item->achievement
                ) {
                    unset($expected[$idx]);
                    break;
                }
            }
        }

        $this->assertEmpty($expected);
    }
}
