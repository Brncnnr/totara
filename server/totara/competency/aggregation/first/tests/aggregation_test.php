<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package aggregation_first
 */

use aggregation_first\first;
use core\orm\collection;
use core_phpunit\testcase;
use totara_competency\aggregation_users_table;
use totara_competency\base_achievement_detail;
use totara_competency\entity\pathway_achievement;
use totara_competency\pathway;
use totara_competency\pathway_evaluator;
use totara_competency\pathway_evaluator_user_source;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class aggregation_first_aggregation_testcase extends testcase {

    public function test_aggregation_type() {
        $this->assertSame('first', first::aggregation_type());
    }

    public function test_with_empty_pathways() {
        $user = $this->getDataGenerator()->create_user();

        $aggregation = new first();
        $aggregation->set_pathways([])
                    ->aggregate_for_user($user->id);

        $this->assertNull($aggregation->get_achieved_value_id($user->id));
        $this->assertEquals([], $aggregation->get_achieved_via($user->id));
    }

    public function test_with_single_pathway_returning_null() {
        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();

        $pathway1 = $competency_generator->create_test_pathway($competency);

        $pathway1_mock = $this->getMockBuilder(pathway::class)
            ->setMethods(['aggregate_current_value', 'get_id', 'get_sortorder', 'get_competency'])
            ->getMockForAbstractClass();

        $achievement_detail = $this->getMockForAbstractClass(base_achievement_detail::class);
        $pathway1_mock->method('aggregate_current_value')->willReturn($achievement_detail);
        $pathway1_mock->method('get_id')->willReturn($pathway1->get_id());
        $pathway1_mock->method('get_sortorder')->willReturn($pathway1->get_sortorder());
        $pathway1_mock->method('get_competency')->willReturn($competency);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway1_mock, $pw_user_source]);
        $pathway_evaluator->aggregate();

        $aggregation = new first();
        $aggregation->set_pathways([$pathway1_mock])
                    ->aggregate_for_user($user->id);

        $this->assertNull($aggregation->get_achieved_value_id($user->id));
        $this->assertEquals([], $aggregation->get_achieved_via($user->id));
    }

    public function test_with_single_pathway_returning_value() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();

        $pathway1 = $competency_generator->create_test_pathway($competency);

        $scale = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy')->create_scale('comp');
        $scale_values = $DB->get_records('comp_scale_values', ['scaleid' => $scale->id]);
        $scale_value = new scale_value(array_pop($scale_values));

        $pathway1_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['aggregate_current_value', 'get_id', 'get_sortorder', 'get_competency', 'is_valid'])
                         ->getMockForAbstractClass();

        $achievement_detail = $this->getMockForAbstractClass(base_achievement_detail::class);
        $achievement_detail->set_scale_value_id($scale_value->id);
        $pathway1_mock->method('aggregate_current_value')->willReturn($achievement_detail);
        $pathway1_mock->method('get_id')->willReturn($pathway1->get_id());
        $pathway1_mock->method('get_sortorder')->willReturn($pathway1->get_sortorder());
        $pathway1_mock->method('get_competency')->willReturn($competency);
        $pathway1_mock->method('is_valid')->willReturn(true);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway1_mock, $pw_user_source]);
        $pathway_evaluator->aggregate();

        $aggregation = new first();
        $aggregation->set_pathways([$pathway1_mock])
                    ->aggregate_for_user($user->id);

        // Reload current achievement as values will need to be strings from the database for the expected and actual to match.
        $current_achievement = pathway_achievement::get_current($pathway1_mock, $user->id);

        $achieved_via_ids = collection::new($aggregation->get_achieved_via($user->id))->pluck('id');
        $this->assertEquals($scale_value->id, $aggregation->get_achieved_value_id($user->id));
        $this->assertEquals([$current_achievement->id], $achieved_via_ids);
    }

    public function test_multiple_pathways_returning_value_or_null() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();

        $pathway1 = $competency_generator->create_test_pathway($competency);
        $pathway2 = $competency_generator->create_test_pathway($competency);
        $pathway3 = $competency_generator->create_test_pathway($competency);

        $scale = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy')->create_scale('comp');
        $scale_values = $DB->get_records('comp_scale_values', ['scaleid' => $scale->id], 'sortorder DESC');

        $pathway1_mock = $this->getMockBuilder(pathway::class)
            ->disableOriginalConstructor()
             ->setMethods(['aggregate_current_value', 'get_id', 'get_sortorder', 'get_competency', 'get_path_type', 'is_valid'])
             ->getMockForAbstractClass();
        $achievement_detail1 = $this->getMockForAbstractClass(base_achievement_detail::class);
        $pathway1_mock->method('aggregate_current_value')->willReturn($achievement_detail1);
        $pathway1_mock->method('get_id')->willReturn($pathway1->get_id());
        $pathway1_mock->method('get_sortorder')->willReturn($pathway1->get_sortorder());
        $pathway1_mock->method('get_path_type')->willReturn($pathway2->get_path_type());
        $pathway1_mock->method('get_competency')->willReturn($competency);
        $pathway1_mock->method('is_valid')->willReturn(true);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        $pathway_evaluator1 = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway1_mock, $pw_user_source]);
        $pathway_evaluator1->aggregate();

        $scale_value2 = new scale_value(array_pop($scale_values));
        $pathway2_mock = $this->getMockBuilder(pathway::class)
            ->disableOriginalConstructor()
            ->setMethods(['aggregate_current_value', 'get_id', 'get_sortorder', 'get_competency', 'get_path_type', 'is_valid'])
             ->getMockForAbstractClass();
        $achievement_detail2 = $this->getMockForAbstractClass(base_achievement_detail::class);
        $achievement_detail2->set_scale_value_id($scale_value2->id);
        $pathway2_mock->method('aggregate_current_value')->willReturn($achievement_detail2);
        $pathway2_mock->method('get_id')->willReturn($pathway2->get_id());
        $pathway2_mock->method('get_sortorder')->willReturn($pathway2->get_sortorder());
        $pathway2_mock->method('get_path_type')->willReturn($pathway2->get_path_type());
        $pathway2_mock->method('get_competency')->willReturn($competency);
        $pathway2_mock->method('is_valid')->willReturn(true);

        $pathway_evaluator2 = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway2_mock, $pw_user_source]);
        $pathway_evaluator2->aggregate();

        $scale_value3 = new scale_value(array_pop($scale_values));
        $pathway3_mock = $this->getMockBuilder(pathway::class)
            ->disableOriginalConstructor()
            ->setMethods(['aggregate_current_value', 'get_id', 'get_sortorder', 'get_competency', 'get_path_type', 'is_valid'])
             ->getMockForAbstractClass();
        $achievement_detail3 = $this->getMockForAbstractClass(base_achievement_detail::class);
        $achievement_detail3->set_scale_value_id($scale_value3->id);
        $pathway3_mock->method('aggregate_current_value')->willReturn($achievement_detail3);
        $pathway3_mock->method('get_id')->willReturn($pathway3->get_id());
        $pathway3_mock->method('get_sortorder')->willReturn($pathway3->get_sortorder());
        $pathway3_mock->method('get_path_type')->willReturn($pathway2->get_path_type());
        $pathway3_mock->method('get_competency')->willReturn($competency);
        $pathway3_mock->method('is_valid')->willReturn(true);

        $pathway_evaluator3 = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway3_mock, $pw_user_source]);
        $pathway_evaluator3->aggregate();

        $aggregation = new first();
        // I order pathway 3 before pathway 2 in the input array.
        // It should be reordered according to its get_sortorder value and that should mean that achievement2 is the achieved.
        $aggregation->set_pathways([$pathway1_mock, $pathway3_mock, $pathway2_mock])
            ->aggregate_for_user($user->id);

        $current_achievement2 = pathway_achievement::get_current($pathway2_mock, $user->id);

        // It skipped the null on the first pathway as it is looking for the first proper value.
        // pathway2 would have been ordered ahead of pathway3, and so we get the below:
        $achieved_via_ids = collection::new($aggregation->get_achieved_via($user->id))->pluck('id');
        $this->assertEquals($scale_value2->id, $aggregation->get_achieved_value_id($user->id));
        $this->assertEquals([$current_achievement2->id], $achieved_via_ids);
    }

}
