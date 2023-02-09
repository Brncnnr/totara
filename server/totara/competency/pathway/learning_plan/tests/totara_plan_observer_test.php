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
 * @package pathway_learning_plan
 */

use core_phpunit\testcase;
use core\orm\query\builder;
use pathway_learning_plan\learning_plan;
use totara_competency\aggregation_users_table;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\pathway;
use totara_competency\expand_task;
use totara_competency\hook\competency_configuration_changed;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class pathway_learning_plan_totara_plan_observer_testcase extends testcase {

    public function test_event_for_competency_with_lp_pathway() {
        advanced_feature::enable('competency_assignment');

        $this->setAdminUser();

        $data = $this->setup_data();

        // No learning plan pathway being set up for competency here.
        // Let's just make sure none is being added by default
        $this->assertFalse(pathway::repository()->where('path_type', 'learning_plan')->exists());

        $lp_pathway = new learning_plan();
        $lp_pathway->set_competency($data->competency);
        $lp_pathway->save();

        // Pathways are usually created through the API which results in the competency_configuration_changed hook being triggered
        // Simulating it here
        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($data->competency->id);
        $hook->execute();

        $great = scale_value::repository()->where('name', '=', 'Great')->one();
        $good = scale_value::repository()->where('name', '=', 'Good')->one();

        $count = competency_achievement::repository()
            ->where('user_id', '=', $data->user->id)
            ->where('competency_id', '=', $data->competency->id)
            ->count();
        $this->assertEquals(0, $count);

        $source_table = new aggregation_users_table();
        $builder = builder::table($source_table->get_table_name())
            ->where($source_table->get_user_id_column(), $data->user->id)
            ->where($source_table->get_competency_id_column(), $data->competency->id)
            ->where($source_table->get_process_key_column(), null);

        // All assigned users are queued for aggregation
        $this->assertTrue($builder->exists());
        $source_table->truncate();

        $development_plan = new development_plan($data->plan->id);
        /** @var dp_competency_component $component */
        $component = $development_plan->get_component('competency');
        $component->set_value($data->competency->id, $data->user->id, $great->id, new stdClass());

        // Verify that a row was inserted in the aggregation queue
        $builder = builder::table($source_table->get_table_name())
            ->where($source_table->get_user_id_column(), $data->user->id)
            ->where($source_table->get_competency_id_column(), $data->competency->id)
            ->where($source_table->get_process_key_column(), null);

        $this->assertTrue($builder->exists());
        $source_table->truncate();

        $this->assertFalse($builder->exists());

        // Part 2 of this test (as we have data set up already): account for pathways being archived.
        $lp_pathway->delete();

        // Pathways are usually deleted through the API which results in the competency_configuration_changed hook being triggered
        // Simulating it here
        /** @var competency_configuration_changed $hook */
        $hook = new competency_configuration_changed($data->competency->id);
        $hook->execute();

        $component->set_value($data->competency->id, $data->user->id, $good->id, new stdClass());

        // As the user is still assigned to the competency
        $this->assertTrue($builder->exists());
    }

    public function test_event_for_competency_without_lp_pathway() {
        \totara_core\advanced_feature::enable('competency_assignment');

        $this->setAdminUser();

        $data = $this->setup_data();

        // No learning plan pathway being set up for competency here.
        // Let's just make sure none is being added by default:
        $this->assertFalse(pathway::repository()->where('path_type', 'learning_plan')->exists());

        $great = scale_value::repository()->where('name', '=', 'Great')->one();

        $count = competency_achievement::repository()
            ->where('user_id', '=', $data->user->id)
            ->where('competency_id', '=', $data->competency->id)
            ->count();
        $this->assertEquals(0, $count);

        $development_plan = new development_plan($data->plan->id);
        /** @var dp_competency_component $component */
        $component = $development_plan->get_component('competency');
        $component->set_value($data->competency->id, $data->user->id, $great->id, new stdClass());

        // Verify that a row was inserted in the aggregation queue
        $source_table = new aggregation_users_table();

        $this->assertFalse(
            builder::table($source_table->get_table_name())
                ->where($source_table->get_user_id_column(), $data->user->id)
                ->where($source_table->get_competency_id_column(), $data->competency->id)
                ->where($source_table->get_process_key_column(), null)
                ->exists()
        );
    }

    public function test_event_for_competency_with_lp_pathway_learn_only() {
        \totara_core\advanced_feature::disable('competency_assignment');

        $this->setAdminUser();

        $data = $this->setup_data();

        // No learning plan pathway being set up for competency here.
        // Let's just make sure none is being added by default:
        $this->assertFalse(pathway::repository()->where('path_type', 'learning_plan')->exists());

        $lp_pathway = new learning_plan();
        $lp_pathway->set_competency($data->competency);
        $lp_pathway->save();

        $great = scale_value::repository()->where('name', '=', 'Great')->one();
        $good = scale_value::repository()->where('name', '=', 'Good')->one();

        $source_table = new aggregation_users_table();
        $builder = builder::table($source_table->get_table_name())
            ->where($source_table->get_user_id_column(), $data->user->id)
            ->where($source_table->get_competency_id_column(), $data->competency->id)
            ->where($source_table->get_process_key_column(), null);

        $this->assertFalse($builder->exists());

        $development_plan = new development_plan($data->plan->id);
        /** @var dp_competency_component $component */
        $component = $development_plan->get_component('competency');
        $component->set_value($data->competency->id, $data->user->id, $great->id, new stdClass());

        // Verify that a row was inserted in the aggregation queue
        $this->assertTrue($builder->exists());

        $source_table->truncate();
        $this->assertFalse($builder->exists());

        // Part 2 of this test (as we have data set up already): account for pathways being archived.
        $lp_pathway->delete();

        $component->set_value($data->competency->id, $data->user->id, $good->id, new stdClass());

        // In learn only we always queue regardless of non-existing pathways
        $this->assertTrue($builder->exists());
    }

    public function test_event_for_competency_without_lp_pathway_learn_only() {
        \totara_core\advanced_feature::disable('competency_assignment');

        $this->setAdminUser();

        $data = $this->setup_data();

        // No learning plan pathway being set up for competency here.
        // Let's just make sure none is being added by default:
        $this->assertFalse(pathway::repository()->where('path_type', 'learning_plan')->exists());

        $great = scale_value::repository()->where('name', '=', 'Great')->one();

        $count = competency_achievement::repository()
            ->where('user_id', '=', $data->user->id)
            ->where('competency_id', '=', $data->competency->id)
            ->count();
        $this->assertEquals(0, $count);

        $development_plan = new development_plan($data->plan->id);
        /** @var dp_competency_component $component */
        $component = $development_plan->get_component('competency');
        $component->set_value($data->competency->id, $data->user->id, $great->id, new stdClass());

        // Verify that a row was inserted in the aggregation queue
        $source_table = new aggregation_users_table();

        // In learn only we always queue regardless of non-existing pathways
        $this->assertTrue(
            builder::table($source_table->get_table_name())
                ->where($source_table->get_user_id_column(), $data->user->id)
                ->where($source_table->get_competency_id_column(), $data->competency->id)
                ->where($source_table->get_process_key_column(), null)
                ->exists()
        );
    }

    protected function setup_data() {
        $data = new class {
            public $user;
            public $scale;
            public $competency;
            public $plan;
            public $assignment;
        };

        $sink = $this->redirectEvents();

        $data->user = $this->getDataGenerator()->create_user();

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
        $data->competency = new competency($comp);

        /** @var \totara_plan\testing\generator $plan_generator */
        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $data->plan = $plan_generator->create_learning_plan(['userid' => $data->user->id]);
        $plan_generator->add_learning_plan_competency($data->plan->id, $data->competency->id);

        $competency_generator = \totara_competency\testing\generator::instance();
        $assignment_generator = $competency_generator->assignment_generator();
        $data->assignment = $assignment_generator->create_user_assignment($data->competency->id, $data->user->id);
        (new expand_task($GLOBALS['DB']))->expand_all();

        $sink->close();

        return $data;
    }

}
