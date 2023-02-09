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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

use core\orm\collection;
use core_phpunit\testcase;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\aggregation_users_table;
use totara_competency\competency_aggregator_user_source;
use totara_competency\data_providers;
use totara_competency\min_proficiency_override_for_assignments;
use totara_competency\models\assignment_actions;
use totara_competency\expand_task;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\configuration_change;
use totara_competency\models\activity_log;
use totara_competency\pathway_evaluator;
use totara_competency\achievement_configuration;
use totara_competency\competency_achievement_aggregator;
use totara_competency\base_achievement_detail;
use totara_competency\pathway_evaluator_user_source;
use totara_competency\testing\assignment_generator;
use totara_core\advanced_feature;
use totara_job\job_assignment;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;
use totara_hierarchy\testing\generator as hierarchy_generator;
use totara_competency\testing\generator as competency_generator;

/**
 * @group totara_competency
 */
class totara_competency_data_provider_activity_log_testcase extends testcase {

    public function test_with_nothing_to_fetch() {
        // Dummy numbers for user id and competency id.
        $provider = data_providers\activity_log::create(100, 200);
        $this->assertEmpty($provider->fetch());
    }

    public function test_with_one_assignment_only() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        $totara_hierarchy_generator = hierarchy_generator::instance();
        $compfw = $totara_hierarchy_generator->create_comp_frame([]);
        $comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        /** @var assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();
        $assignment = $assignment_generator->create_user_assignment($comp->id, $user->id);

        $model = new assignment_actions();
        $model->activate([$assignment->id]);

        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        $provider = data_providers\activity_log::create($user->id, $comp->id);

        $data = $provider->fetch();
        $this->assertCount(2, $data);

        // The last element (i.e. chronologically earliest) should be the assignment.
        $datum = array_pop($data);
        $this->assertInstanceOf(activity_log\assignment::class, $datum);
        $this->assertEquals(competency_assignment_user_log::ACTION_ASSIGNED, $datum->get_entity()->action);

        // The other element should be for tracking started.
        $datum = array_pop($data);
        $this->assertInstanceOf(activity_log\assignment::class, $datum);
        $this->assertEquals(competency_assignment_user_log::ACTION_TRACKING_START, $datum->get_entity()->action);
    }

    public function test_arrange_log_data_orders_by_date() {
        $mocks = [];
        for ($i = 0; $i < 5; $i++) {
            $mock = $this->getMockBuilder(activity_log::class)->setMethods(['get_date'])->getMockForAbstractClass();
            if ($i === 3) {
                // So both elements 2 and 3 will return the same date.
                $mock->method('get_date')->willReturn(2);
            } else {
                $mock->method('get_date')->willReturn($i);
            }

            $mocks[$i] = $mock;
        }

        // Note that 2 is before 3.
        $data = [$mocks[0], $mocks[4], $mocks[2], $mocks[3], $mocks[1]];

        $provider = data_providers\activity_log::create(100, 200);
        $reflection = new ReflectionObject($provider);

        // Using reflections here to test some internal implementation because it's
        // a key feature of this provider.
        $arrange_log_data = $reflection->getMethod('arrange_log_data');
        $arrange_log_data->setAccessible(true);
        $returned = $arrange_log_data->invoke($provider, $data);

        // The returned array should be in reverse chronological order.
        // There should not be occasional failures where 3 is before 2. Even though they have
        // the same date value, it should maintain the order between them that they went in with.
        $this->assertEquals([$mocks[4], $mocks[2], $mocks[3], $mocks[1], $mocks[0]], $returned);
    }

    public function test_arrange_log_data_competency_achievement() {
        $user = $this->getDataGenerator()->create_user();

        $competency_id = 100;
        $achievement_date = 200;

        /** @var hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchy_generator->create_scale('comp', [], [
            ['name' => 'Great', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
        ]);
        $scale_value = scale_value::repository()->where('name', '=', 'Great')->one();

        $assignment = new assignment();
        $assignment->competency_id = $competency_id;
        $assignment->user_group_id = 300;
        $assignment->user_group_type = 'test';
        $assignment->created_by = 400;
        $assignment->save();

        $achievement = new competency_achievement();
        $achievement->user_id = $user->id;
        $achievement->competency_id = $competency_id;
        $achievement->proficient = 0;
        $achievement->status = 0;
        $achievement->time_status = $achievement_date;
        $achievement->time_created = $achievement_date;
        $achievement->assignment_id = $assignment->id;
        $achievement->scale_value_id = $scale_value->id;
        $achievement->save();

        $mock1 = $this->getMockBuilder(activity_log::class)->setMethods(['get_date'])->getMockForAbstractClass();
        $mock1->method('get_date')->willReturn(2);

        $achievement_log_entry = activity_log\competency_achievement::load_by_entity($achievement);

        $mock2 = $this->getMockBuilder(activity_log::class)->setMethods(['get_date'])->getMockForAbstractClass();
        $mock2->method('get_date')->willReturn(2000);

        $provider = data_providers\activity_log::create(100, 200);
        $reflection = new ReflectionObject($provider);

        $arrange_log_data = $reflection->getMethod('arrange_log_data');
        $arrange_log_data->setAccessible(true);
        $returned = $arrange_log_data->invoke($provider, [$mock1, $achievement_log_entry, $mock2]);

        // Popping the last element off the end, so this should be the earliest.
        $last = array_pop($returned);
        $this->assertEquals($mock1, $last);

        // Next should be the achieved via entry that was added because of the achievement record.
        $last = array_pop($returned);
        $this->assertInstanceOf(activity_log\competency_achieved_via::class, $last);
        $this->assertEquals($achievement_date, $last->get_date());

        $last = array_pop($returned);
        $this->assertEquals($achievement_log_entry, $last);

        // And now make sure the competency_achievement was added.
        $last = array_pop($returned);
        $this->assertEquals($mock2, $last);
    }

    /**
     * Test loading the log details for a straightforward scenario where user is assigned and then
     * achieves a value for the competency.
     */
    public function test_integration_single_assignment_achievement() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        $this->setAdminUser();

