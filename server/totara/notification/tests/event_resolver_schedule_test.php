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
use totara_notification\local\event_resolver_schedule;
use totara_notification\local\schedule_helper;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_before_event;
use totara_notification\testing\generator;
use totara_notification_mock_recipient as mock_recipient;
use totara_notification_mock_scheduled_aware_event_resolver as mock_resolver;
use totara_notification_mock_scheduled_aware_event_resolver as mock_scheduled_resolver;
use totara_notification_mock_scheduled_built_in_notification as mock_built_in;

class totara_notification_event_resolver_schedule_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_scheduled_built_in_notification();
        $generator->include_mock_recipient();
        $generator->include_mock_scheduled_aware_notifiable_event_resolver();

        $generator->purge_built_in_notifications();
    }

    /**
     * @return void
     */
    public function test_get_maximum_schedule_offset_with_maximum_built_in_instead_of_custom_preference(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);

        mock_built_in::set_default_schedule_offset(
            schedule_after_event::default_value(10)
        );

        $extended_context = extended_context::make_system();
        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(5),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(6),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(7),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(7),
            ]
        );

        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(
            10 * DAYSECS,
            $schedule_resolver->get_maximum_offset(),
        );
    }

    /**
     * @return void
     */
    public function test_get_maximum_schedule_offset_with_maximum_custom_preference_instead_of_built_in(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);

        mock_built_in::set_default_schedule_offset(
            schedule_after_event::default_value(5)
        );

        $extended_context = extended_context::make_system();
        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(5),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(6),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(9),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(7),
            ]
        );

        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(
            9 * DAYSECS,
            $schedule_resolver->get_maximum_offset()
        );
    }

    /**
     * Return null if there is nothing that scheduled for before/after.
     * @return void
     */
    public function test_get_maximum_schedule_offset_should_not_return_zero(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);

        $extended_context = extended_context::make_system();
        mock_built_in::set_default_schedule_offset(0);

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => 0,
            ]
        );

        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(0, $schedule_resolver->get_maximum_offset());
    }

    /**
     * @return void
     */
    public function test_get_maximum_schedule_offset_when_there_is_only_before_event(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);

        $extended_context = extended_context::make_system();
        mock_built_in::set_default_schedule_offset(schedule_before_event::default_value(2));

        foreach ([5, 6, 9] as $day) {
            $generator->create_notification_preference(
                mock_resolver::class,
                $extended_context,
                [
                    'recipient' => mock_recipient::class,
                    'schedule_offset' => schedule_before_event::default_value($day),
                ]
            );
        }

        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);

        self::assertEquals(
            -2 * DAYSECS,
            $schedule_resolver->get_maximum_offset(),
        );
    }


    /**
     * Return null if there is nothing that scheduled for before/after.
     * @return void
     */
    public function test_get_minimum_schedule_offset_should_not_return_zero(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);

        $extended_context = extended_context::make_system();
        mock_built_in::set_default_schedule_offset(0);

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => 0,
            ]
        );

        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(0, $schedule_resolver->get_minimum_offset());
    }

    /**
     * @return void
     */
    public function test_get_minimum_schedule_offset_when_there_is_only_after_event(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);

        $extended_context = extended_context::make_system();
        mock_built_in::set_default_schedule_offset(schedule_after_event::default_value(2));

        foreach ([5, 6, 9] as $day) {
            $generator->create_notification_preference(
                mock_resolver::class,
                $extended_context,
                [
                    'recipient' => mock_recipient::class,
                    'schedule_offset' => schedule_after_event::default_value($day),
                ]
            );
        }

        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(
            2 * DAYSECS,
            $schedule_resolver->get_minimum_offset(),
        );
    }

    /**
     * @return void
     */
    public function test_get_minimumschedule_offset_with_minimum_built_in_instead_of_custom_preference(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);

        mock_built_in::set_default_schedule_offset(
            schedule_before_event::default_value(10)
        );

        $extended_context = extended_context::make_system();
        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(5),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(6),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(7),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(7),
            ]
        );

        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(
            -10 * DAYSECS,
            $schedule_resolver->get_minimum_offset(),
        );
    }

    /**
     * @return void
     */
    public function test_get_minimum_schedule_offset_with_minimum_custom_preference_instead_of_built_in(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);

        mock_built_in::set_default_schedule_offset(
            schedule_before_event::default_value(5)
        );

        $extended_context = extended_context::make_system();
        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(5),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(6),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(9),
            ]
        );

        $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(7),
            ]
        );

        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(
            -9 * DAYSECS,
            $schedule_resolver->get_minimum_offset()
        );
    }

    /**
     * @return void
     */
    public function test_get_maximum_schedule_offset_is_the_middle_number(): void {
        $generator = generator::instance();
        $extended_context = extended_context::make_system();

        // From here we can see that zero is the largest number. However, our function will return 2 days
        // as the maximum offset instead of zero
        foreach ([0, -2, -3] as $day) {
            $generator->create_notification_preference(
                mock_resolver::class,
                $extended_context,
                [
                    'recipient' => mock_recipient::class,
                    'schedule_offset' => schedule_helper::days_to_seconds($day),
                ]
            );
        }

        // Zero because the resolver does not have associated event.
        mock_resolver::set_associated_notifiable_event(false);
        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(0, $schedule_resolver->get_maximum_offset());

        // Change it to have the associated event, which we will receive number 2 in day seconds
        mock_resolver::set_associated_notifiable_event(true);
        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(-2 * DAYSECS, $schedule_resolver->get_maximum_offset());
    }

    /**
     * @return void
     */
    public function test_get_minimum_schedule_offset_is_the_middle_number(): void {
        $generator = generator::instance();
        $extended_context = extended_context::make_system();

        // From here we can see that zero is the smallest number. However, our function will return minus 1 days as
        // the minimum offset instead of zero
        foreach ([0, 4, 5] as $day) {
            $generator->create_notification_preference(
                mock_resolver::class,
                $extended_context,
                [
                    'recipient' => mock_recipient::class,
                    'schedule_offset' => schedule_helper::days_to_seconds($day),
                ]
            );
        }

        // Zero because the resolver does not have associated event.
        mock_resolver::set_associated_notifiable_event(false);
        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(0, $schedule_resolver->get_minimum_offset());

        // Change it to have associated event, which we will receive number 4 in days seconds.
        mock_resolver::set_associated_notifiable_event(true);
        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(4 * DAYSECS, $schedule_resolver->get_minimum_offset());
    }

    /**
     * @return void
     */
    public function test_get_maximum_schedule_offset_from_built_in(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);
        mock_built_in::set_default_schedule_offset(-1);

        $extended_context = extended_context::make_system();

        // Create 3 custom notifications that are having schedule offset as zero.
        for ($i = 0; $i < 3; $i++) {
            $generator->create_notification_preference(
                mock_resolver::class,
                $extended_context,
                [
                    'recipient' => mock_recipient::class,
                    'schedule_offset' => 0,
                ]
            );
        }

        // Zero because the resolver does not have associated event.
        mock_resolver::set_associated_notifiable_event(false);
        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(0, $schedule_resolver->get_maximum_offset());

        // Change it to have associated event, which we will receive number -1.
        mock_resolver::set_associated_notifiable_event(true);
        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(-1, $schedule_resolver->get_maximum_offset());
    }


    /**
     * @return void
     */
    public function test_get_minimum_schedule_offset_from_built_in(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);
        mock_built_in::set_default_schedule_offset(1);

        $extended_context = extended_context::make_system();

        // Create 3 custom notifications that are having schedule offset as zero.
        for ($i = 0; $i < 3; $i++) {
            $generator->create_notification_preference(
                mock_resolver::class,
                $extended_context,
                [
                    'recipient' => mock_recipient::class,
                    'schedule_offset' => 0,
                ]
            );
        }

        // Zero because the resolver does not have associated event.
        mock_resolver::set_associated_notifiable_event(false);
        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(0, $schedule_resolver->get_minimum_offset());

        // Change it to have associated event, which we will receive number 1.
        mock_resolver::set_associated_notifiable_event(true);
        $schedule_resolver = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(1, $schedule_resolver->get_minimum_offset());
    }

    /**
     * This test can help us catch the database issue with using MIN/MAX in SQL.
     * Thanks MSSQL :)
     *
     * @return void
     */
    public function test_get_maximum_and_minimum_schedule_offset(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $generator->include_mock_recipient();

        $extended_context = extended_context::make_system();

        $generator->create_notification_preference(
            mock_scheduled_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(3),
            ]
        );
        $generator->create_notification_preference(
            mock_scheduled_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(2),
            ]
        );
        $generator->create_notification_preference(
            mock_scheduled_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(1),
            ]
        );
        $generator->create_notification_preference(
            mock_scheduled_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => 0,
            ]
        );
        $generator->create_notification_preference(
            mock_scheduled_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(1),
            ]
        );
        $generator->create_notification_preference(
            mock_scheduled_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(2),
            ]
        );
        $generator->create_notification_preference(
            mock_scheduled_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(3),
            ]
        );
        $generator->create_notification_preference(
            mock_scheduled_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(4),
            ]
        );

        $resolver_schedule = event_resolver_schedule::instance(mock_scheduled_resolver::class);
        self::assertEquals(
            schedule_helper::days_to_seconds(-3),
            $resolver_schedule->get_minimum_offset(),
        );

        self::assertEquals(
            schedule_helper::days_to_seconds(4),
            $resolver_schedule->get_maximum_offset(),
        );
    }

    /**
     * @return void
     */
    public function test_get_maximum_with_null_returned(): void {
        // Before this test, at setUp() - everything had already been purged.
        // Therefore there should be no records nor built-in records within the system.
        $resolver_schedule = event_resolver_schedule::instance(mock_scheduled_resolver::class);
        self::assertNull($resolver_schedule->get_maximum_offset());
    }

    /**
     * @return void
     */
    public function test_get_minimum_with_null_returned(): void {
        // Before this test, at setUp() - everything had already been purged.
        // Therefore there should be no records nor built-in records within the system
        $resolver_schedule = event_resolver_schedule::instance(mock_scheduled_resolver::class);
        self::assertNull($resolver_schedule->get_minimum_offset());
    }

    /**
     * @return void
     */
    public function test_get_maximum_without_built_in_notification(): void {
        $generator = generator::instance();
        $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(1)
            ]
        );

        $resolver_schedule = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(-DAYSECS, $resolver_schedule->get_maximum_offset());
    }

    /**
     * @return void
     */
    public function test_get_minimum_without_built_in_notification(): void {
        $generator = generator::instance();
        $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(1)
            ]
        );

        $resolver_schedule = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(DAYSECS, $resolver_schedule->get_minimum_offset());
    }

    /**
     * @return void
     */
    public function test_get_maximum_with_only_zero_and_interface_implemented(): void {
        $generator = generator::instance();
        $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => 0
            ]
        );

        mock_resolver::set_associated_notifiable_event(true);
        $resolver_schedule = event_resolver_schedule::instance(mock_resolver::class);

        self::assertNull($resolver_schedule->get_maximum_offset());
    }

    /**
     * @return void
     */
    public function test_get_minimum_with_only_zero_and_interface_implemented(): void {
        $generator = generator::instance();
        $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => 0
            ]
        );

        mock_resolver::set_associated_notifiable_event(true);
        $resolver_schedule = event_resolver_schedule::instance(mock_resolver::class);

        self::assertNull($resolver_schedule->get_minimum_offset());
    }

    /**
     * @return void
     */
    public function test_get_maximum_with_only_zero_and_without_interface_implemented(): void {
        $generator = generator::instance();
        $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => 0
            ]
        );

        mock_resolver::set_associated_notifiable_event(false);
        $resolver_schedule = event_resolver_schedule::instance(mock_resolver::class);

        self::assertEquals(0, $resolver_schedule->get_maximum_offset());
    }

    /**
     * @return void
     */
    public function test_get_minimum_with_only_zero_and_without_interface_implemented(): void {
        $generator = generator::instance();
        $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => 0
            ]
        );

        mock_resolver::set_associated_notifiable_event(false);
        $resolver_schedule = event_resolver_schedule::instance(mock_resolver::class);

        self::assertEquals(0, $resolver_schedule->get_minimum_offset());
    }

    /**
     * Test min(null, number) => null. Which is NOT what we would want.
     *
     * @return void
     */
    public function test_get_minimum_without_preferences_record_but_only_built_in(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);

        mock_built_in::set_default_schedule_offset(15);
        mock_resolver::set_associated_notifiable_event(true);

        $resolver_schedule = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(15, $resolver_schedule->get_minimum_offset());
    }

    /**
     * Test max(null, number) => number. Which is what we would want.
     *
     * @return void
     */
    public function test_get_maximum_without_preferences_record_but_only_built_in(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component(mock_built_in::class);

        mock_built_in::set_default_schedule_offset(-15);
        mock_resolver::set_associated_notifiable_event(true);

        $resolver_schedule = event_resolver_schedule::instance(mock_resolver::class);
        self::assertEquals(-15, $resolver_schedule->get_maximum_offset());
    }

    /**
     * @return void
     */
    public function test_get_maximum_with_preferences_record_only_but_without_built_in(): void {
        $generator = generator::instance();
        $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => 0
            ]
        );

        mock_resolver::set_associated_notifiable_event(false);
        $resolver_schedule = event_resolver_schedule::instance(mock_resolver::class);

        self::assertEquals(0, $resolver_schedule->get_maximum_offset());
    }
}