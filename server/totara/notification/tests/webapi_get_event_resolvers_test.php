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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use totara_core\extended_context;
use core_phpunit\testcase;
use totara_notification\entity\notifiable_event_preference as entity;
use totara_notification\factory\notifiable_event_resolver_factory;
use totara_notification\model\notifiable_event_preference;
use totara_notification\resolver\resolver_helper;
use totara_notification\testing\generator as totara_notification_generator;
use totara_notification\webapi\resolver\query\event_resolvers;
use totara_notification_mock_notifiable_event_resolver as mock_event_resolver;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Note that this test is about testing the persist query rather than
 * the query resolver/handler. We are doing this because the resolver
 * only giving us the list of event class name.
 * Once we are upgrading the resolver to actually do DB look ups then it would be
 * the right time to have a test for the resolver.
 */
class totara_notification_webapi_get_event_resolvers_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_event_resolvers_at_system_context(): void {
        $generator = totara_notification_generator::instance();
        $generator->include_mock_recipient();
        $generator->include_mock_notifiable_event_resolver();
        $generator->add_notifiable_event_resolver(mock_event_resolver::class);

        $context_system = context_system::instance();

        mock_event_resolver::set_notification_available_recipients([
            totara_notification_mock_recipient::class,
        ]);

        // Create a custom notification for the mock notifiable event and check if it is included
        // when calling to the query.
        $custom_notification = $generator->create_notification_preference(
            mock_event_resolver::class,
            extended_context::make_with_context($context_system),
            [
                'title' => 'Custom title',
                'subject' => 'Custom subject',
                'subject_format' => FORMAT_MOODLE,
                'body' => 'Custom body',
                'body_format' => FORMAT_MOODLE,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $this->setAdminUser();
        $result = $this->execute_graphql_operation(
            'totara_notification_event_resolvers_v2',
            ['extended_context' => ['context_id' => $context_system->id],],
        );

        $this->resetDebugging();

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('resolvers', $result->data);

        $resolvers = $result->data['resolvers'];
        self::assertIsArray($resolvers);

        // There are mock custom event, along side with all of the other events
        // from the system.
        self::assertGreaterThan(1, count($resolvers));
        $mock_resolvers = array_filter(
            $resolvers,
            function (array $resolver): bool {
                return $resolver['class_name'] === mock_event_resolver::class;
            }
        );

        self::assertCount(1, $mock_resolvers);
        $mock_resolver = reset($mock_resolvers);

        self::assertArrayHasKey('name', $mock_resolver);
        self::assertEquals(
            resolver_helper::get_human_readable_resolver_name(mock_event_resolver::class),
            $mock_resolver['name']
        );

        self::assertArrayHasKey('notification_preferences', $mock_resolver);
        self::assertIsArray($mock_resolver['notification_preferences']);

        // There should have only custom notification for this event at the system context.
        self::assertCount(1, $mock_resolver['notification_preferences']);
        $preference = reset($mock_resolver['notification_preferences']);

        self::assertIsArray($preference);
        self::assertArrayHasKey('id', $preference);
        self::assertEquals($custom_notification->get_id(), $preference['id']);

        self::assertArrayHasKey('status', $mock_resolver);
        $status = $mock_resolver['status'];
        self::assertTrue($status['is_enabled']);
    }

    /**
     * @return void
     */
    public function test_get_notifiable_events_at_lower_context(): void {
        $notification_generator = totara_notification_generator::instance();
        $notification_generator->include_mock_notifiable_event_resolver();
        $notification_generator->include_mock_recipient();
        $notification_generator->add_notifiable_event_resolver(mock_event_resolver::class);

        $context_system = context_system::instance();

        mock_event_resolver::set_notification_available_recipients([
            totara_notification_mock_recipient::class,
        ]);

        // Create a custom notification for the mock notifiable event and check if it is included
        // when calling to the query.
        $custom_notification = $notification_generator->create_notification_preference(
            mock_event_resolver::class,
            extended_context::make_with_context($context_system),
            [
                'title' => 'Custom title',
                'subject' => 'Custom subject',
                'subject_format' => FORMAT_MOODLE,
                'body' => 'Custom body',
                'body_format' => FORMAT_MOODLE,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        // Create a course and fetch the notifiable_events at this course context.
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);

        $this->setAdminUser();
        mock_event_resolver::set_support_contexts(
            extended_context::make_with_context($context_course)
        );

        $result = $this->execute_graphql_operation(
            'totara_notification_event_resolvers_v2',
            ['extended_context' => ['context_id' => $context_course->id],],
        );

        $this->resetDebugging();

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('resolvers', $result->data);

        $resolvers = $result->data['resolvers'];
        self::assertIsArray($resolvers);

        // There are mock custom event, along side with all of the other events
        // from the system.
        self::assertGreaterThanOrEqual(1, count($resolvers));
        $mock_resolvers = array_filter(
            $resolvers,
            function (array $resolver): bool {
                return $resolver['class_name'] === mock_event_resolver::class;
            }
        );

        self::assertCount(1, $mock_resolvers);
        $mock_resolver = reset($mock_resolvers);

        self::assertArrayHasKey('name', $mock_resolver);
        self::assertEquals(
            resolver_helper::get_human_readable_resolver_name(mock_event_resolver::class),
            $mock_resolver['name']
        );

        self::assertArrayHasKey('notification_preferences', $mock_resolver);
        self::assertIsArray($mock_resolver['notification_preferences']);

        // There should have only custom notification for this event at the system context.
        self::assertCount(1, $mock_resolver['notification_preferences']);
        $preference = reset($mock_resolver['notification_preferences']);

        self::assertIsArray($preference);
        self::assertArrayHasKey('id', $preference);
        self::assertEquals($custom_notification->get_id(), $preference['id']);

        self::assertArrayHasKey('status', $mock_resolver);
        $status = $mock_resolver['status'];
        self::assertTrue($status['is_enabled']);
    }

    /**
     * @return void
     */
    public function test_get_notifiable_event_at_system_context_without_custom_at_lower_context(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $notification_generator = totara_notification_generator::instance();
        $notification_generator->include_mock_recipient();
        $notification_generator->include_mock_notifiable_event_resolver();
        $notification_generator->add_notifiable_event_resolver(mock_event_resolver::class);

        mock_event_resolver::set_notification_available_recipients([
            totara_notification_mock_recipient::class,
        ]);

        // Create a custom notification for the mock notifiable event and check if it is included
        // when calling to the query.
        $custom_notification = $notification_generator->create_notification_preference(
            mock_event_resolver::class,
            extended_context::make_with_context(context_course::instance($course->id)),
            [
                'title' => 'Custom title',
                'subject' => 'Custom subject',
                'subject_format' => FORMAT_MOODLE,
                'body' => 'Custom body',
                'body_format' => FORMAT_MOODLE,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $this->setAdminUser();
        $result = $this->execute_graphql_operation(
            'totara_notification_event_resolvers_v2',
            ['extended_context' => ['context_id' => context_system::instance()->id],],
        );

        $this->resetDebugging();

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('resolvers', $result->data);

        $resolvers = $result->data['resolvers'];
        self::assertIsArray($resolvers);

        // There are mock custom event, along side with all of the other events
        // from the system.
        self::assertGreaterThan(1, count($resolvers));
        $mock_resolvers = array_filter(
            $resolvers,
            function (array $notifiable_event): bool {
                return $notifiable_event['class_name'] === mock_event_resolver::class;
            }
        );

        self::assertCount(1, $mock_resolvers);
        $mock_resolver = reset($mock_resolvers);

        self::assertArrayHasKey('name', $mock_resolver);
        self::assertEquals(
            resolver_helper::get_human_readable_resolver_name(mock_event_resolver::class),
            $mock_resolver['name']
        );

        self::assertArrayHasKey('notification_preferences', $mock_resolver);
        self::assertIsArray($mock_resolver['notification_preferences']);
        self::assertEmpty($mock_resolver['notification_preferences']);

        foreach ($resolvers as $resolver) {
            self::assertArrayHasKey('notification_preferences', $resolver);
            $preferences = $resolver['notification_preferences'];

            self::assertIsArray($preferences);
            foreach ($preferences as $preference) {
                self::assertArrayHasKey('id', $preference);
                self::assertNotEquals($custom_notification->get_id(), $preference['id']);
            }
        }

        self::assertArrayHasKey('status', $mock_resolver);
        $status = $mock_resolver['status'];
        self::assertTrue($status['is_enabled']);
    }

    /**
     * @return void
     */
    public function test_get_notifiable_event_at_course_context_when_disabled_in_system_context(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $notification_generator = totara_notification_generator::instance();
        $notification_generator->include_mock_recipient();
        $notification_generator->include_mock_notifiable_event_resolver();
        $notification_generator->add_notifiable_event_resolver(mock_event_resolver::class);

        mock_event_resolver::set_notification_available_recipients([
            totara_notification_mock_recipient::class,
        ]);

        $course_context = context_course::instance($course->id);

        $this->setAdminUser();
        mock_event_resolver::set_support_contexts(
            extended_context::make_system(),
            extended_context::make_with_context($course_context)
        );

        $result = $this->execute_graphql_operation(
            'totara_notification_event_resolvers_v2',
            ['extended_context' => ['context_id' => $course_context->id],],
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('resolvers', $result->data);

        $resolvers = $result->data['resolvers'];
        self::assertIsArray($resolvers);

        // There are mock custom event, along side with all of the other events
        // from the system.
        self::assertGreaterThanOrEqual(1, count($resolvers));
        $mock_resolvers = array_filter(
            $resolvers,
            function (array $resolver): bool {
                return $resolver['class_name'] === mock_event_resolver::class;
            }
        );

        self::assertCount(1, $mock_resolvers);
        $mock_resolver = reset($mock_resolvers);

        // Check the status of event before making any customisation
        self::assertArrayHasKey('status', $mock_resolver);
        $status = $mock_resolver['status'];
        self::assertTrue($status['is_enabled']);

        // Set notifiable event as disabled in system context
        $system_context = \context_system::instance();

        $extended_context = extended_context::make_with_context($system_context);

        $notifiable_event_entity = entity::repository()->for_context(mock_event_resolver::class, $extended_context);
        if (!$notifiable_event_entity) {
            $notifiable_event = notifiable_event_preference::create(mock_event_resolver::class, $extended_context);
        } else {
            $notifiable_event = notifiable_event_preference::from_entity($notifiable_event_entity);
        }

        $notifiable_event->set_enabled(false);
        $notifiable_event->save();

        // Check that event is now disabled in system context
        $result = $this->execute_graphql_operation(
            'totara_notification_event_resolvers_v2',
            ['extended_context' => ['context_id' => $system_context->id],],
        );

        $this->resetDebugging();

        self::assertArrayHasKey('resolvers', $result->data);
        $resolvers = $result->data['resolvers'];
        self::assertIsArray($resolvers);

        // Get the mock custom event
        $mock_resolvers = array_filter(
            $resolvers,
            function (array $resolver): bool {
                return $resolver['class_name'] === mock_event_resolver::class;
            }
        );

        self::assertCount(1, $mock_resolvers);
        $mock_resolver = reset($mock_resolvers);

        self::assertArrayHasKey('status', $mock_resolver);
        $status = $mock_resolver['status'];
        self::assertFalse($status['is_enabled']);

        // Now check that it is not shown in the course context.
        $result = $this->execute_graphql_operation(
            'totara_notification_event_resolvers_v2',
            ['extended_context' => ['context_id' => $course_context->id],],
        );

        $this->resetDebugging();

        self::assertArrayHasKey('resolvers', $result->data);
        $resolvers = $result->data['resolvers'];
        self::assertIsArray($resolvers);

        // Get the mock custom event
        $mock_resolvers = array_filter(
            $resolvers,
            function (array $resolver): bool {
                return $resolver['class_name'] === mock_event_resolver::class;
            }
        );

        // Resolver list should be empty before the filtering, but we'll check after filtering to make test more robust.
        self::assertEmpty($mock_resolvers);
    }

    /**
     * @return void
     */
    public function test_get_event_resolvers_at_system_contexts_where_one_resolver_does_not_support_user_permission(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $notification_generator = totara_notification_generator::instance();
        $notification_generator->add_notifiable_event_resolver(mock_event_resolver::class,);
        $notification_generator->include_mock_notifiable_event_resolver();

        $context_system = extended_context::make_system();
        mock_event_resolver::set_permissions($context_system, $user_one->id, true);

        // There will be mock_event_resolver returned in the factory, among the others.
        $event_resolvers = notifiable_event_resolver_factory::get_resolver_classes();
        self::assertGreaterThanOrEqual(1, count($event_resolvers));

        // Making sure that this user does not have the general capabilities.
        self::assertFalse(
            has_capability(
                'totara/notification:managenotifications',
                $context_system->get_context(),
                $user_one->id
            )
        );

        // Add a custom capability to the user to make sure that user is able to fetch the event resolvers,
        // but not all of it.
        $role_id = $DB->get_field('role', 'id', ['shortname' => 'user']);
        assign_capability(
            'moodle/site:config',
            CAP_ALLOW,
            $role_id,
            $context_system->get_context_id()
        );

        self::assertTrue(
            has_capability(
                'moodle/site:config',
                $context_system->get_context(),
                $user_one->id
            )
        );

        $notification_generator->add_extra_capability('moodle/site:config', [CONTEXT_SYSTEM]);
        $this->setUser($user_one);

        $fetched_event_resolvers = $this->resolve_graphql_query(
            $this->get_graphql_name(event_resolvers::class),
            [
                'extended_context' => [
                    'context_id' => $context_system->get_context_id(),
                ]
            ]
        );

        $this->resetDebugging();

        // There shoulld be only one notifiable event resolver that we added from the above.
        self::assertCount(1, $fetched_event_resolvers);
        $first_event_resolver = reset($fetched_event_resolvers);

        self::assertEquals(mock_event_resolver::class, $first_event_resolver);
    }

    /**
     * Test that the warnings property is being returned.
     *
     * @return void
     */
    public function test_warnings(): void {
        $notification_generator = totara_notification_generator::instance();
        $notification_generator->include_mock_recipient();
        $notification_generator->add_notifiable_event_resolver(mock_event_resolver::class,);
        $notification_generator->include_mock_notifiable_event_resolver();

        $test_warnings = ['test warning 1', 'test warning 2'];
        mock_event_resolver::set_warnings($test_warnings);

        mock_event_resolver::set_notification_available_recipients([
            totara_notification_mock_recipient::class,
        ]);

        self::setAdminUser();

        $result = $this->execute_graphql_operation(
            'totara_notification_event_resolvers_v2',
            [
                'extended_context' => [
                    'context_id' => context_system::instance()->id,
                ],
            ],
        );

        $this->resetDebugging();

        self::assertEmpty($result->errors);

        $found = false;
        foreach ($result->data['resolvers'] as $event_resolver) {
            if ($event_resolver['class_name'] == mock_event_resolver::class) {
                self::assertEquals($test_warnings, $event_resolver['warnings']);
                $found = true;
            } else {
                // We're assuming that all other resolvers do not produce any warnings. If there is a resolver
                // introduced in future which has warnings by default in system context then update this test.
                self::assertEmpty($event_resolver['warnings']);
            }
        }
        self::assertTrue($found);
    }

    /**
     * @dataProvider test_get_event_resolvers_can_manage_can_audit_provider
     * @return void
     */
    public function test_get_event_resolvers_can_manage_can_audit(bool $notificationlogs_enabled, array $assigned_capabilities, string $query_context, array $expected): void {
        global $DB, $CFG;

        set_config('notificationlogs', (int)$notificationlogs_enabled);

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();
        $course = $generator->create_course();

        $notification_generator = totara_notification_generator::instance();
        $notification_generator->include_mock_notifiable_event_resolver();
        $notification_generator->include_mock_recipient();
        $notification_generator->add_notifiable_event_resolver(mock_event_resolver::class,);

        $context_system = extended_context::make_system();
        mock_event_resolver::set_notification_available_recipients([
            totara_notification_mock_recipient::class,
        ]);

        mock_event_resolver::set_permissions($context_system, $user_one->id, true);

        $context_system = context_system::instance();
        $context_course = context_course::instance($course->id);

        $this->setUser($user_one);

        $role_id = $DB->get_field('role', 'id', ['shortname' => 'user']);
        foreach ($assigned_capabilities as $capability) {
            $context = $capability['context'] === 'course' ? $context_course : $context_system;
            assign_capability(
                $capability['name'],
                CAP_ALLOW,
                $role_id,
                $context->id
            );
        }

        $context = $query_context === 'course' ? $context_course : $context_system;

        $result = $this->execute_graphql_operation(
            'totara_notification_event_resolvers_v2',
            ['extended_context' => ['context_id' => $context->id]]
        );

        // called multiple times - deprecation warnings - covered by new endpoint tests
        $this->resetDebugging();

        if (empty($expected['errors'])) {
            self::assertEmpty($result->errors);
        } else {
            self::assertNotEmpty($result->errors);

            $actual_errors = [];
            foreach ($result->errors as $error) {
                $actual_errors[] = $error->getMessage();
            }
            self::assertEqualsCanonicalizing($expected['errors'], $actual_errors);
        }

        if (empty($expected['resolvers'])) {
            $resolvers = $result->data ? $result->data['resolvers'] : [];
            self::assertEmpty($resolvers);
        } else {
            self::assertNotEmpty($result->data);
            self::assertArrayHasKey('resolvers', $result->data);
            $actual_resolvers = $result->data['resolvers'];

            // Only testing that the number of resolvers returned with the can_manage / can_audit combination is as expected
            $actual = [];
            foreach ($actual_resolvers as $resolver) {
                $key = (int)$resolver['interactor']['can_manage'] . '/' . (int)$resolver['interactor']['can_audit'];
                if (isset($actual[$key])) {
                    $actual[$key] += 1;
                } else {
                    $actual[$key] = 1;
                }
            }

            self::assertSame(count($expected['resolvers']), count($actual));
            foreach ($expected['resolvers'] as $exp_resolver) {
                $key = (int)$exp_resolver['can_manage'] . '/' . (int)$exp_resolver['can_audit'];
                self::assertSame($exp_resolver['count'], $actual[$key]);
            }
        }
    }

    /**
     * Provider for test_get_event_resolvers_can_manage_can_audit
     * @return array
     */
    public function test_get_event_resolvers_can_manage_can_audit_provider() {
        $resolvers_all = notifiable_event_resolver_factory::get_resolver_classes();

        // Get all the resolvers available at course/container level.
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);
        $resolvers_crs = notifiable_event_resolver_factory::get_context_available_resolvers($context_course->id);

        // Parse these down to expected counts.
        $expected_all = count($resolvers_all) + 1; // +1 for mock resolver.
        $expected_crs = count($resolvers_crs) + 1; // +1 for mock resolver.
        $expected_rst = $expected_all - $expected_crs;

        return [
            [
                'notificationlogs_enabled' => true,
                'assigned_capabilities' => [],
                'query_context' => 'system',
                'expected' => [
                    'errors' => [
                        'You are not allowed to manage notification preference',
                    ],
                    'resolvers' => [],
                ],
            ],
            [
                'notificationlogs_enabled' => true,
                'assigned_capabilities' => [],
                'query_context' => 'course',
                'expected' => [
                    'errors' => [
                        'You are not allowed to manage notification preference',
                    ],
                    'resolvers' => [],
                ],
            ],
            [
                'notificationlogs_enabled' => true,
                'assigned_capabilities' => [
                    [
                        'name' => 'moodle/course:auditcoursenotifications',
                        'context' => 'course',
                    ],
                ],
                'query_context' => 'system',
                'expected' => [
                    'errors' => [
                        'You are not allowed to manage notification preference',
                    ],
                    'resolvers' => [],
                ],
            ],
            [
                'notificationlogs_enabled' => true,
                'assigned_capabilities' => [
                    [
                        'name' => 'moodle/course:auditcoursenotifications',
                        'context' => 'course',
                    ],
                ],
                'query_context' => 'course',
                'expected' => [
                    'errors' => [],
                    'resolvers' => [
                        [
                            'can_manage' => false,
                            'can_audit' => true,
                            'count' => count($resolvers_crs), // Don't count the mock system context one.
                        ],
                    ],
                ],
            ],
            [
                'notificationlogs_enabled' => false,
                'assigned_capabilities' => [
                    [
                        'name' => 'moodle/course:auditcoursenotifications',
                        'context' => 'course',
                    ],
                ],
                'query_context' => 'course',
                'expected' => [
                    'errors' => [],
                    'resolvers' => [],
                ],
            ],
            [
                'notificationlogs_enabled' => true,
                'assigned_capabilities' => [
                    [
                        'name' => 'moodle/course:auditcoursenotifications',
                        'context' => 'system',
                    ],
                    [
                        'name' => 'totara/notification:managenotifications',
                        'context' => 'system',
                    ],
                ],
                'query_context' => 'system',
                'expected' => [
                    'errors' => [],
                    'resolvers' => [
                        [
                            'can_manage' => true,
                            'can_audit' => false,
                            'count' => $expected_rst,
                        ],
                        [
                            'can_manage' => true,
                            'can_audit' => true,
                            'count' => $expected_crs,
                        ],
                    ],
                ],
            ],
            [
                'notificationlogs_enabled' => false,
                'assigned_capabilities' => [
                    [
                        'name' => 'moodle/course:auditcoursenotifications',
                        'context' => 'system',
                    ],
                    [
                        'name' => 'totara/notification:managenotifications',
                        'context' => 'system',
                    ],
                ],
                'query_context' => 'system',
                'expected' => [
                    'errors' => [],
                    'resolvers' => [
                        [
                            'can_manage' => true,
                            'can_audit' => false,
                            'count' => $expected_all,
                        ],
                    ],
                ],
            ],
        ];
    }
}
