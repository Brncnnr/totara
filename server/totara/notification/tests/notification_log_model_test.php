<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\entity\notification_log as log_entity;
use totara_notification\model\notification_event_log as event_log_model;
use totara_notification\model\notification_log as log_model;
use totara_notification\testing\generator;

class notification_log_model_testcase extends testcase {

    /**
     * @return void
     */
    public function test_create(): void {
        $generator = generator::instance();

        $user1 = self::getDataGenerator()->create_user(['shortname' => 'user1', 'lastname' => 'User1 last name']);
        $user2 = self::getDataGenerator()->create_user(['shortname' => 'user1', 'lastname' => 'User1 last name']);

        $event_data = ['fld1' => 'a', 'fld2' => 'b'];
        $system_extended_context = extended_context::make_system();

        $event_log1 = event_log_model::create(
            'some_resolver_class',
            $system_extended_context,
            $user1->id,
            $event_data,
            'some_schedule_class',
            123,
            'string_key',
            ['string_component'],
            time()
        );

        $event_log2 = event_log_model::create(
            'another_resolver_class',
            $system_extended_context,
            $user1->id,
            $event_data,
            'some_schedule_class',
            123,
            'string_key',
            ['string_component'],
            time()
        );

        $generator->include_mock_scheduled_event_with_on_event_resolver();
        $preference1 = $generator->create_notification_preference(
            totara_notification_mock_scheduled_event_with_on_event_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => 'some_recipient_class',
                'schedule_offset' => 3,
                'title' => '',
                'subject' => '',
                'subject_format' => 2,
                'notification_class_name' => ''
            ]
        );

        $generator->include_mock_scheduled_aware_notifiable_event_resolver();
        $preference2 = $generator->create_notification_preference(
            totara_notification_mock_scheduled_aware_event_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => 'some_recipient_class',
                'schedule_offset' => 3,
                'title' => '',
                'subject' => '',
                'subject_format' => 2,
                'notification_class_name' => ''
            ]
        );
        builder::table(log_entity::TABLE)->delete();
        self::assertSame(0, builder::table(log_entity::TABLE)->count());

        // First recipient
        $now = time();
        $log1 = log_model::create(
            $event_log1->id,
            $preference1->get_id(),
            $user1->id,
            $now
        );
        self::assertSame(1, builder::table(log_entity::TABLE)->count());

        // Exact same data
        $log2 = log_model::create(
            $event_log1->id,
            $preference1->get_id(),
            $user1->id,
            $now
        );
        self::assertSame(2, builder::table(log_entity::TABLE)->count());
        self::assertNotSame($log1->id, $log2->id);

        // Different recipient
        $log3 = log_model::create(
            $event_log1->id,
            $preference1->get_id(),
            $user2->id,
            $now
        );
        self::assertSame(3, builder::table(log_entity::TABLE)->count());
        self::assertNotSame($log1->id, $log2->id);
        self::assertNotSame($log1->id, $log3->id);

        // Different preference
        $log4 = log_model::create(
            $event_log1->id,
            $preference2->get_id(),
            $user1->id,
            $now
        );
        self::assertSame(4, builder::table(log_entity::TABLE)->count());
        self::assertNotSame($log1->id, $log4->id);
        self::assertNotSame($log2->id, $log4->id);
        self::assertNotSame($log3->id, $log4->id);

        // Different event_log
        $log5 = log_model::create(
            $event_log2->id,
            $preference1->get_id(),
            $user1->id,
            $now
        );
        self::assertSame(5, builder::table(log_entity::TABLE)->count());
        self::assertNotSame($log1->id, $log5->id);
        self::assertNotSame($log3->id, $log2->id);
        self::assertNotSame($log3->id, $log5->id);
        self::assertNotSame($log4->id, $log5->id);
    }

}