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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package criteria_childcompetency
 */

use core\orm\collection;
use core\orm\query\builder;
use core\webapi\query_resolver;
use criteria_childcompetency\webapi\resolver\query\achievements;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\expand_task;
use totara_competency\user_groups;
use totara_criteria\criterion;
use totara_hierarchy\entity\assignment_availability;
use totara_hierarchy\entity\competency;

global $CFG;
require_once $CFG->dirroot . '/totara/criteria/tests/competency_achievements_testcase.php';

/**
 * Tests the query to fetch data for child competency achievements
 *
 * @group totara_competency
 */
class criteria_childcompetency_webapi_query_achievements_testcase extends totara_criteria_competency_achievements_testcase {
    /**
     * @return string|query_resolver
     */
    protected function get_resolver_classname(): string {
        return achievements::class;
    }

    /**
     * Test configuration display - aggregate all.
     */
    public function test_it_allows_to_view_own_child_competency_achievements_by_default() {
        // Logging in...
        $this->setUser($this->data['user']);

        // Righto, let's assert what we need.
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
            [$this->data['competencies'][1]->id, $this->data['competencies'][2]->id, $this->data['competencies'][3]->id],
            $items->pluck('id')
        );

        // Now let's assert that it returns correct achievement values

        // Competency 1
        $this->assertEquals($this->data['proficient_value']->id, $items->item($this->data['competencies'][1]->id)['value']->id);

        // Competency 2
        $this->assertNull($items->item($this->data['competencies'][2]->id)['value']);

