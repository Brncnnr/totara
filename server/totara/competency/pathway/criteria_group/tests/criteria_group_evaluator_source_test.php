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
use criteria_linkedcourses\linkedcourses;
use pathway_criteria_group\criteria_group;
use pathway_criteria_group\criteria_group_evaluator_user_source;
use totara_competency\aggregation_users_table;
use totara_competency\entity\pathway_achievement;
use totara_criteria\criterion;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale;

/**
 * @group totara_competency
 */
class pathway_criteria_group_evaluator_source_testcase extends testcase {

    /**
     * Test save new
     */
    public function test_get_users_to_reaggregate_perform() {
        \totara_core\advanced_feature::enable('competency_assignment');

        /** @var \totara_hierarchy\testing\generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $scale = $hierarchygenerator->create_scale('comp');
        $scale = new scale($scale);
        $framework = $hierarchygenerator->create_comp_frame(['scale' => $scale->id]);
        $comp = $hierarchygenerator->create_comp(['frameworkid' => $framework->id]);
        $comp = new competency($comp->id);

        $user = $this->getDataGenerator()->create_user();

        $queue_table = new aggregation_users_table();
        $queue_table->queue_for_aggregation($user->id, $comp->id);

        $criteriion = new linkedcourses();
        $criteriion->set_aggregation_method(criterion::AGGREGATE_ALL);
        $criteriion->set_competency_id($comp->id);

        $pathway = new criteria_group();
        $pathway->set_competency($comp)
            ->set_scale_value($scale->min_proficient_value)
            ->set_sortorder(1)
            ->add_criterion($criteriion)
            ->save();

        $source = new criteria_group_evaluator_user_source($queue_table);
        // Has changed is 0 so it should not be picked up
        $actual_users = $source->get_users_to_reaggregate($pathway);
        $this->assertInstanceOf(moodle_recordset::class, $actual_users);
        $actual_users = iterator_to_array($actual_users);
        $this->assertEmpty($actual_users);

        builder::get_db()->execute("UPDATE {{$queue_table->get_table_name()}} SET {$queue_table->get_has_changed_column()} = 1");
        $actual_users = $source->get_users_to_reaggregate($pathway);
        $actual_users = iterator_to_array($actual_users);
        $this->assertCount(1, $actual_users);

        $expected = (object)[
            'user_id' => $user->id,
            'achievement_id' => null,
            'scale_value_id' => null
        ];
        $this->assertEquals($expected, reset($actual_users));

        $achievement = pathway_achievement::get_current($pathway, $user->id);

        $actual_users = $source->get_users_to_reaggregate($pathway);
        $actual_users = iterator_to_array($actual_users);
        $expected = (object)[
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'scale_value_id' => null
        ];
        $this->assertEquals($expected, reset($actual_users));

        $achievement->scale_value_id = $scale->minproficiencyid;
        $achievement->save();

        $actual_users = $source->get_users_to_reaggregate($pathway);
        $actual_users = iterator_to_array($actual_users);
        $expected = (object)[
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'scale_value_id' => $scale->minproficiencyid
        ];
        $this->assertEquals($expected, reset($actual_users));

        // Archiving the old one will prompt to create a new one
        $achievement->archive();
        $achievement2 = pathway_achievement::get_current($pathway, $user->id);
        $this->assertNotEquals($achievement2->id, $achievement->id);

        $actual_users = $source->get_users_to_reaggregate($pathway);
        $actual_users = iterator_to_array($actual_users);
        $expected = (object)[
            'user_id' => $user->id,
            'achievement_id' => $achievement2->id,
            'scale_value_id' => null
        ];
        $this->assertEquals($expected, reset($actual_users));

        // Archiving will result in no achievement returned
        $achievement->archive();

        $actual_users = $source->get_users_to_reaggregate($pathway);
        $actual_users = iterator_to_array($actual_users);
        $expected = (object)[
            'user_id' => $user->id,
            'achievement_id' => null,
            'scale_value_id' => null
        ];
        $this->assertEquals($expected, reset($actual_users));
    }

    /**
     * Test save new
     */
    public function test_get_users_to_reaggregate_learn() {
        \totara_core\advanced_feature::disable('competency_assignment');

        /** @var \totara_hierarchy\testing\generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $scale = $hierarchygenerator->create_scale('comp');
        $scale = new scale($scale);
        $framework = $hierarchygenerator->create_comp_frame(['scale' => $scale->id]);
        $comp = $hierarchygenerator->create_comp(['frameworkid' => $framework->id]);
        $comp = new competency($comp->id);

        $user = $this->getDataGenerator()->create_user();

        $queue_table = new aggregation_users_table();
        $queue_table->queue_for_aggregation($user->id, $comp->id);

        $criteriion = new linkedcourses();
        $criteriion->set_aggregation_method(criterion::AGGREGATE_ALL);
        $criteriion->set_competency_id($comp->id);

        $pathway = new criteria_group();
        $pathway->set_competency($comp)
            ->set_scale_value($scale->min_proficient_value)
            ->set_sortorder(1)
            ->add_criterion($criteriion)
            ->save();

        $source = new criteria_group_evaluator_user_source($queue_table);
        // Has changed is 0 so it should not be picked up
        $actual_users = $source->get_users_to_reaggregate($pathway);
        $this->assertInstanceOf(moodle_recordset::class, $actual_users);
        $actual_users = iterator_to_array($actual_users);
        $this->assertEmpty($actual_users);

        builder::get_db()->execute("UPDATE {{$queue_table->get_table_name()}} SET {$queue_table->get_has_changed_column()} = 1");
        $actual_users = $source->get_users_to_reaggregate($pathway);
        $actual_users = iterator_to_array($actual_users);
        $this->assertCount(1, $actual_users);

        $expected = (object)[
            'user_id' => $user->id,
            'achievement_id' => null,
            'scale_value_id' => null
        ];
        $this->assertEquals($expected, reset($actual_users));

        $achievement = pathway_achievement::get_current($pathway, $user->id);

        // If there's an achievement with no scale value we will get a result
        $actual_users = $source->get_users_to_reaggregate($pathway);
        $actual_users = iterator_to_array($actual_users);
        $expected = (object)[
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'scale_value_id' => null
        ];
        $this->assertEquals($expected, reset($actual_users));

        // Now make sure we have a scale value
        $achievement->scale_value_id = $scale->minproficiencyid;
        $achievement->save();

        // This should now return nothing
        $actual_users = $source->get_users_to_reaggregate($pathway);
        $actual_users = iterator_to_array($actual_users);
        $this->assertCount(0, $actual_users);
    }

}