        $totara_hierarchy_generator = hierarchy_generator::instance();

        $scale = $totara_hierarchy_generator->create_scale(
            'comp',
            [],
            [
                ['name' => 'Great', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
                ['name' => 'Good', 'proficient' => 1, 'sortorder' => 2, 'default' => 1],
                ['name' => 'Bad', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
            ]
        );

        $compfw = $totara_hierarchy_generator->create_comp_frame(['scale' => $scale->id]);
        $comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency = new competency($comp);

        /** @var scale_value $great */
        $great = scale_value::repository()->where('name', '=', 'Great')->one();

        /** @var assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();
        $assignment = $assignment_generator->create_user_assignment($comp->id, $user->id);

        $model = new assignment_actions();
        $model->activate([$assignment->id]);

        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        /** @var competency_generator $totara_competency_generator */
        $totara_competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $pathway = $totara_competency_generator->create_test_pathway();
        $pathway->set_test_aggregate_current_value($great);
        $pathway->set_competency($competency);
        $pathway->save();

        if (!class_exists('pathway_test_pathway\achievement_detail')) {
            $test_achievement_detail = $this->getMockForAbstractClass(base_achievement_detail::class);
            class_alias(get_class($test_achievement_detail), 'pathway_test_pathway\achievement_detail');
        }

        $now = time();

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway, $pw_user_source]);
        $pathway_evaluator->aggregate($now++);

        $achievement_configuration = new achievement_configuration($competency);

        $comp_user_source = new competency_aggregator_user_source($source_table, true);
        $competency_aggregator = new competency_achievement_aggregator($achievement_configuration, $comp_user_source);
        $competency_aggregator->aggregate($now++);

        // Should trigger an entry in configuration_change.
        // Have put the time in the future to be more sure of it's order in the assertions below.
        $achievement_configuration->save_aggregation($now++);

