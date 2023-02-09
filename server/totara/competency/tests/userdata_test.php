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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 * @subpackage test
 */

use core_phpunit\testcase;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\pathway_achievement;
use totara_competency\userdata\achievement;
use totara_criteria\entity\criteria_item;
use totara_criteria\entity\criteria_item_record;
use totara_criteria\entity\criterion;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;
use totara_userdata\userdata\target_user;

/**
 * @group totara_competency
 */
class totara_competency_userdata_testcase extends testcase {

    /**
     * @var stdClass
     */
    private $user1;

    /**
     * @var stdClass
     */
    private $user2;

    /**
     * @var competency
     */
    private $competency;

    /**
     * @var scale_value
     */
    private $scale_value;

    protected function setUp(): void {
        $this->competency = $this->generator()->create_competency();
        $this->scale_value = scale_value::repository()->order_by('id')->first();

        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();

        $this->user1->achievements = $this->create_achievement_data($this->user1, 3);
        $this->user2->achievements = $this->create_achievement_data($this->user2, 2);
    }

    protected function tearDown(): void {
        $this->user1 = null;
        $this->user2 = null;
        $this->competency = null;
        $this->scale_value = null;
    }

    public function test_purge_and_count() {
        $user1_achievement_count = count(array_merge(...array_values($this->user1->achievements)));
        $user2_achievement_count = count(array_merge(...array_values($this->user2->achievements)));

        $this->assertEquals($user1_achievement_count, $this->count_data($this->user1));
        $this->assertEquals(3, criteria_item_record::repository()->where('user_id', $this->user1->id)->count());
        $this->assertEquals(3, competency_achievement::repository()->where('user_id', $this->user1->id)->count());
        $this->assertEquals(3, pathway_achievement::repository()->where('user_id', $this->user1->id)->count());

        $this->assertEquals($user2_achievement_count, $this->count_data($this->user2));
        $this->assertEquals(2, criteria_item_record::repository()->where('user_id', $this->user2->id)->count());
        $this->assertEquals(2, competency_achievement::repository()->where('user_id', $this->user2->id)->count());
        $this->assertEquals(2, pathway_achievement::repository()->where('user_id', $this->user2->id)->count());

        $this->purge($this->user1);

        $this->assertEquals(0, $this->count_data($this->user1));
        $this->assertEquals(0, criteria_item_record::repository()->where('user_id', $this->user1->id)->count());
        $this->assertEquals(0, competency_achievement::repository()->where('user_id', $this->user1->id)->count());
        $this->assertEquals(0, pathway_achievement::repository()->where('user_id', $this->user1->id)->count());

        $this->assertEquals($user2_achievement_count, $this->count_data($this->user2));
        $this->assertEquals(2, criteria_item_record::repository()->where('user_id', $this->user2->id)->count());
        $this->assertEquals(2, competency_achievement::repository()->where('user_id', $this->user2->id)->count());
        $this->assertEquals(2, pathway_achievement::repository()->where('user_id', $this->user2->id)->count());

        $this->purge($this->user2);

        $this->assertEquals(0, $this->count_data($this->user2));
        $this->assertEquals(0, criteria_item_record::repository()->where('user_id', $this->user2->id)->count());
        $this->assertEquals(0, competency_achievement::repository()->where('user_id', $this->user2->id)->count());
        $this->assertEquals(0, pathway_achievement::repository()->where('user_id', $this->user2->id)->count());
    }

    public function test_export() {
        $all_expected_items = $this->user1->achievements;
        $all_exported_items = $this->export($this->user1);

        $this->assertCount(3, $all_expected_items);
        $this->assertCount(3, $all_exported_items);

        foreach (array_keys($all_exported_items) as $export_type) {
            $expected_items = $all_expected_items[$export_type];
            $actual_items = $all_exported_items[$export_type];

            $this->assertEquals(count($expected_items), count($actual_items));

            for ($i = 0; $i < count($actual_items); $i++) {
                $expected_exported_item = $this->{"expected_{$export_type}_exported"}($expected_items[$i]);
                $actual_item = $actual_items[$i];

                foreach ($actual_item as $key => $value) {
                    $this->assertEquals($expected_exported_item[$key], $actual_item[$key]);
                }
            }
        }
    }

