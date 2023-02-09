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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package criteria_othercompetency
 */

use core\orm\collection;
use core\orm\query\builder;
use core\webapi\query_resolver;
use criteria_othercompetency\webapi\resolver\query\achievements;
use totara_competency\user_groups;
use totara_criteria\criterion;

global $CFG;
require_once $CFG->dirroot . '/totara/criteria/tests/competency_achievements_testcase.php';

/**
 * Tests the query to fetch data for other competency achievements
 *
 * @group totara_competency
 */
class criteria_othercompetency_webapi_query_achievements_testcase extends totara_criteria_competency_achievements_testcase {

    /**
     * @var \totara_hierarchy\entity\competency[] $other_competency_items
     */
    private $other_competency_items;

    protected function tearDown(): void {
        parent::tearDown();
        $this->other_competency_items = null;
    }

    /**
     * @return string|query_resolver
     */
    protected function get_resolver_classname(): string {
        return achievements::class;
    }

    /**
     * Test return correct assigned and self_assignable value.
     */
    public function test_assigned_status_is_returned_correctly() {
        $this->setUser($this->data['user']);

        $args = [
            'instance_id'   => $this->data['criterion']->get_id(),
            'user_id'       => $this->data['user']->id,
        ];
        $result = $this->execute_resolver($args);

        /**
         * @var collection $items
         */
        $items = $result['items'];
        $item = $items->to_array()[0];
        $this->assertFalse($item['assigned']);
        $this->assertFalse($item['self_assignable']);
    }

    /**
     * Test it returns other competency correctly
     */
    public function test_resolve_return_correctly() {

        // Logging in...
        $this->setUser($this->data['user']);

        $args = [
            'instance_id'   => $this->data['criterion']->get_id(),
            'user_id'       => $this->data['user']->id,
        ];
        $result = $this->execute_resolver($args);

        /** @var collection $items */
        $items = $result['items'];

        $this->assertEquals(criterion::AGGREGATE_ALL, $result['aggregation_method']);
        $this->assertEquals(1, $result['required_items']);
        $this->assertInstanceOf(collection::class, $items);

        $this->assertEqualsCanonicalizing(
            [$this->data['competencies'][0]->id, $this->data['competencies'][1]->id, $this->data['competencies'][2]->id],
            $items->pluck('id')
        );
    }

    /**
     * Test competencies displays the same order as admin set
     */
    public function test_it_return_competencies_in_correct_order() {
        /** @var criterion $criterion */
        $criterion = $this->data['criterion'];

        // We want to test the order for all the competencies (different frameworks, sortthreads and IDs)
        $competencies = [];
        $competencies[] = $this->competency_generator->create_competency(
            null,
            null,
            ['sortthread' => '01.02']
        );
        $competencies[] = $this->competency_generator->create_competency(
            null,
            $competencies[0]->frameworkid,
            ['sortthread' => '01.01']
        );
        $competencies[] = $this->competency_generator->create_competency(
            null,
            $competencies[0]->frameworkid,
            ['sortthread' => '02']
        );

        // So we add them to the criterion here.
        $criterion->set_item_ids(array_column($competencies, 'id'))->save();

        // Logging in...
        $this->setUser($this->data['user']);

        /** @var \totara_hierarchy\entity\competency[] $competencies */
        $competencies = \totara_hierarchy\entity\competency::repository()
            ->where_in('id', $criterion->get_item_ids())
            ->order_by('sortthread')
            ->get()
            ->all();

        $args = [
            'instance_id'   => $criterion->get_id(),
            'user_id'       => $this->data['user']->id,
        ];
        $result = $this->execute_resolver($args);

        $items = $result['items']->to_array();
        for ($i = 0; $i < count($items); $i++) {
            $this->assertEquals($competencies[$i]->sortthread, $items[$i]['competency']->sortthread);
        }
    }

    public function create_data() {
        [$user, $role] = parent::create_data();

        $assignments = [];

        $competency = $this->competency_generator->create_competency();

        $this->other_competency_items = [];
        $this->other_competency_items[] = $this->competency_generator->create_competency('Other Comp 1');
        $this->other_competency_items[] = $this->competency_generator->create_competency('Other Comp 2');
        $this->other_competency_items[] = $this->competency_generator->create_competency('Other Comp 3');

        // Manually set the sortthread (sort order) of the competencies
        for ($i = 0; $i < count($this->other_competency_items); $i++) {
            $this->other_competency_items[$i]->sortthread = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            $this->other_competency_items[$i]->save();
        }

        $other_competency_ids = array_column($this->other_competency_items, 'id');

        // Ensure othercompetency plugin is enabled
        $enabled_setting = 'criteria_types_enabled';
        set_config($enabled_setting, 'othercompetency', 'totara_criteria');

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $criterion = $criteria_generator->create_othercompetency(['competencyids' => $other_competency_ids]);
        $criterion_with_no_competency = $criteria_generator->create_othercompetency(['competencyids' => []]);
        $valid_criterion = $criteria_generator->create_othercompetency(['competencyids' => $other_competency_ids])
            ->set_valid(true)
            ->save();
        $invalid_criterion = $criteria_generator->create_othercompetency(['competencyids' => $other_competency_ids])
            ->set_valid(false)
            ->save();

        $assignment = $this->competency_generator->assignment_generator()->create_assignment(
            [
                'user_group_type' => user_groups::USER,
                'user_group_id'   => $user->id,
                'competency_id'   => $competency->id,
            ]
        );

        $assignments[] = $this->competency_generator->assignment_generator()->create_assignment(
            [
                'user_group_type' => user_groups::USER,
                'user_group_id'   => $user->id,
                'competency_id'   => $this->other_competency_items[0]->id,
            ]
        );

        $assignments[] = $this->competency_generator->assignment_generator()->create_assignment(
            [
                'user_group_type' => user_groups::USER,
                'user_group_id'   => $user->id,
                'competency_id'   => $this->other_competency_items[1]->id,
            ]
        );

        $assignments[] = $this->competency_generator->assignment_generator()->create_assignment(
            [
                'user_group_type' => user_groups::USER,
                'user_group_id'   => $user->id,
                'competency_id'   => $this->other_competency_items[2]->id,
            ]
        );

        $proficient_value = builder::table('comp_scale_values')
            ->join('comp_scale_assignments', 'scaleid', 'scaleid')
            // ->where('comp_scale_assignments.frameworkid', $competencies[1]->frameworkid)
            ->where('proficient', 1)
            ->order_by('sortorder', 'desc')
            ->first();

        $not_proficient_value = builder::table('comp_scale_values')
            ->join('comp_scale_assignments', 'scaleid', 'scaleid')
            // ->where('comp_scale_assignments.frameworkid', $competencies[1]->frameworkid)
            ->where('proficient', 0)
            ->order_by('sortorder')
            ->first();


        return [
            'criterion'                    => $criterion,
            'criterion_with_no_competency' => $criterion_with_no_competency,
            'valid_criterion'              => $valid_criterion,
            'invalid_criterion'            => $invalid_criterion,
            'competencies'                 => $this->other_competency_items,
            'assignments'                  => $assignments,
            'assignment'                   => $assignment,
            'proficient_value'             => $proficient_value,
            'not_proficient_value'         => $not_proficient_value,
            'role'                         => $role,
            'user'                         => $user,
        ];
    }
}
