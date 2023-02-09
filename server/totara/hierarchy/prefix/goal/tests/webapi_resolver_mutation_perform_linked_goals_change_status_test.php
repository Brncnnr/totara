<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

use core\date_format;
use core\webapi\formatter\field\date_field_formatter;
use hierarchy_goal\entity\perform_status;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once __DIR__ . '/perform_linked_goals_base_testcase.php';

/**
 * @group hierarchy_goal
 */
class hierarchy_goal_webapi_resolver_mutation_linked_goals_change_status_testcase extends perform_linked_goals_base_testcase {

    use webapi_phpunit_helper;

    private const MUTATION = 'hierarchy_goal_perform_linked_goals_change_status';

    private $data;

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        parent::setUp();

        $this->data = $this->create_activity_data(goal::SCOPE_PERSONAL);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->data = null;
    }

    private function resolve_mutation(array $args = []): array {
        $scale_value = $this->data->scale->values->first();
        $args = array_merge([
            'goal_assignment_id' => $this->data->goal1->id,
            'goal_type' => 'PERSONAL',
            'participant_instance_id' => $this->data->manager_participant_instance1->id,
            'section_element_id' => $this->data->section_element->id,
            'scale_value_id' => $scale_value->id,
        ], $args);

        return $this->resolve_graphql_mutation(self::MUTATION, ['input' => $args]);
    }

    public function test_resolve_mutation_successful(): void {
        self::setUser($this->data->manager_user);
        self::assertEquals(0, perform_status::repository()->count());

        $result1 = $this->resolve_mutation();
        self::assertArrayHasKey('perform_status', $result1);
        self::assertArrayNotHasKey('already_exists', $result1);
        self::assertEquals(1, perform_status::repository()->count());
        self::assertEquals(perform_status::repository()->one()->id, $result1['perform_status']->id);

        $result2 = $this->resolve_mutation();
        self::assertArrayHasKey('perform_status', $result2);
        self::assertTrue($result2['already_exists']);
        self::assertEquals(1, perform_status::repository()->count());
        self::assertEquals($result1['perform_status']->id, $result2['perform_status']->id);
    }

    public function test_resolve_using_graphql_stack(): void {
        self::setUser($this->data->manager_user);
        $scale_value = $this->data->scale->values->first();
        $now = time();
        $args = [
            'goal_assignment_id' => $this->data->goal1->id,
            'goal_type' => 'PERSONAL',
            'participant_instance_id' => $this->data->manager_participant_instance1->id,
            'section_element_id' => $this->data->section_element->id,
            'scale_value_id' => $scale_value->id,
        ];
        $result = $this->execute_graphql_operation(self::MUTATION, ['input' => $args]);

        self::assertEmpty($result->errors);

        $created_at = perform_status::repository()->one(true)->created_at;
        $actual = $result->data['hierarchy_goal_perform_linked_goals_change_status'];
        self::assertEqualsCanonicalizing([
            'perform_status' => [
                'status_changer_user' => [
                    'fullname' => 'Manager User',
                ],
                'scale_value' => [
                    'name' => 'Created',
                    'proficient' => false,
                ],
                'created_at' => (new date_field_formatter(date_format::FORMAT_DATE, context_system::instance()))
                    ->format($created_at)
            ],
            'already_exists' => null,
        ], $actual);
    }

    public function test_user_not_logged_in(): void {
        self::setUser(null);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('You are not logged in');

        $this->resolve_mutation();
    }

    public function test_goals_feature_disabled(): void {
        self::setUser($this->data->manager_user);
        advanced_feature::disable('goals');

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Feature goals is not available.');

        $this->resolve_mutation();
    }

    public function test_performance_activities_feature_disabled(): void {
        self::setUser($this->data->manager_user);
        advanced_feature::disable('performance_activities');

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Feature performance_activities is not available.');

        $this->resolve_mutation();
    }

    public function test_invalid_goal_type(): void {
        self::setUser($this->data->manager_user);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid goal type non-existent');

        $this->resolve_mutation(['goal_type' => 'non-existent']);
    }
}