    private function expected_criteria_items_exported(criteria_item_record $record): array {
        return array_merge($record->to_array(), [
            'item_id'               => $record->item->id,
            'item_type'             => $record->item->item_type,
            'criterion_id'          => $record->item->criterion->id,
            'criterion_plugin_type' => $record->item->criterion->plugin_type,
            'time_evaluated'        => $record->timeevaluated,
            'time_achieved'         => $record->timeachieved,
        ]);
    }

    private function expected_achievements_exported(competency_achievement $record): array {
        return array_merge($record->to_array(), [
            'competency_id'    => $record->competency_id,
            'competency_name'  => $record->competency->fullname,
            'scale_value_name' => $record->value->name,
        ]);
    }

    private function expected_pathway_achievements_exported(pathway_achievement $record): array {
        return array_merge($record->to_array(), [
            'pathway_type'     => $record->pathway->path_type,
            'competency_id'    => $record->pathway->competency->id,
            'competency_name'  => $record->pathway->competency->fullname,
            'scale_value_name' => $record->scale_value->name,
        ]);
    }

    private function create_achievement_data(stdClass $user, int $records_to_create = 3): array {
        $assignment = $this->generator()->assignment_generator()->create_user_assignment($this->competency->id, $user->id);

        $achievements['achievements'] = [];
        for ($i = 0; $i < $records_to_create; $i++) {
            $achievement = new competency_achievement();
            $achievement->user_id = $user->id;
            $achievement->competency_id = $this->competency->id;
            $achievement->assignment_id = $assignment->id;
            $achievement->scale_value_id = $this->scale_value->id;
            $achievement->proficient = 1;
            $achievement->status = 1;
            $achievement->time_created = $i . $user->id;
            $achievement->time_status = $i . $user->id;
            $achievement->time_proficient = $i . $user->id;
            $achievement->time_scale_value = $i . $user->id;
            $achievement->last_aggregated = $i . $user->id;

            $achievements['achievements'][] = $achievement->save();
        }

        $pathway = $this->generator()->create_test_pathway($this->competency);

        $achievements['pathway_achievements'] = [];
        for ($i = 0; $i < $records_to_create; $i++) {
            $achievement = new pathway_achievement();
            $achievement->pathway_id = $pathway->get_id();
            $achievement->user_id = $user->id;
            $achievement->scale_value_id = $this->scale_value->id;
            $achievement->date_achieved = $i . $user->id;
            $achievement->last_aggregated = $i . $user->id;
            $achievement->status = 1;
            $achievement->related_info = 'test_' . $user->id;

            $achievements['pathway_achievements'][] = $achievement->save();
        }

        $criterion = new criterion();
        $criterion->plugin_type = 'test';
        $criterion->aggregation_method = 1;
        $criterion->aggregation_params = 'test_' . $user->id;
        $criterion->criterion_modified = 0;
        $criterion->save();

        $criteria_item = new criteria_item();
        $criteria_item->criterion_id = $criterion->id;
        $criteria_item->item_type = 'test_' . $user->id;
        $criteria_item->item_id = 0;
        $criteria_item->save();

        $achievements['criteria_items'] = [];
        for ($i = 0; $i < $records_to_create; $i++) {
            $criteria_record = new criteria_item_record();
            $criteria_record->criterion_item_id = $criteria_item->id;
            $criteria_record->user_id = $user->id;
            $criteria_record->criterion_met = 1;
            $criteria_record->timeevaluated = $i . $user->id;

            $achievements['criteria_items'][] = $criteria_record->save();
        }

        return $achievements;
    }

    private function purge(stdClass $user) {
        achievement::execute_purge(new target_user($user), context_system::instance());
    }

    private function export(stdClass $user): array {
        return achievement::execute_export(new target_user($user), context_system::instance())->data;
    }

    private function count_data(stdClass $user): int {
        return achievement::execute_count(new target_user($user), context_system::instance());
    }

    /**
     * Get competency generator
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_competency');
    }

}