        $activity_log_data = data_providers\activity_log::create($user->id, $comp->id)->fetch();

        $this->assertCount(5, $activity_log_data);
        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\assignment::class, $last);
        $this->assertEquals('Assigned: Admin User (Admin)', $last->get_description());
        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\assignment::class, $last);
        $this->assertEquals('Competency active: Achievement tracking started', $last->get_description());
        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\competency_achieved_via::class, $last);
        // Trying to mock the method that gives us 'Criteria met' strings doesn't work unfortunately, as the
        // a new instance of achievement_detail is created which doesn't have the mocked method.
        $this->assertEquals('Criteria met: . Achieved \'Great\' rating.', $last->get_description());
        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\competency_achievement::class, $last);
        $this->assertEquals('Rating: Great', $last->get_description());
        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\configuration_change::class, $last);
        $this->assertEquals('Overall rating calculation change', $last->get_description());
    }

    /**
     * Test loading the log details for a more complex history.
     */
    public function test_integration_multi_assignment() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $other_user = $this->getDataGenerator()->create_user();

        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'Audience One']);
        /** @var \totara_cohort\testing\generator $cohort_generator */
        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $cohort_generator->cohort_assign_users($cohort->id, [$user->id, $other_user->id]);

        $this->setAdminUser();

        $totara_hierarchy_generator = hierarchy_generator::instance();

        $scale = $totara_hierarchy_generator->create_scale(
            'comp',
            [],
            [
                ['name' => 'Great', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
                ['name' => 'Good', 'proficient' => 1, 'sortorder' => 2, 'default' => 1],
                ['name' => 'Bad', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
            ]
        );

        $compfw = $totara_hierarchy_generator->create_comp_frame(['scale' => $scale->id]);
        $comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency = new competency($comp);

        $other_comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $other_competency = new competency($other_comp);

        /** @var scale_value $great */
        $great = scale_value::repository()->where('name', '=', 'Great')->one();
        /** @var scale_value $good */
        $good = scale_value::repository()->where('name', '=', 'Good')->one();

        /** @var assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();
        $assignment = $assignment_generator->create_cohort_assignment($comp->id, $cohort->id);
        $other_competency_assignment = $assignment_generator->create_user_assignment($other_comp->id, $user->id);

        (new assignment_actions())->activate([$assignment->id, $other_competency_assignment->id]);
        (new expand_task($DB))->expand_all();

        /** @var competency_generator $\totara_competency\testing\generator */
        $totara_competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $pathway = $totara_competency_generator->create_test_pathway();
        $pathway->set_test_aggregate_current_value($great);
        $pathway->set_competency($competency);
        $pathway->save();

        if (!class_exists('pathway_test_pathway\achievement_detail')) {
            $test_achievement_detail = $this->getMockForAbstractClass(base_achievement_detail::class);
            class_alias(get_class($test_achievement_detail), 'pathway_test_pathway\achievement_detail');
        }

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $pw_user_source = new pathway_evaluator_user_source($source_table, true);
        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway, $pw_user_source]);
        $pathway_evaluator->aggregate();

        $comp_user_source = new competency_aggregator_user_source($source_table, true);
        (new competency_achievement_aggregator(new achievement_configuration($competency), $comp_user_source))->aggregate();

        // Now we create an achievement for the other competency, to ensure that this doesn't show up.
        $other_pathway = $totara_competency_generator->create_test_pathway();
        $other_pathway->set_test_aggregate_current_value($great);
        $other_pathway->set_competency($other_competency);
        $other_pathway->save();

        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway, $pw_user_source]);
        $pathway_evaluator->aggregate();
        (new competency_achievement_aggregator(new achievement_configuration($competency), $comp_user_source))->aggregate();

        $this->waitForSecond();

        // Note that we've set continuous tracking to true here.
        (new assignment_actions())->archive([$assignment->id], true);
        (new expand_task($DB))->expand_all();

        $pathway->set_test_aggregate_current_value($good);
        $pathway_evaluator = $this->getMockForAbstractClass(pathway_evaluator::class, [$pathway, $pw_user_source]);
        $pathway_evaluator->aggregate();
        (new competency_achievement_aggregator(new achievement_configuration($competency), $comp_user_source))->aggregate();

        $this->waitForSecond();

        $second_assignment = $assignment_generator->create_user_assignment($comp->id, $user->id);
        (new assignment_actions())->activate([$second_assignment->id]);
        (new expand_task($DB))->expand_all();

        // Should trigger an entry in configuration_change.
        // Have put the time in the future to be more sure of it's order in the assertions below.
        (new achievement_configuration($competency))->save_aggregation(time() + 1);

        $activity_log_data = data_providers\activity_log::create($user->id, $comp->id)->fetch();

        $this->assertCount(10, $activity_log_data);

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\assignment::class, $last);
        $this->assertEquals('Assigned: Audience One (Audience)', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\assignment::class, $last);
        $this->assertEquals('Competency active: Achievement tracking started', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\competency_achieved_via::class, $last);
        // Trying to mock the method that gives us 'Criteria met' strings doesn't work unfortunately, as the
        // a new instance of achievement_detail is created which doesn't have the mocked method.
        $this->assertEquals('Criteria met: . Achieved \'Great\' rating.', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\competency_achievement::class, $last);
        $this->assertEquals('Rating: Great', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\assignment::class, $last);
        $this->assertEquals('Unassigned: Audience One (Audience)', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\assignment::class, $last);
        $this->assertEquals('Assignment transferred for continuous tracking', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\competency_achieved_via::class, $last);
        $this->assertEquals('Criteria met: . Achieved \'Good\' rating.', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\competency_achievement::class, $last);
        $this->assertEquals('Rating: Good', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\assignment::class, $last);
        $this->assertEquals('Assigned: Admin User (Admin)', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\configuration_change::class, $last);
        $this->assertEquals('Overall rating calculation change', $last->get_description());

        // Now let's make sure filtering by assignment works.

        $activity_log_data = data_providers\activity_log::create($user->id, $comp->id)
            ->set_filters(['assignment_id' => $assignment->id])
            ->fetch();

        $this->assertCount(6, $activity_log_data);

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\assignment::class, $last);
        $this->assertEquals('Assigned: Audience One (Audience)', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\assignment::class, $last);
        $this->assertEquals('Competency active: Achievement tracking started', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\competency_achieved_via::class, $last);
        $this->assertEquals('Criteria met: . Achieved \'Great\' rating.', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\competency_achievement::class, $last);
        $this->assertEquals('Rating: Great', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\assignment::class, $last);
        $this->assertEquals('Unassigned: Audience One (Audience)', $last->get_description());

        $last = array_pop($activity_log_data);
        $this->assertInstanceOf(activity_log\configuration_change::class, $last);
        $this->assertEquals('Overall rating calculation change', $last->get_description());
    }

    /**
     * Test that only criteria config changes that happen after the first assignment are shown.
     * Also test that no config changes are shown if the user is not assigned to the competency.
     */
    public function test_config_change_before_assignment() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();

        /** @var competency_generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();

        $configuration_change = new configuration_change();
        $configuration_change->competency_id = $competency->id;
        $configuration_change->change_type = configuration_change::CHANGED_MIN_PROFICIENCY;
        $configuration_change->time_changed = time() - 5;
        $configuration_change->save();

        // Test that config doesn't show without assignment
        (new expand_task($DB))->expand_all();
        $this->assertEmpty(data_providers\activity_log::create($user->id, $competency->id)->fetch());

        /** @var assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();
        $user_assignment = $assignment_generator->create_user_assignment($competency->id, $user->id);
        (new assignment_actions())->activate([$user_assignment->id, $competency->id]);
        (new expand_task($DB))->expand_all();
        $this->waitForSecond();

        $configuration_change = new configuration_change();
        $configuration_change->competency_id = $competency->id;
        $configuration_change->change_type = configuration_change::CHANGED_AGGREGATION;
        $configuration_change->time_changed = time();
        $configuration_change->save();

        $this->waitForSecond();

        /** @var hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $pos_frame = $hierarchy_generator->create_pos_frame([]);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $pos_frame->id]);
        job_assignment::create([
            'userid' => $user->id,
            'positionid' => $pos->id,
            'idnumber' => 'xyz',
        ]);
        $pos_assignment = $assignment_generator->create_position_assignment($competency->id, $pos->id);
        (new assignment_actions())->activate([$pos_assignment->id, $competency->id]);

        $configuration_change = new configuration_change();
        $configuration_change->competency_id = $competency->id;
        $configuration_change->change_type = configuration_change::CHANGED_AGGREGATION;
        $configuration_change->time_changed = time() + 5;
        $configuration_change->save();

        (new expand_task($DB))->expand_all();

        $activity_log_data = data_providers\activity_log::create($user->id, $competency->id)->fetch();

        // The first log entry shouldn't be config change since it was before the assignment
        $this->assertNotInstanceOf(activity_log\configuration_change::class, end($activity_log_data));

        // Middle change log entry should exist
        $this->assertInstanceOf(activity_log\configuration_change::class, $activity_log_data[2]);

        // The latest log entry should be config change since it happened after assignment
        $this->assertInstanceOf(activity_log\configuration_change::class, $activity_log_data[0]);
    }

    /**
     * Test that when a competency no longer has a scale value a generic 'Rating value reset' string and 'Rating: None' is shown,
     * but only if there is a previous non-null rating that has been achieved.
     */
    public function test_scale_value_none() {
        /** @var competency_generator $hierarchy_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $user = $this->getDataGenerator()->create_user();
        $competency = $competency_generator->create_competency();
        $assignment = $competency_generator->assignment_generator()->create_assignment([
            'user_group_type' => 'user',
            'user_group_id' => $user->id,
            'competency_id' => $competency->id,
        ]);

        $time = time();
        $competency_id = $competency->id;
        $assignment_id = $assignment->id;
        $user_id = $user->id;

        /** @var hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchy_generator->create_scale(
            'comp',
            [],
            [
                ['name' => 'Great', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );
        $great = scale_value::repository()->where('name', '=', 'Great')->one();

        $achievement = new competency_achievement();
        $achievement->time_created = $time;
        $achievement->scale_value_id = null;
        $achievement->assignment_id = $assignment_id;
        $achievement->competency_id = $competency_id;
        $achievement->user_id = $user_id;
        $achievement->proficient = 1;
        $achievement->status = 1;
        $achievement->time_status = 0;
        $achievement->save();

        $achievement = new competency_achievement();
        $achievement->time_created = $time + 1;
        $achievement->scale_value_id = $great->id;
        $achievement->assignment_id = $assignment_id;
        $achievement->competency_id = $competency_id;
        $achievement->user_id = $user_id;
        $achievement->proficient = 1;
        $achievement->status = 1;
        $achievement->time_status = 0;
        $achievement->save();

        $achievement = new competency_achievement();
        $achievement->time_created = $time + 2;
        $achievement->scale_value_id = null;
        $achievement->assignment_id = $assignment_id;
        $achievement->competency_id = $competency_id;
        $achievement->user_id = $user_id;
        $achievement->proficient = 0;
        $achievement->status = 1;
        $achievement->time_status = 0;
        $achievement->save();

        $activity_log_data = data_providers\activity_log::create($user_id, $competency_id)->fetch();

        $this->assertCount(4, $activity_log_data);
        $this->assertEquals('Rating: None', $activity_log_data[0]->get_description());
        $this->assertEquals('Rating value reset', $activity_log_data[1]->get_description());
        $this->assertEquals('Rating: Great', $activity_log_data[2]->get_description());
        $this->assertEquals('Criteria met: . Achieved \'Great\' rating.', $activity_log_data[3]->get_description());
    }

    /**
     * Test log entries for multiple assignments with some having proficiency_override
     */
    public function test_proficiency_override(): void {
        global $DB;
        advanced_feature::enable('competency_assignment');

        $competency_generator = competency_generator::instance();
        $assignment_generator = new assignment_generator($competency_generator);

        $scale = $competency_generator->create_scale(
            'comp',
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
        $highest_scale_value = $scale_values->first();

        $framework = $competency_generator->create_framework($scale, 'Test framework');
        $position = $assignment_generator->create_position(['frameworkid' => $framework->id]);
        $user = $this->getDataGenerator()->create_user();
        job_assignment::create([
            'userid' => $user->id,
            'idnumber' => 'JobPosition',
            'positionid' => $position->id,
        ]);

        $user2 = $this->getDataGenerator()->create_user();

        /** @var competency $competency1 */
        $competency1 = $competency_generator->create_competency('Test competency 1', $framework);

        // Multiple assignments. All using the default min proficiency for now
        $user_asg1 = $assignment_generator->create_user_assignment($competency1->id, $user->id);
        $pos_asg1 = $assignment_generator->create_position_assignment($competency1->id, $position->id);
        $user_asg2 = $assignment_generator->create_user_assignment($competency1->id, $user2->id);
        (new expand_task($DB))->expand_all();

        // Order is important - must be in this order
        $expected = [
            'Assigned: Test Position 1 (Position)',
            'Competency active: Achievement tracking started',
            'Assigned: Admin User (Admin)',
        ];
        $activity_log_data = data_providers\activity_log::create($user->id, $competency1->id)->fetch();
        $this->verify_activity_log_entries($expected, $activity_log_data);

        // The activity log only include configuration changes that happened since the first assignment - so waiting for a tick
        $this->waitForSecond();

        // Change min proficiency for an assignment
        (new min_proficiency_override_for_assignments($highest_scale_value->id, [$pos_asg1->id, $user_asg1->id, $user_asg2->id]))->process();

        array_unshift($expected,
            "Minimum required proficiency value for assignment set to 'Arrived'",
            "Minimum required proficiency value for assignment set to 'Arrived'",
        );
        $activity_log_data = data_providers\activity_log::create($user->id, $competency1->id)->fetch();
        $this->verify_activity_log_entries($expected, $activity_log_data);

        // Ensuring this next override is later than the first
        $this->waitForSecond();

        // Reset min proficiency to default
        (new min_proficiency_override_for_assignments(null, [$pos_asg1->id]))->process();

        array_unshift($expected, 'Minimum required proficiency value for assignment removed');
        $activity_log_data = data_providers\activity_log::create($user->id, $competency1->id)->fetch();
        $this->verify_activity_log_entries($expected, $activity_log_data);

        // Now check with assignment filter
        $expected = [
            "Minimum required proficiency value for assignment set to 'Arrived'",
            'Competency active: Achievement tracking started',
            'Assigned: Admin User (Admin)',
        ];

        $activity_log_data = data_providers\activity_log::create($user->id, $competency1->id)
            ->set_filters(['assignment_id' => $user_asg1->id])
            ->fetch();
        $this->verify_activity_log_entries($expected, $activity_log_data);

        $expected = [
            'Minimum required proficiency value for assignment removed',
            "Minimum required proficiency value for assignment set to 'Arrived'",
            'Assigned: Test Position 1 (Position)',
        ];

        $activity_log_data = data_providers\activity_log::create($user->id, $competency1->id)
            ->set_filters(['assignment_id' => $pos_asg1->id])
            ->fetch();
        $this->verify_activity_log_entries($expected, $activity_log_data);
    }

    /**
     * @param string[] $expected
     * @param activity_log[] $actual
     */
    private function verify_activity_log_entries(array $expected, array $actual): void {
        $this->assertCount(count($expected), $actual);
        foreach ($actual as $idx => $row) {
            self::assertSame($expected[$idx], $row->get_description());
        }
    }
}
