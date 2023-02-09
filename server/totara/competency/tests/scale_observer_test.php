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

use core\orm\query\builder;
use core_phpunit\testcase;
use hierarchy_competency\event\scale_min_proficient_value_updated;
use totara_competency\aggregation_users_table;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\entity\configuration_change;
use totara_competency\expand_task;
use totara_core\advanced_feature;
use totara_hierarchy\entity\scale as scale_entity;
use totara_hierarchy\entity\scale_value;

/**
 * Tests covering the competency scale observer making sure the events do the right thing
 *
 * @group totara_competency
 */
class totara_competency_scale_observer_testcase extends testcase {

    public function test_minimum_proficient_value_changed_no_assignments() {
        advanced_feature::enable('competency_assignment');

        $scale = $this->create_scale();
        $comp = $this->create_competency($scale->id);

        $scale_values = scale_value::repository()
            ->where('scaleid', $scale->id)
            ->order_by('sortorder', 'DESC')
            ->get();

        $lowest = $scale_values->first();

        $this->assertNotEquals($scale->minproficiencyid, $lowest->id);

        $scale = new scale_entity($scale);
        $scale->minproficiencyid = $lowest->id;
        $scale->save();

        $table = new aggregation_users_table();

        $this->assertEmpty(builder::get_db()->get_records($table->get_table_name()));

        scale_min_proficient_value_updated::create_from_instance((object)$scale->to_array())->trigger();

        // No assignments so nothing should be queued
        $this->assertEmpty(builder::get_db()->get_records($table->get_table_name()));
    }

