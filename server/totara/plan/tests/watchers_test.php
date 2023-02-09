<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_plan
 */

use core\orm\collection;
use core_phpunit\testcase;
use totara_competency\entity\competency_achievement;
use totara_competency\expand_task;
use totara_competency\hook\competency_achievement_updated_bulk;
use totara_competency\models\assignment_actions;
use totara_hierarchy\entity\competency_framework;
use totara_hierarchy\entity\scale;
use totara_hierarchy\entity\scale_value;
use totara_plan\watcher\competency;

 /**
 * Test hook watchers in this plugin
 *
 * @group totara_plan
 */
class totara_plan_watchers_testcase extends testcase {

    public function test_competency_achievement_updated_bulk() {
        global $DB;

        $this->setAdminUser();

        // Setup users and learning plans
        $users = [
            1 => $this->getDataGenerator()->create_user(),
            2 => $this->getDataGenerator()->create_user(),
        ];

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_competency\testing\assignment_generator $assignment_generator */
        $assignment_generator = $competency_generator->assignment_generator();

        /** @var scale $scale */
        $scale = $competency_generator->create_scale(
            'test_scale',
            'Test scale',
            [
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
            ]
        );

        /** @var collection $scale_values */
        $scale_values = $scale->sorted_values_high_to_low->key_by('sortorder');

        /** @var competency_framework $framework */
        $framework = $competency_generator->create_framework($scale, 'Test framework');
        /** @var competency[] $competencies */
        $competencies = [];
        for ($i = 1; $i <= 4; $i++) {
            $competencies[$i] = $competency_generator->create_competency("Competency {$i}", $framework);
        }

        $assignments = [];
        foreach ($users as $user_key => $user) {
            foreach ($competencies as $competency_key => $competency) {
                $assignment = $assignment_generator->create_user_assignment($competency->id, $user->id);
                $assignments["{$user_key}_{$competency_key}"] = $assignment->id;
            }
        }
        (new assignment_actions())->activate($assignments);
        (new expand_task($DB))->expand_all();

        // Setup learning plans
        $this->create_plan($users[1]->id, [$competencies[1]->id]);
        $this->create_plan($users[2]->id, [$competencies[1]->id, $competencies[2]->id]);
        $this->verify_plan_values([
            [
                'competency_id' => $competencies[1]->id,
                'user_id' => $users[1]->id,
                'scale_value' => $scale_values->item(5),
            ],
            [
                'competency_id' => $competencies[1]->id,
                'user_id' => $users[2]->id,
                'scale_value' => $scale_values->item(5),
            ],
            [
                'competency_id' => $competencies[2]->id,
                'user_id' => $users[2]->id,
                'scale_value' => $scale_values->item(5),
            ],
        ]);

        $this->assertEquals(3, $DB->count_records('dp_plan_competency_value', ['scale_value_id' => $scale_values->item(5)->id]));

        // Setup achievements
        $to_setup = [
            ['user' => 1, 'competency' => 1, 'value' => 4],
            ['user' => 2, 'competency' => 1, 'value' => 3],
            ['user' => 2, 'competency' => 2, 'value' => 2],
        ];

        foreach ($to_setup as $el) {
            $user_id = $users[$el['user']]->id;
            $competency_id = $competencies[$el['competency']]->id;
            $assignment_id = $assignments["{$el['user']}_{$el['competency']}"];
            $scale_value = $scale_values->item($el['value']);
            $this->create_achievement($user_id, $competency_id, $assignment_id, $scale_value);
        }
        $this->assertEquals(3, $DB->count_records('totara_competency_achievement'));

        // Now for the tests
        $hook = new competency_achievement_updated_bulk($competencies[1]);
        foreach ($users as $user) {
            $hook->add_user_id($user->id, ['is_proficient' => 0]);
        }
        competency::achievement_updated_bulk($hook);

        $this->verify_plan_values([
            [
                'competency_id' => $competencies[1]->id,
                'user_id' => $users[1]->id,
                'scale_value' => $scale_values->item(4),
            ],
            [
                'competency_id' => $competencies[1]->id,
                'user_id' => $users[2]->id,
                'scale_value' => $scale_values->item(3),
            ],
            [
                'competency_id' => $competencies[2]->id,
                'user_id' => $users[2]->id,
                'scale_value' => $scale_values->item(5),
            ],
        ]);

        $hook = new competency_achievement_updated_bulk($competencies[2]);
        foreach ($users as $user) {
            $hook->add_user_id($user->id, ['is_proficient' => 0]);
        }
        competency::achievement_updated_bulk($hook);

        $this->verify_plan_values([
            [
                'competency_id' => $competencies[1]->id,
                'user_id' => $users[1]->id,
                'scale_value' => $scale_values->item(4),
            ],
            [
                'competency_id' => $competencies[1]->id,
                'user_id' => $users[2]->id,
                'scale_value' => $scale_values->item(3),
            ],
            [
                'competency_id' => $competencies[2]->id,
                'user_id' => $users[2]->id,
                'scale_value' => $scale_values->item(2),
            ],
        ]);
    }


    /**
     * @param int $user_id
     * @param array $competency_ids
     */
    private function create_plan(int $user_id, array $competency_ids) {
        /** @var \totara_plan\testing\generator $plan_generator */
        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $plan = $plan_generator->create_learning_plan(['userid' => $user_id]);

        foreach ($competency_ids as $competency_id) {
            $plan_generator->add_learning_plan_competency($plan->id, $competency_id);
        }

        $development_plan = new development_plan($plan->id);
        $development_plan->set_status(DP_PLAN_STATUS_APPROVED);
    }

    /**
     * @param int $user_id
     * @param int $competency_id
     * @param int $assignment_id
     * @param scale_value $scale_value
     * @param int $status
    */
    private function create_achievement(int $user_id, int $competency_id, int $assignment_id, scale_value $scale_value, int $status = competency_achievement::ACTIVE_ASSIGNMENT) {
        $now = time();

        $to_create = new competency_achievement();
        $to_create->competency_id = $competency_id;
        $to_create->user_id = $user_id;
        $to_create->assignment_id = $assignment_id;
        $to_create->scale_value_id = $scale_value->id;
        $to_create->proficient = $scale_value->proficient;
        $to_create->status = $status;
        $to_create->time_created = $now;
        $to_create->time_status = $now;

        $to_create->save();
    }

    private function verify_plan_values(array $expected) {
        global $DB;

        $rows = $DB->get_records('dp_plan_competency_value');
        $this->assertSame(count($expected), count($rows));

        foreach ($expected as $key => $to_find) {
            $expected_scale_value = $to_find['scale_value'];
            foreach ($rows as $row) {
                /** @var scale_value $expected_scale_value */
                if ($to_find['competency_id'] == $row->competency_id
                    && $to_find['user_id'] == $row->user_id
                    && $expected_scale_value->id == $row->scale_value_id
                    && (($expected_scale_value->proficient && !is_null($row->timeproficient))
                        || (!$expected_scale_value->proficient && is_null($row->timeproficient)))) {
                    unset($expected[$key]);
                    break;
                }
            }
        }

        $this->assertEmpty($expected);
    }
}
