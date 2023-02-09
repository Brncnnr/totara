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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package pathway_learning_plan
 */

use core_phpunit\testcase;
use pathway_learning_plan\achievement_detail;
use pathway_manual\manual;
use totara_competency\expand_task;
use totara_competency\models\assignment_actions;
use totara_competency\achievement_configuration;
use totara_competency\aggregation_task;
use totara_competency\aggregation_users_table;
use totara_competency\competency_achievement_aggregator;
use totara_competency\competency_aggregator_user_source;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\pathway_achievement;
use pathway_learning_plan\learning_plan;
use totara_competency\pathway_factory;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class pathway_learning_plan_learning_plan_testcase extends testcase {

    /**
     * Test returned pathway type
     */
    public function test_pathway_type() {
        $this->assertSame('learning_plan', learning_plan::pathway_type());
    }


    public function test_aggregate_current_value() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_hierarchy\testing\generator $totara_hierarchy_generator */
        $totara_hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $scale = $totara_hierarchy_generator->create_scale(
            'comp',
            [],
            [
                ['name' => 'Great', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
                ['name' => 'Good', 'proficient' => 0, 'sortorder' => 2, 'default' => 1],
                ['name' => 'Bad', 'proficient' => 0, 'sortorder' => 3, 'default' => 0]
            ]
        );

        $compfw = $totara_hierarchy_generator->create_comp_frame(['scale' => $scale->id]);
        $comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency = new competency($comp);

        $lp_pathway = new learning_plan();
        $lp_pathway->set_competency($competency);
        $lp_pathway->save();

        $great = scale_value::repository()->where('name', '=', 'Great')->one();

        $record = new stdClass();
        $record->competency_id = $comp->id;
        $record->user_id = $user->id;
        $record->scale_value_id = $great->id;
        $record->date_assigned = time();
        $record->id = $DB->insert_record('dp_plan_competency_value', $record);

        $achievement_detail = $lp_pathway->aggregate_current_value($user->id);

        $this->assertEquals($great->id, $achievement_detail->get_scale_value_id());
    }

    public function test_integration() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $other_assigned_user = $this->getDataGenerator()->create_user();
        $not_assigned_user = $this->getDataGenerator()->create_user();

        /** @var \totara_hierarchy\testing\generator $totara_hierarchy_generator */
        $totara_hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $scale = $totara_hierarchy_generator->create_scale(
            'comp',
            [],
            [
                ['name' => 'Great', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
                ['name' => 'Good', 'proficient' => 0, 'sortorder' => 2, 'default' => 1],
                ['name' => 'Bad', 'proficient' => 0, 'sortorder' => 3, 'default' => 0]
            ]
        );

        $compfw = $totara_hierarchy_generator->create_comp_frame(['scale' => $scale->id]);
        $comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $competency = new competency($comp);

        $lp_pathway = new learning_plan();
        $lp_pathway->set_competency($competency);
        $lp_pathway->save();

        $great = scale_value::repository()->where('name', '=', 'Great')->one();

        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();
        $assignment = $assignment_generator->create_user_assignment($comp->id, $user->id);
        $other_assignment = $assignment_generator->create_user_assignment($comp->id, $other_assigned_user->id);
        (new assignment_actions())->activate([$assignment->id, $other_assignment->id]);
        (new expand_task($DB))->expand_all();

        $count = competency_achievement::repository()
            ->where('user_id', '=', $user->id)
            ->where('competency_id', '=', $comp->id)
            ->count();
        $this->assertEquals(0, $count);

        /** @var \totara_plan\testing\generator $plan_generator */
        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $plan = $plan_generator->create_learning_plan(['userid' => $user->id]);

        $this->setAdminUser();
        $plan_generator->add_learning_plan_competency($plan->id, $competency->id);

        /** @var dp_competency_component $competency_component */
        $competency_component = (new development_plan($plan->id))->get_component('competency');
        $competency_component->set_value($competency->id, $user->id, $great->id, new stdClass());
        // We won't set a value for other assigned user, we want to make sure they are excluded.
        $competency_component->set_value($competency->id, $not_assigned_user->id, $great->id, new stdClass());

        // Verify that a row was inserted in the aggregation queue
        $this->assertTrue($DB->record_exists('totara_competency_aggregation_queue',
            ['user_id' => $user->id, 'competency_id' => $comp->id, 'process_key' => null]
        ));
        // Run the task
        $this->run_aggregation_task();

        $pathway_achievement = pathway_achievement::get_current($lp_pathway, $user->id);
        $this->assertEquals($great->id, $pathway_achievement->scale_value_id);

        $pathway_achievement = pathway_achievement::get_current($lp_pathway, $other_assigned_user->id);
        $this->assertNull($pathway_achievement->scale_value_id);

        $pathway_achievement = pathway_achievement::get_current($lp_pathway, $not_assigned_user->id);
        $this->assertNull($pathway_achievement->scale_value_id);

        $source_table = new aggregation_users_table();
        $source_table->queue_for_aggregation($user->id, $competency->id);
        $comp_user_source = new competency_aggregator_user_source($source_table, true);
        (new competency_achievement_aggregator(new achievement_configuration($competency), $comp_user_source))->aggregate();

        $achievements = competency_achievement::repository()
            ->where('user_id', '=', $user->id)
            ->where('competency_id', '=', $comp->id)
            ->get();
        $this->assertCount(1, $achievements);
        $this->assertEquals($great->id, $achievements->first()->scale_value_id);
    }

    /**
     * Test validate
     */
    public function test_validate() {
        advanced_feature::enable('learningplans');

        // Enabled
        $learning_plan = new learning_plan();
        $learning_plan->validate();
        $this->assertTrue($learning_plan->is_valid());

        // Disabled
        advanced_feature::disable('learningplans');
        $learning_plan->validate();
        $this->assertFalse($learning_plan->is_valid());
    }

    public function test_achievement_detail_string() {
        $strings = (new achievement_detail())->get_achieved_via_strings();
        $this->assertCount(1, $strings);
        $this->assertEquals(get_string('achievement_detail', 'pathway_learning_plan'), reset($strings));
    }

    public function test_is_enabled() {
        $plan_pathway = new learning_plan();
        $other_pathway = new manual();

        $this->assertTrue($plan_pathway->is_enabled());
        $this->assertTrue($other_pathway->is_enabled());
        $this->assertContainsEquals('learning_plan', pathway_factory::get_pathway_types());

        advanced_feature::disable('learningplans');

        $this->assertFalse($plan_pathway->is_enabled());
        $this->assertTrue($other_pathway->is_enabled());
        $this->assertNotContainsEquals('learning_plan', pathway_factory::get_pathway_types());
    }

    private function run_aggregation_task() {
        (new aggregation_task(new aggregation_users_table(), false))->execute();
    }

}
