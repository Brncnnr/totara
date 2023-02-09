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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\pathway_factory;
use totara_criteria\criterion;

/**
 * @group totara_competency
 */
class totara_competency_pathway_factory_testcase extends \advanced_testcase {

    /**
     * Test create invalid type
     */
    public function test_create_invalid_type() {
        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Pathway type 'Invalid' not found.");
        $instance = pathway_factory::create('Invalid');
    }

    /**
     * Test create
     */
    public function test_create() {
        $instance = pathway_factory::create('criteria_group');
        $this->assertSame('criteria_group', $instance->get_path_type());
    }

    /**
     * Test fetch invalid type
     */
    public function test_fetch_invalid_type() {
        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Pathway type 'Invalid' not found.");
        $instance = pathway_factory::fetch('Invalid', 123);
    }

    /**
     * Test fetch
     */
    public function test_fetch() {
        global $DB;

        // Setup some data
        // Courses
        $courses = [];

        $prefix = 'Course ';
        for ($i = 1; $i <= 5; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }

        // Coursecompletions
        $crit_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $cc1 = $crit_generator->create_coursecompletion([
            'aggregation' => criterion::AGGREGATE_ALL,
            'courseids' => [$courses[1]->id, $courses[2]->id],
        ]);

        $cc2 = $crit_generator->create_coursecompletion([
            'aggregation' => criterion::AGGREGATE_ANY_N,
            'req_items' => 1,
            'courseids' => [$courses[1]->id, $courses[3]->id, $courses[5]->id],
        ]);

        // Competency
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $scale = $hierarchygenerator->create_scale('comp');
        $scalevalueid = $DB->get_field('comp_scale_values', 'id', ['scaleid' => $scale->id], IGNORE_MULTIPLE);

        $compfw = $hierarchygenerator->create_comp_frame(['scale' => $scale->id]);
        $comp = $hierarchygenerator->create_comp(['frameworkid' => $compfw->id]);

        // Criteria_group pathway
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $cg = $generator->create_criteria_group($comp->id, [$cc1, $cc2], $scalevalueid);

        // Now the actual test
        $instance = pathway_factory::fetch('criteria_group', $cg->get_id());
        $this->assertSame('criteria_group', $instance->get_path_type());
        $this->assertSame($cg->get_id(), $instance->get_id());
        $this->assertSame($cg->get_competency()->get_attribute('id'), $instance->get_competency()->get_attribute('id'));
        $this->assertTrue($instance->is_active());
        $this->assertSame(2, count($instance->get_criteria()));
    }

    public function test_get_single_value_types() {
        $types = pathway_factory::get_single_value_types();

        $this->assertEqualsCanonicalizing(
            [
                'criteria_group',
            ],
            $types
        );
    }

    public function test_get_multi_value_types() {
        $types = pathway_factory::get_multi_value_types();

        $this->assertEqualsCanonicalizing(
            [
                'manual',
                'learning_plan',
                'perform_rating',
            ],
            $types
        );
    }

    public function test_get_pathway_types() {
        $types = pathway_factory::get_pathway_types();

        $this->assertEqualsCanonicalizing(
            [
                'manual',
                'learning_plan',
                'criteria_group',
                'perform_rating',
            ],
            $types
        );
    }

}
