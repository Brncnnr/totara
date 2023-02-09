<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use mod_perform\models\activity\activity;
use mod_perform\testing\generator;
use mod_perform\state\activity\draft;
use totara_core\advanced_feature;
use totara_core\relationship\relationship;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_set_manual_relationship_selector_roles_testcase extends testcase {
    private const MUTATION = 'mod_perform_set_manual_relationship_selector_roles';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_update_manual_relationships_selectors(): void {
        [$activity, $new_selections, $relationship] = $this->test_env();
        $this->assert_relationships(
            $activity, relationship::load_by_idnumber('subject')
        );

        $args = [
            'input' => [
                'activity_id' => $activity->id,
                'roles' => $new_selections
            ]
        ];

        ['success' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        self::assertTrue($result, 'Mutation failed');

        $this->assert_relationships(
            activity::load_by_id($activity->id), $relationship
        );
    }

    /**
     * @covers ::resolve
     */
    public function test_successful_ajax_call(): void {
        [$activity, $new_selections, $relationship] = $this->test_env();
        $this->assert_relationships(
            $activity, relationship::load_by_idnumber('subject')
        );

        $args = [
            'input' => [
                'activity_id' => $activity->id,
                'roles' => $new_selections
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertTrue($result['success'], 'Mutation failed');

        $this->assert_relationships(
            activity::load_by_id($activity->id), $relationship
        );
    }

    /**
     * @covers ::resolve
    */
    public function test_failed_ajax_query(): void {
        [$activity, $new_selections, ] = $this->test_env();

        $args = [
            'input' => [
                'activity_id' => $activity->id,
                'roles' => $new_selections
            ]
        ];
        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed($result, 'input');

        $this->setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }

    /**
     * Creates test data.
     *
     * @param string $relationship_name the name of the relationship to be used
     *        to update the generated activity.
     *
     * @return array an [activity, new selector relationships, new relationship]
     *         tuple.
     */
    private function test_env(string $relationship_name='manager'): array {
        self::setAdminUser();
        $activity = generator::instance()->create_activity_in_container([
            'activity_status' => draft::get_code()
        ]);

        $new_selections = [];
        $relationship = relationship::load_by_idnumber($relationship_name);
        foreach ($activity->manual_relationships as $existing) {
            $new_selections[] = [
                'manual_relationship_id' => $existing->manual_relationship_id,
                'selector_relationship_id' => $relationship->id,
            ];
        }

        return [$activity, $new_selections, $relationship];
    }

    /**
     * Checks whether the manual selections of the given activity match the
     * expected one.
     *
     * @param activity $activity activity to check.
     * @param relationship $relationship expected relationship.
     */
    private function assert_relationships(
        activity $activity,
        relationship $expected
    ): void {
        foreach ($activity->manual_relationships as $actual) {
            self::assertEquals($actual->selector_relationship_id, $expected->id);
        }
    }
}
