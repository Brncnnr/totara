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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 * @subpackage test
 */

use core_phpunit\testcase;
use core\webapi\execution_context;
use pathway_criteria_group\entity\criteria_group as criteria_group_entity;
use pathway_criteria_group\entity\criteria_group_criterion as criteria_group_criterion_entity;
use pathway_criteria_group\webapi\resolver\query\achievements;
use totara_criteria\entity\criterion;
use totara_hierarchy\entity\scale_value;

defined('MOODLE_INTERNAL') || die();


/**
 * Tests the query to fetch all criterions within a criteria group
 *
 * @group totara_competency
 */
class pathway_criteria_group_webapi_resolver_query_achievements_testcase extends testcase {

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return execution_context::create($type, $operation);
    }

    public function test_load_for_non_existing_instance() {
        $this->setAdminUser();

        $args = ['instance_id' => 999];

        $result = achievements::resolve($args, $this->get_execution_context());
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_load_achievements_for_group() {
        $this->setAdminUser();

        $data = $this->create_data();

        $scale_values = scale_value::repository()
            ->where('scaleid', $data->scale->id)
            ->order_by('sortorder', 'asc')
            ->get();


        $criterion_amount = 1;
        foreach ($scale_values as $scale_value) {
            $criteria_group = new criteria_group_entity();
            $criteria_group->scale_value_id = $scale_value->id;
            $criteria_group->save();

            $expected_criterions = [];
            for ($i = 1; $i <= $criterion_amount; $i ++) {
                $type = 'linkedcourses'.$i;

                $criterion = new criterion();
                $criterion->aggregation_method = 1;
                $criterion->aggregation_params = '[]';
                $criterion->plugin_type = $type;
                $criterion->criterion_modified = 0;
                $criterion->save();

                $group_criterion = new criteria_group_criterion_entity();
                $group_criterion->criteria_group_id = $criteria_group->id;
                $group_criterion->criterion_type = $type;
                $group_criterion->criterion_id = $criterion->id;
                $group_criterion->save();

                $expected_criterions[] = [
                    'instance_id' => $group_criterion->criterion_id,
                    'type' => $group_criterion->criterion_type,
                ];
            }
            $criterion_amount++;
        }

        // Let's use the last group created
        $args = ['instance_id' => $criteria_group->id];

        $result = achievements::resolve($args, $this->get_execution_context());
        $this->assertEquals($expected_criterions, $result);
    }

    protected function create_data() {
        $data = new class() {
            public $scale;
        };

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator =  $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $data->scale = $hierarchy_generator->create_scale('comp');

        return $data;
    }

}