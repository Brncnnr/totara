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
 * @package totara_criteria
 * @subpackage test
 */

use core\orm\collection;
use core\orm\query\builder;
use core\orm\query\exceptions\multiple_records_found_exception;
use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use core\webapi\execution_context;
use core\webapi\query_resolver;
use totara_criteria\criterion;
use totara_criteria\criterion_not_found_exception;
use totara_hierarchy\entity\assignment_availability;
use totara_hierarchy\entity\competency;

/**
 * Tests the query to fetch data for competency achievements
 *
 * @group totara_competency
 */
abstract class totara_criteria_competency_achievements_testcase extends testcase {

    /**
     * Return resolver class.
     *
     * @return string|query_resolver
     */
    abstract protected function get_resolver_classname(): string;

    /**
     * Create testing data.
     *
     * @return array
     * @throws multiple_records_found_exception
     * @throws record_not_found_exception
     * @throws coding_exception
     */
    protected function create_data() {
        $user = $this->getDataGenerator()->create_user();

        $role = builder::table('role')
            ->where('shortname', 'user')
            ->one();

        $this->getDataGenerator()->role_assign($role->id, $user->id);

        // Assign capability
        assign_capability('totara/competency:view_own_profile', CAP_ALLOW, $role->id, context_user::instance($user->id));

        return [$user, $role];
    }

    protected $data;

    /**
     * @var \totara_competency\testing\generator
     */
    protected $competency_generator;

    protected function setUp(): void {
        parent::setUp();
        $this->competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $this->data = $this->create_data();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->data = null;
        $this->competency_generator = null;
    }

    protected function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return execution_context::create($type, $operation);
    }

    /**
     * Test it works if no competency is being found.
     */
    public function test_it_works_with_no_competencies() {

        // Logging in...
        $this->setUser($this->data['user']);

        $args = [
            'instance_id'   => $this->data['criterion_with_no_competency']->get_id(),
            'user_id'       => $this->data['user']->id,
        ];
        $result = $this->execute_resolver($args);

        /** @var collection $items */
        $items = $result['items'] ?? null;

        $this->assertEquals(criterion::AGGREGATE_ALL, $result['aggregation_method']);
        $this->assertEquals(1, $result['required_items']);
        $this->assertInstanceOf(collection::class, $items);

        $this->assertEmpty($items);
    }

    /**
     * Test throw criterion_not_found_exception if there is no criterion.
     */
    public function test_it_throws_not_found_exception_for_criterion() {
        $this->setUser($this->data['user']);

        $this->expectException(criterion_not_found_exception::class);

        $args = [
            'instance_id'   => 0,
            'user_id'       => $GLOBALS['USER']->id,
        ];
        $this->execute_resolver($args);
    }

    /**
     * Test throw required_capability_exception correctly when user is not assigned capability.
     */
    public function test_it_prohibit_to_view_competency_achievements() {
        $this->setUser($this->data['user']);
        // Let's take away the capability and check that there is an error
        unassign_capability(
            'totara/competency:view_own_profile',
            $this->data['role']->id
        );

        $args = [
            'instance_id'   => $this->data['criterion']->get_id(),
            'user_id'       => $this->data['user']->id,
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (View own competency profile)');
        $this->execute_resolver($args);
    }

    /**
     * Test self-assign competency button is not visible if it is hidden by admin.
     *
     * situations
     * 1. $can_assign = true , $competency->visible = false
     * 2. $can_assign = true , $competency->visible = true
     */
    public function test_assignable_should_be_invisible_if_hidden() {
        // Logging in...
        $this->setUser($this->data['user']);

        assign_capability(
            'totara/competency:assign_self',
            CAP_ALLOW,
            $this->data['role']->id,
            context_user::instance($this->data['user']->id)->id
        );

        /** @var criterion $criterion */
        $criterion = $this->data['criterion'];
        $first_competency = new competency($criterion->get_item_ids()[0]);

        $assignment_availability = new assignment_availability();
        $assignment_availability->availability = competency::ASSIGNMENT_CREATE_SELF;
        $assignment_availability->comp_id = $first_competency->id;
        $assignment_availability->save();

        $args = [
            'instance_id'   => $this->data['criterion']->get_id(),
            'user_id'       => $this->data['user']->id,
        ];
        $result = $this->execute_resolver($args);

        /** @var collection $items */
        $items = $result['items'];

        $item = $items->to_array()[0];

        $this->assertTrue((bool)$item['competency']->visible);
        $this->assertTrue($item['self_assignable']);

        $first_competency->visible = 0;
        $first_competency->save();

        $result = $this->execute_resolver($args);

        $items = $result['items'];
        $item = $items->to_array()[0];

        $this->assertFalse((bool)$item['competency']->visible);
        $this->assertFalse($item['self_assignable']);
    }

    public function test_criteria_validity_is_returned_correctly() {
        $this->setUser($this->data['user']);

        $args = [
            'instance_id'   => $this->data['valid_criterion']->get_id(),
            'user_id'       => $this->data['user']->id,
        ];
        $result = $this->execute_resolver($args);
        $this->assertTrue($result['is_valid']);

        $args = [
            'instance_id'   => $this->data['invalid_criterion']->get_id(),
            'user_id'       => $this->data['user']->id,
        ];
        $result = $this->execute_resolver($args);
        $this->assertNotTrue($result['is_valid']);
    }

    /**
     * Create achievement record.
     *
     * @param       $assignment
     * @param       $user_id
     * @param       $scale_value
     * @param array $attributes
     *
     * @return bool|int
     * @throws dml_exception
     */
    protected function create_achievement_record($assignment, $user_id, $scale_value, $attributes = []) {
        $attributes = array_merge(
            [
                'assignment_id'    => $assignment->id,
                'status'           => 0,
                'proficient'       => $scale_value->proficient,
                'user_id'          => $user_id,
                'competency_id'    => $assignment->competency_id,
                'scale_value_id'   => $scale_value->id,
                'time_created'     => time(),
                'time_status'      => time(),
                'time_proficient'  => time(),
                'time_scale_value' => time(),
                'last_aggregated'  => time(),
            ], $attributes
        );

        return builder::get_db()->insert_record('totara_competency_achievement', (object)$attributes);
    }

    /**
     * Get criteria data generator.
     *
     * @return \totara_criteria\testing\generator
     */
    protected function generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_criteria');
    }

    /**
     * Execute resolver.
     *
     * @param $args
     *
     * @return mixed
     */
    protected function execute_resolver($args) {
        return $this->get_resolver_classname()::resolve(
            [
                'instance_id'   => $args['instance_id'],
                'user_id'       => $args['user_id'],
            ], $this->get_execution_context()
        );
    }
}