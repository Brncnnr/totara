<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

use container_perform\create_exception;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_type;
use totara_core\advanced_feature;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship_provider;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\create_activity
 *
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_create_activity_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_create_activity';

    use webapi_phpunit_helper;

    public function test_create_activity(): void {
        $this->setAdminUser();
        $expected_type = activity_type::load_by_name('check-in');
        $args = [
            'name' => "Mid year performance review",
            'description' => "Test Description",
            'type' => $expected_type->id
        ];

        /** @type activity $result */
        ['activity' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertSame('Mid year performance review', $result->name);
        $this->assertSame('Test Description', $result->description);

        $actual_type = $result->type;
        $this->assertEquals($expected_type->name, $actual_type->name, "wrong type");
        $this->assertEquals($expected_type->display_name, $actual_type->display_name, "wrong display name");
    }

    public function test_create_activity_for_non_admin_user(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $args = [
            'name' => 'Mid year performance review',
            'description' => 'Test Description',
            'type' => activity_type::load_by_name('appraisal')->id
        ];

        $this->expectException(create_exception::class);
        $this->expectExceptionMessage('You do not have the permission to create a performance activity');

        $this->resolve_graphql_mutation('mod_perform_create_activity', $args);
    }

    public function test_create_activity_with_empty_name(): void {
        $this->setAdminUser();
        $args = [
            'name' => '',
            'description' => 'Test Description',
            'type' => activity_type::load_by_name('feedback')->id
        ];

        $this->expectException(create_exception::class);
        $this->expectExceptionMessage('You are not allowed to create an activity with an empty name');

        $this->resolve_graphql_mutation('mod_perform_create_activity', $args);
    }

    public function test_create_activity_with_empty_description(): void {
        $this->setAdminUser();
        $type_id = activity_type::load_by_name('feedback')->id;
        $args = [
            'name' => 'Mid year performance review',
            'description' => "",
            'type' => $type_id
        ];

        ['activity' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertSame('Mid year performance review', $result->name);
        $this->assertSame('', $result->description);

        $args = [
            'name' => 'Mid year performance review',
            'description' => null,
            'type' => $type_id
        ];

        ['activity' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertSame('Mid year performance review', $result->name);
        $this->assertNull($result->description);
    }

    public function test_create_activity_with_empty_type(): void {
        $this->setAdminUser();
        $args = [
            'name' => 'Mid year performance review'
        ];

        $this->expectException(create_exception::class);
        $this->expectExceptionMessageMatches("/type/");

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_create_activity_with_invalid_type(): void {
        $this->setAdminUser();
        $args = [
            'name' => 'Mid year performance review',
            'type' => 12334
        ];

        $this->expectException(create_exception::class);
        $this->expectExceptionMessageMatches("/type id/");

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * Tests selector for manual relationships are set as subject by default.
     *
     * @return void
     */
    public function test_create_activity_creates_default_manual_relationships(): void {
        $this->setAdminUser();
        $expected_type = activity_type::load_by_name('check-in');
        $args = [
            'name' => "Mid year performance review",
            'description' => "Test Description",
            'type' => $expected_type->id
        ];
        ['activity' => $activity] = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $subject_relationship = relationship::load_by_idnumber('subject');
        $manual_relationships = (new relationship_provider())
            ->filter_by_component('mod_perform')
            ->filter_by_type(relationship_entity::TYPE_MANUAL)
            ->get();
        $this->assertSameSize($manual_relationships, $activity->manual_relationships);

        $expected_default_manual_relationships = [];
        foreach ($manual_relationships as $manual_relationship) {
            $expected_default_manual_relationships[] = [
                'manual_relationship_id' => $manual_relationship->id,
                'selector_relationship_id' => $subject_relationship->id,
            ];
        }

        $created_manual_relationships = [];
        foreach ($activity->manual_relationships as $manual_relationship) {
            $created_manual_relationships[] = [
                'manual_relationship_id' => (int)$manual_relationship->manual_relationship_id,
                'selector_relationship_id' => (int)$manual_relationship->selector_relationship_id,

            ];
        }
        $this->assertEqualsCanonicalizing($expected_default_manual_relationships, $created_manual_relationships);
    }

    /**
     * @covers ::resolve
     */
    public function test_successful_ajax_call(): void {
        $this->setAdminUser();

        $expected_type = activity_type::load_by_name('check-in');
        $args = [
            'name' => "Mid year performance review",
            'description' => "Test Description",
            'type' => $expected_type->id
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'activity creation failed');

        $activity = $result['activity'];
        $this->assertSame($args['name'], $activity['name']);
        $this->assertSame($args['description'], $activity['description']);

        $actual_type = $activity['type'];
        $this->assertEquals($expected_type->display_name, $actual_type['display_name'], "wrong type");
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_call(): void {
        $this->setAdminUser();

        $args = [
            'name' => "Mid year performance review",
            'description' => "Test Description",
            'type' => activity_type::load_by_name('check-in')->id
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'permission');
    }
}