        // Competency 3
        $this->assertNull($items->item($this->data['competencies'][3]->id)['value']);
    }

    /**
     * Test configuration display - aggregate all.
     */
    public function test_it_allows_to_view_other_child_competency_achievements() {

        $logged_user = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->role_assign($this->data['role']->id, $logged_user->id);
        assign_capability(
            'totara/competency:view_other_profile',
            CAP_ALLOW,
            $this->data['role']->id,
            context_user::instance($this->data['user']->id)
        );

        $this->setUser($logged_user);

        // Righto, let's assert what we need.
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
            [$this->data['competencies'][1]->id, $this->data['competencies'][2]->id, $this->data['competencies'][3]->id],
            $items->pluck('id')
        );

        // Now let's assert that it returns correct achievement values

        // Competency 1
        $this->assertEquals($this->data['proficient_value']->id, $items->item($this->data['competencies'][1]->id)['value']->id);

        // Competency 2
        $this->assertNull($items->item($this->data['competencies'][2]->id)['value']);

        // Competency 3
        $this->assertNull($items->item($this->data['competencies'][3]->id)['value']);

        // Now let's take away the capability and check that there is an error
        assign_capability(
            'totara/competency:view_other_profile',
            CAP_PROHIBIT,
            $this->data['role']->id,
            context_user::instance($this->data['user']->id),
            true
        );

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (View profile of other users)');
        $this->execute_resolver($args);
    }

    /**
     * Test the assigned status and self assignable of child competency is returned correctly.
     */
    public function test_it_returns_whether_child_competency_has_been_assigned() {
        // Redirecting events to ensure observers don't interfere with the test
        $sink = $this->redirectEvents();

        // Logging in...
        $this->setUser($this->data['user']);

        // Let's create a competency with no assignment...
        $na_competency = $this->competency_generator->create_competency(
            null,
            $this->data['competencies'][0]->frameworkid,
            ['parentid' => $this->data['competencies'][0]->id]
        ); // No value

        // Archived competency
        $archived_competency = $this->competency_generator->create_competency(
            null,
            $this->data['competencies'][0]->frameworkid,
            ['parentid' => $this->data['competencies'][0]->id]
        );
        $archived_assignment = $this->competency_generator
            ->assignment_generator()
            ->create_assignment(
                [
                    'user_group_type' => user_groups::USER,
                    'user_group_id'   => $this->data['user']->id,
                    'competency_id'   => $archived_competency->id,
                    'archived'        => assignment::STATUS_ARCHIVED,
                ]
            );

        // Let's make some competencies self-assignable
        $comp = new competency($this->data['competencies'][2]->id);
        $comp->availability()->save((new assignment_availability())->set_attribute('availability', 1));
        $comp->availability()->save((new assignment_availability())->set_attribute('availability', 2));

        // Let's make some competencies self-assignable
        $comp = new competency($this->data['competencies'][3]->id);
        $comp->availability()->save((new assignment_availability())->set_attribute('availability', 2));

        // Let's make some competencies self-assignable
        $comp = new competency($na_competency->id);
        $comp->availability()->save((new assignment_availability())->set_attribute('availability', 1));

        // Fake log entry
        builder::table('totara_competency_assignment_user_logs')
            ->insert(
                [
                    'assignment_id' => $archived_assignment->id,
                    'user_id'       => $this->data['user']->id,
                    'action'        => competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED,
                    'created_at'    => time(),
                ]
            );

        (new expand_task(builder::get_db()))->expand_all();

        // Righto, let's assert what we need.
        $args = [
            'instance_id'   => $this->data['criterion']->get_id(),
            'user_id'       => $this->data['user']->id,
        ];
        $result = $this->execute_resolver($args);

        /** @var collection $items */
        $items = $result['items'] ?? null;

        $this->assertEqualsCanonicalizing(
            [
                $this->data['competencies'][1]->id,
                $this->data['competencies'][2]->id,
                $this->data['competencies'][3]->id,
                $na_competency->id,
                $archived_competency->id,
            ],
            $items->pluck('id')
        );

        // Now let's assert that it returns correct achievement values

        // Competency 1
        $this->assertEquals($this->data['proficient_value']->id, $items->item($this->data['competencies'][1]->id)['value']->id);
        $this->assertFalse($items->item($this->data['competencies'][1]->id)['self_assignable']);
        $this->assertTrue($items->item($this->data['competencies'][1]->id)['assigned']);

        // Competency 2
        $this->assertNull($items->item($this->data['competencies'][2]->id)['value']);
        $this->assertTrue($items->item($this->data['competencies'][2]->id)['self_assignable']);
        $this->assertTrue($items->item($this->data['competencies'][2]->id)['assigned']);

        // Competency 3
        $this->assertNull($items->item($this->data['competencies'][3]->id)['value']);
        $this->assertFalse($items->item($this->data['competencies'][3]->id)['self_assignable']);
        $this->assertTrue($items->item($this->data['competencies'][3]->id)['assigned']);

        // Competency 4
        $this->assertNull($items->item($na_competency->id)['value']);
        $this->assertTrue($items->item($na_competency->id)['self_assignable']);
        $this->assertFalse($items->item($na_competency->id)['assigned']);

        // Competency 5
        $this->assertNull($items->item($archived_competency->id)['value']);
        $this->assertFalse($items->item($archived_competency->id)['self_assignable']);
        $this->assertTrue($items->item($archived_competency->id)['assigned']);

        $sink->close();
    }

    /**
     * Test competencies displays the same order as admin set
     */
    public function test_it_return_competencies_in_correct_order() {
        /** @var criterion $criterion */
        $criterion = $this->data['criterion'];

        // Logging in...
        $this->setUser($this->data['user']);

        /** @var competency[] $competencies */
        $competencies = $this->data['competencies'];

        $competencies[1]->sortthread = '01.03';
        $competencies[1]->save();

        $competencies[2]->sortthread = '01.02';
        $competencies[2]->save();

        $competencies[3]->sortthread = '01.01';
        $competencies[3]->save();

        $expected_competencies_order = [
            $competencies[3]->id,
            $competencies[2]->id,
            $competencies[1]->id,
        ];

        $args = [
            'instance_id'   => $criterion->get_id(),
            'user_id'       => $this->data['user']->id,
        ];
        $result = $this->execute_resolver($args);

        $actual_competencies_order = array_column($result['items']->to_array(), 'competency');

        $this->assertEquals($expected_competencies_order, array_column($actual_competencies_order, 'id'));
    }

    protected function create_data() {
        [$user, $role] = parent::create_data();

        $competencies = [];
        $assignments = [];

        // Create competency
        // Create another competency
        // Create child competency
        // Create another child competency
        $competencies[] = $this->competency_generator->create_competency();

        // These 3 we're looking for
        $competencies[] = $this->competency_generator->create_competency(
            null,
            $competencies[0]->frameworkid,
            ['parentid' => $competencies[0]->id]
        ); // Achieved
        $competencies[] = $this->competency_generator->create_competency(
            null,
            $competencies[0]->frameworkid,
            ['parentid' => $competencies[0]->id]
        ); // Not achieved
        $competencies[] = $this->competency_generator->create_competency(
            null,
            $competencies[0]->frameworkid,
            ['parentid' => $competencies[0]->id]
        ); // No value

        $competencies[] = $this->competency_generator->create_competency(
            null,
            $competencies[0]->frameworkid,
            ['parentid' => $competencies[1]->id]
        );
        $competencies[] = $this->competency_generator->create_competency();

        // Create child competency criterion
        $criterion = $this->generator()->create_childcompetency(
            [
                'aggregation' => [
                    'method' => criterion::AGGREGATE_ALL,
                ],
                'competency'  => $competencies[0]->id,
            ]
        );

        // Create child competency criterion with no competency
        $criterion_with_no_competency = $this->generator()->create_childcompetency(
            [
                'aggregation' => [
                    'method' => criterion::AGGREGATE_ALL,
                ],
                'competency'  => $competencies[2]->id,
            ]
        );

        $valid_criterion = $this->generator()->create_childcompetency(['competency' => $competencies[2]->id])
            ->set_valid(true)
            ->save();
        $invalid_criterion = $this->generator()->create_childcompetency(['competency' => $competencies[2]->id])
            ->set_valid(false)
            ->save();

        // This is the main user assignment for a parent competency
        $assignment = $this->competency_generator->assignment_generator()->create_assignment(
            [
                'user_group_type' => user_groups::USER,
                'user_group_id'   => $user->id,
                'competency_id'   => $competencies[0]->id,
            ]
        );

        $assignments[] = $this->competency_generator->assignment_generator()->create_assignment(
            [
                'user_group_type' => user_groups::USER,
                'user_group_id'   => $user->id,
                'competency_id'   => $competencies[1]->id,
            ]
        );

        $assignments[] = $this->competency_generator->assignment_generator()->create_assignment(
            [
                'user_group_type' => user_groups::USER,
                'user_group_id'   => $user->id,
                'competency_id'   => $competencies[2]->id,
            ]
        );

        $assignments[] = $this->competency_generator->assignment_generator()->create_assignment(
            [
                'user_group_type' => user_groups::USER,
                'user_group_id'   => $user->id,
                'competency_id'   => $competencies[3]->id,
            ]
        );

        $proficient_value = builder::table('comp_scale_values')
            ->join('comp_scale_assignments', 'scaleid', 'scaleid')
            ->where('comp_scale_assignments.frameworkid', $competencies[1]->frameworkid)
            ->where('proficient', 1)
            ->order_by('sortorder')
            ->first();

        $not_proficient_value = builder::table('comp_scale_values')
            ->join('comp_scale_assignments', 'scaleid', 'scaleid')
            ->where('comp_scale_assignments.frameworkid', $competencies[1]->frameworkid)
            ->where('proficient', 0)
            ->order_by('sortorder')
            ->first();

        // Let's create bogus achievement values
        $this->create_achievement_record($assignments[0], $user->id, $proficient_value);
        $this->create_achievement_record($assignments[1], $user->id, $not_proficient_value);

        return [
            'criterion'                    => $criterion,
            'criterion_with_no_competency' => $criterion_with_no_competency,
            'valid_criterion'              => $valid_criterion,
            'invalid_criterion'            => $invalid_criterion,
            'competencies'                 => $competencies,
            'assignments'                  => $assignments,
            'assignment'                   => $assignment,
            'proficient_value'             => $proficient_value,
            'not_proficient_value'         => $not_proficient_value,
            'role'                         => $role,
            'user'                         => $user,
        ];
    }
}