    public function test_minimum_proficient_value_changed() {
        advanced_feature::enable('competency_assignment');

        $scale1 = $this->create_scale();
        $scale2 = $this->create_scale();
        $comp1 = $this->create_competency($scale1->id);
        $comp2 = $this->create_competency($scale2->id);

        // We don't want the create event fired here
        $sink = $this->redirectEvents();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $assignment_generator = $this->generator()->assignment_generator();
        $assignment_generator->create_user_assignment($comp1->id, $user1->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp1->id, $user2->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp2->id, $user1->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp2->id, $user2->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp2->id, $user3->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        // Have one assignment archived
        $assignment_generator->create_user_assignment($comp1->id, $user4->id, ['status' => assignment_entity::STATUS_ARCHIVED]);

        (new expand_task($GLOBALS['DB']))->expand_all();

        $sink->close();

        $scale_values = scale_value::repository()
            ->where('scaleid', $scale1->id)
            ->order_by('sortorder', 'DESC')
            ->get();

        $lowest = $scale_values->first();

        $this->assertNotEquals($scale1->minproficiencyid, $lowest->id);

        $scale1 = new scale_entity($scale1);
        $scale1->minproficiencyid = $lowest->id;
        $scale1->save();

        $table = new aggregation_users_table();

        $this->assert_queued_records_exist([]);

        scale_min_proficient_value_updated::create_from_instance((object)$scale1->to_array())->trigger();

        $expected = [
            [
                'user_id' => $user1->id,
                'competency_id' => $comp1->id,
                'process_key' => null
            ],
            [
                'user_id' => $user2->id,
                'competency_id' => $comp1->id,
                'process_key' => null
            ]
        ];
        $this->assert_queued_records_exist($expected);
    }

    public function test_minimum_proficient_value_changed_with_existing_records() {
        advanced_feature::enable('competency_assignment');

        $scale1 = $this->create_scale();
        $scale2 = $this->create_scale();
        $comp1 = $this->create_competency($scale1->id);
        $comp2 = $this->create_competency($scale2->id);

        // We don't want the create event fired here
        $sink = $this->redirectEvents();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $assignment_generator = $this->generator()->assignment_generator();
        $assignment_generator->create_user_assignment($comp1->id, $user1->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp1->id, $user2->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp2->id, $user1->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp2->id, $user2->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp2->id, $user3->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        // Have one assignment archived
        $assignment_generator->create_user_assignment($comp1->id, $user4->id, ['status' => assignment_entity::STATUS_ARCHIVED]);

        (new expand_task($GLOBALS['DB']))->expand_all();

        $sink->close();

        $scale_values = scale_value::repository()
            ->where('scaleid', $scale1->id)
            ->order_by('sortorder', 'DESC')
            ->get();

        $lowest = $scale_values->first();

        $this->assertNotEquals($scale1->minproficiencyid, $lowest->id);

        $scale1 = new scale_entity($scale1);
        $scale1->minproficiencyid = $lowest->id;
        $scale1->save();

        $table = new aggregation_users_table();

        $this->assert_queued_records_exist([]);

        builder::get_db()->insert_records(
            $table->get_table_name(),
            [
                [
                    $table->get_user_id_column() => $user1->id,
                    $table->get_competency_id_column() => $comp1->id,
                    $table->get_process_key_column() => null,
                    $table->get_has_changed_column() => 1
                ],
                [
                    $table->get_user_id_column() => $user2->id,
                    $table->get_competency_id_column() => $comp1->id,
                    $table->get_process_key_column() => 'iamprocessing',
                    $table->get_has_changed_column() => 0
                ],
            ]
        );

        $this->assertEquals(2, builder::get_db()->count_records($table->get_table_name()));

        scale_min_proficient_value_updated::create_from_instance((object)$scale1->to_array())->trigger();

        $expected = [
            [
                'user_id' => $user1->id,
                'competency_id' => $comp1->id,
                'process_key' => null,
                'has_changed' => 1
            ],
            [
                'user_id' => $user2->id,
                'competency_id' => $comp1->id,
                'process_key' => 'iamprocessing',
                'has_changed' => 0
            ],
            [
                'user_id' => $user2->id,
                'competency_id' => $comp1->id,
                'process_key' => null,
                'has_changed' => 1
            ],
        ];
        $this->assert_queued_records_exist($expected);
    }

    public function test_minimum_proficient_value_changed_with_existing_record_which_is_not_changed() {
        advanced_feature::enable('competency_assignment');

        $scale1 = $this->create_scale();
        $scale2 = $this->create_scale();
        $comp1 = $this->create_competency($scale1->id);
        $comp2 = $this->create_competency($scale2->id);

        // We don't want the create event fired here
        $sink = $this->redirectEvents();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $assignment_generator = $this->generator()->assignment_generator();
        $assignment_generator->create_user_assignment($comp1->id, $user1->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp1->id, $user2->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp2->id, $user1->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp2->id, $user2->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        $assignment_generator->create_user_assignment($comp2->id, $user3->id, ['status' => assignment_entity::STATUS_ACTIVE]);
        // Have one assignment archived
        $assignment_generator->create_user_assignment($comp1->id, $user4->id, ['status' => assignment_entity::STATUS_ARCHIVED]);

        (new expand_task($GLOBALS['DB']))->expand_all();

        $sink->close();

        $scale_values = scale_value::repository()
            ->where('scaleid', $scale1->id)
            ->order_by('sortorder', 'DESC')
            ->get();

        $lowest = $scale_values->first();

        $this->assertNotEquals($scale1->minproficiencyid, $lowest->id);

        $scale1 = new scale_entity($scale1);
        $scale1->minproficiencyid = $lowest->id;
        $scale1->save();

        $table = new aggregation_users_table();

        $this->assert_queued_records_exist([]);

        builder::get_db()->insert_records(
            $table->get_table_name(),
            [
                [
                    $table->get_user_id_column() => $user1->id,
                    $table->get_competency_id_column() => $comp1->id,
                    $table->get_process_key_column() => null,
                    $table->get_has_changed_column() => 0
                ],
                [
                    $table->get_user_id_column() => $user2->id,
                    $table->get_competency_id_column() => $comp1->id,
                    $table->get_process_key_column() => 'iamprocessing',
                    $table->get_has_changed_column() => 0
                ],
            ]
        );

        $this->assertEquals(2, builder::get_db()->count_records($table->get_table_name()));

        scale_min_proficient_value_updated::create_from_instance((object)$scale1->to_array())->trigger();

        $expected = [
            [
                'user_id' => $user1->id,
                'competency_id' => $comp1->id,
                'process_key' => null,
                'has_changed' => 0
            ],
            [
                'user_id' => $user2->id,
                'competency_id' => $comp1->id,
                'process_key' => 'iamprocessing',
                'has_changed' => 0
            ],
            [
                'user_id' => $user1->id,
                'competency_id' => $comp1->id,
                'process_key' => null,
                'has_changed' => 1
            ],
            [
                'user_id' => $user2->id,
                'competency_id' => $comp1->id,
                'process_key' => null,
                'has_changed' => 1
            ],
        ];
        $this->assert_queued_records_exist($expected);
    }

    public function test_configuration_change_is_logged() {
        advanced_feature::enable('competency_assignment');

        $scale1 = $this->create_scale();
        $scale2 = $this->create_scale();
        $comp1 = $this->create_competency($scale1->id);
        $comp2 = $this->create_competency($scale1->id);
        $comp3 = $this->create_competency($scale2->id);
        $comp4 = $this->create_competency($scale2->id);
        $comp5 = $this->create_competency($scale2->id);

        $this->assertEquals(0, configuration_change::repository()->count());

        scale_min_proficient_value_updated::create_from_instance($scale1)->trigger();

        // Two competency are affected by that min proficiency value change

        /** @var configuration_change $change */
        $configuration_changes = configuration_change::repository()->get();
        $this->assertEquals(2, $configuration_changes->count());
        $this->assertEqualsCanonicalizing([$comp1->id, $comp2->id], $configuration_changes->pluck('competency_id'));
        $this->assertEquals(
            [configuration_change::CHANGED_MIN_PROFICIENCY],
            array_unique($configuration_changes->pluck('change_type'))
        );
    }

    protected function assert_queued_records_exist(array $expected_records) {
        $table = new aggregation_users_table();

        if (empty($expected_records)) {
            $this->assertEmpty(builder::get_db()->get_records($table->get_table_name()));
            return;
        }

        $actual_records = builder::get_db()->get_records($table->get_table_name());
        $this->assertEquals(count($expected_records), count($actual_records));

        foreach ($expected_records as $values) {
            foreach ($actual_records as $key => $record) {
                if ($values['user_id'] == $record->{$table->get_user_id_column()}
                    && $values['competency_id'] == $record->{$table->get_competency_id_column()}
                ) {
                    if (!array_key_exists('process_key', $values)
                        || $values['process_key'] == $record->{$table->get_process_key_column()}
                    ) {
                        if (!array_key_exists('has_changed', $values)
                            || $values['has_changed'] == $record->{$table->get_has_changed_column()}
                        ) {
                            unset($actual_records[$key]);
                        }
                    }
                }
            }
        }
        $this->assertEmpty($actual_records, 'Actual records in the queue table do not match the expected records.');
    }

    protected function create_scale() {
        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->generator()->hierarchy_generator();

        return $hierarchy_generator->create_scale(
            'comp',
            ['name' => 'Test scale', 'description' => 'Test scale'],
            [
                1 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                2 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                4 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                5 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );
    }

    protected function create_competency(int $scale_id) {
        $hierarchy_generator = $this->generator()->hierarchy_generator();

        // We don't want the create event fired here
        $sink = $this->redirectEvents();

        $fw = $hierarchy_generator->create_comp_frame(['fullname' => 'Framework one', 'idnumber' => 'f1', 'scale' => $scale_id]);
        $comp = $hierarchy_generator->create_comp([
            'frameworkid' => $fw->id,
            'idnumber' => 'c1',
            'parentid' => 0,
        ]);

        // Stop redirecting events from now
        $sink->close();

        return $comp;
    }

    /**
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        return $competency_generator;
    }

}
