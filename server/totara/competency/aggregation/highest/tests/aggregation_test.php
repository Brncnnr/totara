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
 * @package aggregation_highest
 */

use aggregation_highest\highest;
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
class aggregation_highest_aggregation_testcase extends testcase {

    public function test_aggregation_type() {
        $this->assertSame('highest', highest::aggregation_type());
    }

    public function test_with_empty_pathways() {
        $user = $this->getDataGenerator()->create_user();

        $aggregation = new highest();
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

        $achievement_detail = $this->getMockForAbstractClass(base_achievement_detail::class);

        $pathway1_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['aggregate_current_value', 'get_id', 'get_competency'])
                         ->getMockForAbstractClass();
        $pathway1_mock->method('aggregate_current_value')->willReturn($achievement_detail);
        $pathway1_mock->method('get_id')->willReturn($pathway1->get_id());
        $pathway1_mock->method('get_competency')->willReturn($competency);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway1_mock, $pw_user_source]);
        $pathway_evaluator->aggregate();

        $aggregation = new highest();
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

        $achievement_detail = $this->getMockForAbstractClass(base_achievement_detail::class);
        $achievement_detail->set_scale_value_id($scale_value->id);

        $pathway1_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['aggregate_current_value', 'get_id', 'get_competency', 'is_valid'])
                         ->getMockForAbstractClass();
        $pathway1_mock->set_competency($competency);
        $pathway1_mock->method('aggregate_current_value')->willReturn($achievement_detail);
        $pathway1_mock->method('get_id')->willReturn($pathway1->get_id());
        $pathway1_mock->method('get_competency')->willReturn($competency);
        $pathway1_mock->method('is_valid')->willReturn(true);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway1_mock, $pw_user_source]);
        $pathway_evaluator->aggregate();

        $aggregation = new highest();
        $aggregation->set_pathways([$pathway1_mock])
                    ->aggregate_for_user($user->id);

        // Reload current achievement as values will need to be strings from the database for the expected and actual to match.
        $current_achievement = pathway_achievement::get_current($pathway1_mock, $user->id);

        $this->assertEquals($scale_value->id, $aggregation->get_achieved_value_id($user->id));
        $achieved_via = $aggregation->get_achieved_via($user->id);
        $this->assertContainsOnlyInstancesOf(pathway_achievement::class, $achieved_via);
        $achieved_via_ids = collection::new($achieved_via)->pluck('id');
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
        $pathway3a = $competency_generator->create_test_pathway($competency);
        $pathway3b = $competency_generator->create_test_pathway($competency);

        $scale = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy')->create_scale('comp');
        $scale_values = $DB->get_records('comp_scale_values', ['scaleid' => $scale->id], 'sortorder DESC');

        // Now the ordering of scale values can be confusing. The sortorder is reversed in relation to the actual meaning.
        // This means the lower the sortorder the higher the actual value.

        // So we are now starting with the lowest and skip it as we only use the second and the highest value for this test
        $scale_value1 = new scale_value(array_shift($scale_values));

        $achievement_detail1 = $this->getMockForAbstractClass(base_achievement_detail::class);
        $pathway1_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['aggregate_current_value', 'get_id', 'get_competency', 'is_valid'])
                         ->getMockForAbstractClass();
        $pathway1_mock->method('aggregate_current_value')->willReturn($achievement_detail1);
        $pathway1_mock->method('get_id')->willReturn($pathway1->get_id());
        $pathway1_mock->method('get_competency')->willReturn($competency);
        $pathway1_mock->method('is_valid')->willReturn(true);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway1_mock, $pw_user_source]);
        $pathway_evaluator->aggregate();

        // This is the second value and we use it to give the user an achievement
        $scale_value2 = new scale_value(array_shift($scale_values));
        $achievement_detail2 = $this->getMockForAbstractClass(base_achievement_detail::class);
        $achievement_detail2->set_scale_value_id($scale_value2->id);
        $pathway2_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['aggregate_current_value', 'get_id', 'get_competency', 'is_valid'])
                         ->getMockForAbstractClass();
        $pathway2_mock->method('aggregate_current_value')->willReturn($achievement_detail2);
        $pathway2_mock->method('get_id')->willReturn($pathway2->get_id());
        $pathway2_mock->method('get_competency')->willReturn($competency);
        $pathway2_mock->method('is_valid')->willReturn(true);

        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway2_mock, $pw_user_source]);
        $pathway_evaluator->aggregate();

        // This is the highest value, which should be the one the users gets at the end as it's the highest
        $scale_value3 = new scale_value(array_shift($scale_values));
        $achievement_detail3 = $this->getMockForAbstractClass(base_achievement_detail::class);
        $achievement_detail3->set_scale_value_id($scale_value3->id);
        $pathway3a_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['aggregate_current_value', 'get_id', 'get_competency', 'is_valid'])
                         ->getMockForAbstractClass();
        $pathway3a_mock->method('aggregate_current_value')->willReturn($achievement_detail3);
        $pathway3a_mock->method('get_id')->willReturn($pathway3a->get_id());
        $pathway3a_mock->method('get_competency')->willReturn($competency);
        $pathway3a_mock->method('is_valid')->willReturn(true);

        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway3a_mock, $pw_user_source]);
        $pathway_evaluator->aggregate();

        $pathway3b_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['aggregate_current_value', 'get_id', 'get_competency', 'is_valid'])
                         ->getMockForAbstractClass();
        $pathway3b_mock->method('aggregate_current_value')->willReturn($achievement_detail3);
        $pathway3b_mock->method('get_id')->willReturn($pathway3b->get_id());
        $pathway3b_mock->method('get_competency')->willReturn($competency);
        $pathway3b_mock->method('is_valid')->willReturn(true);

        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway3b_mock, $pw_user_source]);
        $pathway_evaluator->aggregate();

        $aggregation = new highest();
        $aggregation->set_pathways([$pathway1_mock, $pathway2_mock, $pathway3a_mock, $pathway3b_mock])
                    ->aggregate_for_user($user->id);

        $current_achievement3a = pathway_achievement::get_current($pathway3a_mock, $user->id);
        $current_achievement3b = pathway_achievement::get_current($pathway3b_mock, $user->id);

        $this->assertEquals($scale_value3->id, $aggregation->get_achieved_value_id($user->id));
        $achieved_via = $aggregation->get_achieved_via($user->id);
        $this->assertContainsOnlyInstancesOf(pathway_achievement::class, $achieved_via);
        $achieved_via_ids = collection::new($achieved_via)->pluck('id');
        $this->assertEqualsCanonicalizing([$current_achievement3a->id, $current_achievement3b->id], $achieved_via_ids);
    }
}
