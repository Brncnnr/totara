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

use core_phpunit\testcase;
use totara_certification\totara_notification\resolver\window_open_date;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\resolver_helper;
use totara_notification\testing\generator;
use totara_notification_invalid_notifiable_event_resolver as invalid_resolver;
use totara_program\totara_notification\resolver\course_set_completed;

class totara_notification_resolver_helper_test extends testcase {
    /**
     * @return void
     */
    public function test_get_resolver_from_invalid_event(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Event class name is an invalid notifiable event');

        resolver_helper::get_resolver_from_notifiable_event('hello_world', []);
    }

    /**
     * @return void
     */
    public function test_phpunit_get_resolver_from_valid_event(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event();

        $resolver = resolver_helper::get_resolver_from_notifiable_event(
            totara_notification_mock_notifiable_event::class,
            ['user_data' => false]
        );

        self::assertInstanceOf(notifiable_event_resolver::class, $resolver);
    }

    /**
     * @return void
     */
    public function test_get_invalid_component_name_from_resolver_name(): void {
        $generator = generator::instance();
        $generator->include_invalid_notifiable_event_resolver();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot find the component from the resolver class name");

        resolver_helper::get_component_of_resolver_class_name(invalid_resolver::class);
    }

    /**
     * @return void
     */
    public function test_get_component_name_from_invalid_resolver_name(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The resolver class name is not a valid notifiable event resolver");

        resolver_helper::get_component_of_resolver_class_name('anima_martin_garrix');
    }

    /**
     * @return void
     */
    public function test_get_component_from_mock_with_trailing(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        self::assertEquals(
            'totara_notification',
            resolver_helper::get_component_of_resolver_class_name('\\totara_notification_mock_notifiable_event_resolver')
        );
    }

    /**
     * @return void
     */
    public function test_is_valid_permission_resolver(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        self::assertFalse(resolver_helper::is_valid_permission_resolver('boom'));
        self::assertFalse(resolver_helper::is_valid_permission_resolver(static::class));
        self::assertTrue(resolver_helper::is_valid_permission_resolver(totara_notification_mock_notifiable_event_resolver::class));
    }

    /**
     * @return void
     */
    public function test_is_valid_audit_resolver(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        self::assertFalse(resolver_helper::is_valid_audit_resolver('boom'));
        self::assertFalse(resolver_helper::is_valid_audit_resolver(static::class));
        self::assertTrue(resolver_helper::is_valid_audit_resolver(totara_notification_mock_notifiable_event_resolver::class));
    }

    public function test_get_human_readable_plugin_name(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        $class = totara_notification_mock_notifiable_event_resolver::class;

        totara_notification_mock_notifiable_event_resolver::set_test_plugin_name('custom test name');
        self::assertEquals(
            'custom test name',
            resolver_helper::get_human_readable_plugin_name($class)
        );

        totara_notification_mock_notifiable_event_resolver::set_test_plugin_name(null);
        self::assertEquals(
            get_string('pluginname', resolver_helper::get_component_of_resolver_class_name($class)),
            resolver_helper::get_human_readable_plugin_name($class)
        );
    }

    public function test_get_human_readable_plugin_name_lang_string_convention(): void {
        // Check for a resolver class that has a pluginname_totara_notification lang string.
        self::assertEquals('Program Management', get_string('pluginname', 'totara_program'));
        self::assertEquals('Program', get_string('pluginname_totara_notification', 'totara_program'));
        self::assertEquals('Program', resolver_helper::get_human_readable_plugin_name(course_set_completed::class));

        // Check for a resolver class that doesn't have a pluginname_totara_notification lang string.
        self::assertFalse(get_string_manager()->string_exists('pluginname_totara_notification', 'totara_certification'));
        self::assertEquals('Certification', get_string('pluginname', 'totara_certification'));
        self::assertEquals('Certification', resolver_helper::get_human_readable_plugin_name(window_open_date::class));
    }

}