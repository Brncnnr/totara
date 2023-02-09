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
use totara_notification\model\notification_preference as model;
use totara_notification\testing\generator;
use totara_notification\webapi\resolver\type\notification_preference;
use totara_notification_mock_scheduled_aware_event_resolver as mock_resolver;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_phpunit\testcase;
use totara_notification_mock_recipient as mock_recipient;

class totara_notification_webapi_type_notification_preference_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var model|null
     */
    private $system_built_in;

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->system_built_in = null;
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event();
        $generator->include_mock_scheduled_aware_notifiable_event_resolver();

        $this->system_built_in = $generator->add_mock_built_in_notification_for_component();
    }

    /**
     * @return void
     */
    public function test_resolve_field_title(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(notification_preference::class),
            'title',
            $this->system_built_in
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);

        self::assertEquals(totara_notification_mock_built_in_notification::get_title(), $value);
    }

    /**
     * @return void
     */
    public function test_resolve_field_body(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(notification_preference::class),
            'body',
            $this->system_built_in
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);

        self::assertEquals(
            totara_notification_mock_built_in_notification::get_default_body()->out(),
            $value
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_subject(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(notification_preference::class),
            'subject',
            $this->system_built_in
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);

        self::assertEquals(
            totara_notification_mock_built_in_notification::get_default_subject()->out(),
            $value
        );
    }

    /**
     * @return void
     */
    public function test_resolve_body_format(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(notification_preference::class),
            'body_format',
            $this->system_built_in
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);

        self::assertEquals(
            totara_notification_mock_built_in_notification::get_default_body_format(),
            $value
        );
    }

    /**
     * @return void
     */
    public function test_resolve_extended_context(): void {
        self::assertEquals(
            $this->system_built_in->get_extended_context(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference::class),
                'extended_context',
                $this->system_built_in,
            )
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);
    }

    /**
     * @return void
     */
    public function test_resolve_is_custom(): void {
        self::assertFalse(
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference::class),
                'is_custom',
                $this->system_built_in,
            )
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);
    }

    /**
     * @return void
     */
    public function test_resolve_resolver_component_name(): void {
        self::assertEquals(
            'totara_notification',
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference::class),
                'resolver_component',
                $this->system_built_in
            )
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);
    }

    /**
     * @return void
     */
    public function test_resolve_resolver_name(): void {
        self::assertEquals(
            totara_notification_mock_notifiable_event_resolver::get_notification_title(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference::class),
                'resolver_name',
                $this->system_built_in
            )
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);
    }

    /**
     * @return void
     */
    public function test_resolve_resolver_class_name(): void {
        self::assertEquals(
            totara_notification_mock_notifiable_event_resolver::class,
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference::class),
                'resolver_class_name',
                $this->system_built_in
            )
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);
    }

    /**
     * @return void
     */
    public function test_resolve_parent(): void {
        self::assertNull(
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference::class),
                'parent_id',
                $this->system_built_in
            )
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);
    }

    /**
     * @return void
     */
    public function test_resolve_title_and_subject_nullable(): void {
        $generator = generator::instance();
        $preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'recipients' => [mock_recipient::class],
                'schedule_offset' => 3,
                'title' => '',
                'subject' => '',
                'subject_format' => 2,
                'notification_class_name' => ''
            ]
        );

        self::assertEquals(
            $preference->get_title(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference::class),
                'title',
                $preference
            )
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);

        self::assertEquals(
            $preference->get_subject(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(notification_preference::class),
                'subject',
                $preference
            )
        );
        $this->assertDebuggingCalled(['Do not use notification_preference::resolve any more, use notification_preference_v2::resolve instead']);

        self::resetDebugging();
    }
}