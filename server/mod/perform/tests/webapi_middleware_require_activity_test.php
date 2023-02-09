<?php
/**
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use mod_perform\activity_access_denied_exception;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\notification as notification_model;
use mod_perform\webapi\middleware\require_activity;

/**
 * @coversDefaultClass \mod_perform\webapi\middleware\require_activity
 *
 * @group perform
 */
class mod_perform_webapi_middleware_require_activity_testcase extends advanced_testcase {
    /**
     * @covers ::by_activity_id
     * @covers ::handle
     */
    public function test_require_by_activity_id(): void {
        $expected = 34324;
        [$activity, $context, $next] = $this->create_test_data($expected);

        // Test with single key.
        $id_key = 'abc';
        $single_key_args = [$id_key => $activity->id];
        $single_key_payload = payload::create($single_key_args, $context);

        $result = require_activity::by_activity_id($id_key, false)
            ->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');

        // Test with composite key.
        $root_key = 'xyz';
        $composite_key_args = [$root_key => $single_key_args];
        $composite_key_payload = payload::create($composite_key_args, $context);

        $result = require_activity::by_activity_id("$root_key.$id_key", true)
            ->handle($composite_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertTrue($context->has_relevant_context(), 'relevant context not set');
        $this->assertEquals(
            $activity->get_context()->id,
            $context->get_relevant_context()->id,
            'wrong context id'
        );

        // Test with wrong key.
        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('activity id');
        require_activity::by_activity_id($id_key, true)
            ->handle($composite_key_payload, $next);
    }

    /**
     * @covers ::by_activity_id
     * @covers ::handle
     */
    public function test_require_on_hidden_activity(): void {
        $user = $this->getDataGenerator()->create_user();

        $expected = 34324;
        [$activity, $context, $next] = $this->create_test_data($expected, $user);

        builder::table('course')
            ->where('id', $activity->course)
            ->update([
                'visible' => 0,
                'visibleold' => 0
            ]);

        $single_key_args = ['activity_id' => $activity->id];
        $single_key_payload = payload::create($single_key_args, $context);

        $this->expectException(activity_access_denied_exception::class);
        $this->expectExceptionMessage('Cannot access this activity (Activity is hidden)');

        require_activity::by_activity_id('activity_id', false)
            ->handle($single_key_payload, $next);
    }

    /**
     * @covers ::by_activity_id
     * @covers ::handle
     */
    public function test_require_on_wrong_tenant(): void {
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();

        $tenant1 = $tenantgenerator->create_tenant();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenant2 = $tenantgenerator->create_tenant();
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $expected = 34324;
        [$activity, $context, $next] = $this->create_test_data($expected, $user1);

        $this->setUser($user2);

        builder::table('course')
            ->where('id', $activity->course)
            ->update([
                'visible' => 0,
                'visibleold' => 0
            ]);

        $single_key_args = ['activity_id' => $activity->id];
        $single_key_payload = payload::create($single_key_args, $context);

        $this->expectException(activity_access_denied_exception::class);
        $this->expectExceptionMessage('Cannot access this activity (Cannot access activity)');

        require_activity::by_activity_id('activity_id', false)
            ->handle($single_key_payload, $next);
    }

    /**
     * @covers ::by_track_id
     * @covers ::handle
     */
    public function test_require_by_track_id(): void {
        $expected = 34324;
        [$activity, $context, $next] = $this->create_test_data($expected);
        $track = $activity->tracks->first();

        $id_key = 'abc';
        $single_key_args = [$id_key => $track->id];
        $single_key_payload = payload::create($single_key_args, $context);

        $result = require_activity::by_track_id($id_key, false)
            ->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');

        // Test with composite key.
        $root_key = 'xyz';
        $composite_key_args = [$root_key => $single_key_args];
        $composite_key_payload = payload::create($composite_key_args, $context);

        $result = require_activity::by_track_id("$root_key.$id_key", true)
            ->handle($composite_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertTrue($context->has_relevant_context(), 'relevant context not set');
        $this->assertEquals(
            $activity->get_context()->id,
            $context->get_relevant_context()->id,
            'wrong context id'
        );

        // Test with wrong key.
        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('track id');
        require_activity::by_track_id($id_key, true)
            ->handle($composite_key_payload, $next);
    }

    /**
     * @covers ::by_section_id
     * @covers ::handle
     */
    public function test_require_by_section_id(): void {
        $expected = 34324;
        [$activity, $context, $next] = $this->create_test_data($expected);
        $section = $activity->sections->first();

        $id_key = 'abc';
        $single_key_args = [$id_key => $section->id];
        $single_key_payload = payload::create($single_key_args, $context);

        $result = require_activity::by_section_id($id_key, false)
            ->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');

        // Test with composite key.
        $root_key = 'xyz';
        $composite_key_args = [$root_key => $single_key_args];
        $composite_key_payload = payload::create($composite_key_args, $context);

        $result = require_activity::by_section_id("$root_key.$id_key", true)
            ->handle($composite_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertTrue($context->has_relevant_context(), 'relevant context not set');
        $this->assertEquals(
            $activity->get_context()->id,
            $context->get_relevant_context()->id,
            'wrong context id'
        );

        // Test with wrong key.
        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('section id');
        require_activity::by_section_id($id_key, true)
            ->handle($composite_key_payload, $next);
    }

    /**
     * @covers ::by_section_element_id
     * @covers ::handle
     */
    public function test_require_by_section_element_id() {
        $expected = 34324;
        [$activity, $context, $next] = $this->create_test_data($expected);
        $section = $activity->sections->first();

        $id_key = 'abc';
        $single_key_args = [$id_key => $section->section_elements->first()->id];
        $single_key_payload = payload::create($single_key_args, $context);

        $result = require_activity::by_section_element_id($id_key, false)
            ->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');

        // Test with composite key.
        $root_key = 'xyz';
        $composite_key_args = [$root_key => $single_key_args];
        $composite_key_payload = payload::create($composite_key_args, $context);

        $result = require_activity::by_section_element_id("$root_key.$id_key", true)
            ->handle($composite_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertTrue($context->has_relevant_context(), 'relevant context not set');
        $this->assertEquals(
            $activity->get_context()->id,
            $context->get_relevant_context()->id,
            'wrong context id'
        );

        // Test with wrong key.
        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('section element id');
        require_activity::by_section_element_id($id_key, true)
            ->handle($composite_key_payload, $next);
    }

    /**
     * @covers ::by_element_id
     * @covers ::handle
     */
    public function test_require_by_element_id() {
        $expected = 34324;
        [$activity, $context, $next] = $this->create_test_data($expected);
        $section = $activity->sections->first();

        $id_key = 'abc';
        $single_key_args = [$id_key => $section->section_elements->first()->element->id];
        $single_key_payload = payload::create($single_key_args, $context);

        $result = require_activity::by_element_id($id_key, false)
            ->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');

        // Test with composite key.
        $root_key = 'xyz';
        $composite_key_args = [$root_key => $single_key_args];
        $composite_key_payload = payload::create($composite_key_args, $context);

        $result = require_activity::by_element_id("$root_key.$id_key", true)
            ->handle($composite_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertTrue($context->has_relevant_context(), 'relevant context not set');
        $this->assertEquals(
            $activity->get_context()->id,
            $context->get_relevant_context()->id,
            'wrong context id'
        );

        // Test with wrong key.
        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('element id');
        require_activity::by_element_id($id_key, true)
            ->handle($composite_key_payload, $next);
    }

    /**
     * @covers ::by_notification_id
     * @covers ::handle
     */
    public function test_require_by_notification_id(): void {
        $expected = 34324;
        [$activity, $context, $next] = $this->create_test_data($expected);
        $notification = notification_model::load_by_activity_and_class_key($activity, 'instance_created');

        $id_key = 'abc';
        $single_key_args = [$id_key => $notification->id];
        $single_key_payload = payload::create($single_key_args, $context);

        $result = require_activity::by_notification_id($id_key, false)
            ->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');

        // Test with composite key.
        $root_key = 'xyz';
        $composite_key_args = [$root_key => $single_key_args];
        $composite_key_payload = payload::create($composite_key_args, $context);

        $result = require_activity::by_notification_id("$root_key.$id_key", true)
            ->handle($composite_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertTrue($context->has_relevant_context(), 'relevant context not set');
        $this->assertEquals(
            $activity->get_context()->id,
            $context->get_relevant_context()->id,
            'wrong context id'
        );

        // Test with wrong key.
        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('notification id');
        require_activity::by_notification_id($id_key, true)
            ->handle($composite_key_payload, $next);
    }

    /**
     * @covers ::by_subject_instance_ids
     * @covers ::handle
     */
    public function test_require_by_subject_instance_ids(): void {
        $this->setAdminUser();
        // Create 2 activities with 2 users each.
        $generator = \mod_perform\testing\generator::instance();
        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(2)
            ->set_number_of_users_per_user_group_type(2);

        [$activity1, $activity2] = $generator->create_full_activities($config)->all();

        $activity1_subject_instance_ids = subject_instance::repository()
            ->filter_by_activity_id($activity1->id)
            ->get()
            ->pluck('id');
        $activity2_subject_instance_ids = subject_instance::repository()
            ->filter_by_activity_id($activity2->id)
            ->get()
            ->pluck('id');

        $expected = 34324;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };
        $context = execution_context::create('dev');

        $id_key = 'abc';
        $single_key_args = [$id_key => $activity1_subject_instance_ids];
        $single_key_payload = payload::create($single_key_args, $context);

        $result = require_activity::by_subject_instance_ids($id_key, false)
            ->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data());
        $this->assertFalse($context->has_relevant_context());

        // Test with composite key.
        $root_key = 'xyz';
        $composite_key_args = [$root_key => $single_key_args];
        $composite_key_payload = payload::create($composite_key_args, $context);

        $result = require_activity::by_subject_instance_ids("$root_key.$id_key", true)
            ->handle($composite_key_payload, $next);

        $this->assertEquals($expected, $result->get_data());
        $this->assertTrue($context->has_relevant_context());
        $this->assertEquals($activity1->get_context()->id, $context->get_relevant_context()->id);

        // Test with mixed subject_instances.
        $id_key = 'abc';
        $single_key_args = [$id_key => array_merge($activity1_subject_instance_ids, $activity2_subject_instance_ids)];
        $single_key_payload = payload::create($single_key_args, $context);
        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('All subject instances must belong to the same activity');
        require_activity::by_subject_instance_ids($id_key, false)
            ->handle($single_key_payload, $next);
    }


    /**
     * Generates test data.
     *
     * @param mixed $expected_result value to return as the result of the next
     *        chained "processor" after the require_activity handler.
     *
     * @param stdClass|null $as_user
     * @return array (activity with one track and one section, graphql execution
     *         context, next handler to execute) tuple.
     * @throws coding_exception
     */
    private function create_test_data($expected_result = null, stdClass $as_user = null): array {
        if ($as_user) {
            $this->setUser($as_user);
        } else {
            $this->setAdminUser();
        }

        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container([
            'create_track' => true,
            'create_section' => true,
        ]);
        $element = $generator->create_element(
            ['context' => $activity->get_context()]
        );
        $generator->create_section_element($activity->sections->first(), $element);

        $next = function (payload $payload) use ($expected_result): result {
            return new result($expected_result);
        };

        $context = execution_context::create("dev");
        return [$activity, $context, $next];
    }
}
