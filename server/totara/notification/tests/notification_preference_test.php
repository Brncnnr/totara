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
use totara_core\extended_context;
use totara_notification\entity\notification_preference as notification_preference_entity;
use totara_notification\testing\generator;
use totara_notification_mock_built_in_notification as mock_notification;
use totara_notification_mock_scheduled_aware_event_resolver as mock_resolver;
use totara_notification_mock_recipient as mock_recipient;

class totara_notification_notification_preference_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_recipient();
        $generator->include_mock_scheduled_aware_notifiable_event_resolver();
        $generator->include_mock_built_in_notification();
    }

    /**
     * @return void
     */
    public function test_check_is_before_event(): void {
        $generator = generator::instance();
        $extended_context = extended_context::make_system();

        $preference = $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'recipients' => [mock_recipient::class],
                'schedule_offset' => -1,
            ]
        );

        self::assertFalse($preference->is_on_event());
    }

    /**
     * @return void
     */
    public function test_check_is_on_event(): void {
        $generator = generator::instance();
        $extended_context = extended_context::make_system();

        $preference = $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'recipients' => [mock_recipient::class],
                'schedule_offset' => 0,
            ]
        );

        self::assertTrue($preference->is_on_event());
    }

    /**
     * @return void
     */
    public function test_check_is_after_event(): void {
        $generator = generator::instance();
        $extended_context = extended_context::make_system();

        $preference = $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'recipients' => [mock_recipient::class],
                'schedule_offset' => 3,
            ]
        );

        self::assertFalse($preference->is_on_event());
    }

    /**
     * @return void
     */
    public function test_notification_preference_title_and_subject_nullable(): void {
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

        self::assertEmpty($preference->get_title());
        self::assertEmpty($preference->get_subject());
        self::assertEmpty($preference->get_notification_class_name());
        self::resetDebugging();
    }

    /**
     * Assert that the body property properly obeys the following:
     * - If empty but not null, it will return the empty value
     * - If null, it will fall back to the parent's value (if set)
     * - Otherwise will fall back to the built-in notification
     */
    public function test_notification_preference_body_nullable(): void {
        $generator = generator::instance();

        // Our mock preference will live inside a program
        /** @var totara_program\testing\generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program = $gen->create_program([]);
        $context = extended_context::make_with_context($program->get_context());

        // Make a mock parent preference, we can use this to test if the overrides work
        $parent = $generator->create_notification_preference(
            mock_resolver::class,
            $context,
            [
                'recipient' => mock_recipient::class,
                'recipients' => [mock_recipient::class],
                'schedule_offset' => 3,
                'title' => 'Parent Title',
                'subject' => 'Parent Subject',
                'subject_format' => 2,
                'notification_class_name' => '',
                'body' => 'Parent Body',
                'body_format' => 2,
            ]
        );

        // Make our preference with an empty string
        $preference = $generator->create_notification_preference(
            mock_resolver::class,
            $context,
            [
                'recipient' => mock_recipient::class,
                'recipients' => [mock_recipient::class],
                'schedule_offset' => 3,
                'title' => 'My Title',
                'subject' => 'My Subject',
                'subject_format' => 2,
                'notification_class_name' => mock_notification::class,
                'body' => '',
                'body_format' => 2,
            ]
        );
        // Force the parent in
        $reflected_parent = new ReflectionProperty($preference, 'parent');
        $reflected_parent->setAccessible(true);
        $reflected_parent->setValue($preference, $parent);
        self::assertSame($parent, $preference->get_parent());

        $reflected_entity = new ReflectionProperty($preference, 'entity');
        $reflected_entity->setAccessible(true);
        /** @var notification_preference_entity $entity */
        $entity = $reflected_entity->getValue($preference);
        $entity->ancestor_id = $parent->get_id();

        // Now assert that calling get_body will return the empty string
        self::assertSame('', $preference->get_body());
        self::assertNotNull($preference->get_body());

        // Now check if the body has a value, we see it
        $entity->body = 'My Set Body';
        self::assertSame('My Set Body', $preference->get_body());

        // Now assert that if null, we see the parent body instead
        $entity->body = null;
        self::assertSame('Parent Body', $preference->get_body());

        // Now if there's no parent, we want to fall back to the built-in notification instead
        $entity->ancestor_id = null;
        $reflected_parent->setValue($preference, null);

        $lang_string = new lang_string('notification_body_label', 'totara_notification');
        mock_notification::set_default_body($lang_string);

        self::assertSame($lang_string->out(), $preference->get_body());

        self::resetDebugging();
    }
}