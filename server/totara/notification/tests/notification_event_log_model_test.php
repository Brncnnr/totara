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
use totara_notification\entity\notification_event_log as event_log_entity;
use totara_notification\model\notification_event_log as event_log_model;

class notification_event_log_model_testcase extends testcase {

    /**
     * @return void
     */
    public function test_create(): void {
        $generator = self::getDataGenerator();

        $user1 = $generator->create_user(['shortname' => 'user1', 'lastname' => 'User1 last name']);
        $event_data = ['fld1' => 'a', 'fld2' => 'b'];

        $course = $generator->create_course(['fullname' => 'Test course']);

        $system_extended_context = extended_context::make_system();
        $course_extended_context = extended_context::make_with_context(context_course::instance($course->id));

        builder::table(event_log_entity::TABLE)->delete();
        self::assertSame(0, builder::table(event_log_entity::TABLE)->count());

        $time1 = 1;
        $time2 = 2;

        // Same entity twice
        $log1 = event_log_model::create_if_not_exist(
            'some_resolver_class',
            $system_extended_context,
            $user1->id,
            $event_data,
            'some_schedule_class',
            -123,
            'string_key',
            ['string_component'],
            $time1,
            false
        );
        self::assertSame(1, builder::table(event_log_entity::TABLE)->count());

        $log2 = event_log_model::create_if_not_exist(
            'some_resolver_class',
            $system_extended_context,
            $user1->id,
            $event_data,
            'some_schedule_class',
            -123,
            'string_key',
            ['string_component'],
            $time1,
            false
        );
        self::assertSame(1, builder::table(event_log_entity::TABLE)->count());
        self::assertSame($log1->id, $log2->id);

        // Different schedule - different events
        $log3 = event_log_model::create_if_not_exist(
            'some_resolver_class',
            $system_extended_context,
            $user1->id,
            $event_data,
            'another_schedule_class',
            -123,
            'string_key',
            ['string_component'],
            $time1,
            false
        );
        self::assertSame(2, builder::table(event_log_entity::TABLE)->count());
        self::assertNotSame($log1->id, $log3->id);

        // Different context - different events
        $log4 = event_log_model::create_if_not_exist(
            'some_resolver_class',
            $course_extended_context,
            $user1->id,
            $event_data,
            'some_schedule_class',
            -123,
            'string_key',
            ['string_component'],
            $time1,
            false
        );
        self::assertSame(3, builder::table(event_log_entity::TABLE)->count());
        self::assertNotSame($log1->id, $log4->id);
        self::assertNotSame($log3->id, $log4->id);

        // Different time - different events
        $log5 = event_log_model::create_if_not_exist(
            'some_resolver_class',
            $system_extended_context,
            $user1->id,
            $event_data,
            'some_schedule_class',
            -123,
            'string_key',
            ['string_component'],
            $time2,
            false
        );
        self::assertSame(4, builder::table(event_log_entity::TABLE)->count());
        self::assertNotSame($log1->id, $log5->id);
        self::assertNotSame($log3->id, $log5->id);
        self::assertNotSame($log4->id, $log5->id);

        // Different event_data - different events
        $log6 = event_log_model::create_if_not_exist(
            'some_resolver_class',
            $system_extended_context,
            $user1->id,
            ['different'],
            'some_schedule_class',
            -123,
            'string_key',
            ['string_component'],
            $time2,
            false
        );
        self::assertSame(5, builder::table(event_log_entity::TABLE)->count());
        self::assertNotSame($log1->id, $log5->id);
        self::assertNotSame($log3->id, $log5->id);
        self::assertNotSame($log4->id, $log5->id);
        self::assertNotSame($log5->id, $log6->id);
    }

    /**
     * @return void
     */
    public function test_error_in_event_data(): void {
        $generator = self::getDataGenerator();

        $user1 = $generator->create_user(['shortname' => 'user1', 'lastname' => 'User1 last name']);
        $event_data = ["\x5A\x6F\xEB"];

        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        // Same entity twice
        event_log_model::create_if_not_exist(
            'some_resolver_class',
            extended_context::make_system(),
            $user1->id,
            $event_data,
            'some_schedule_class',
            -123,
            'string_key',
            ['string_component'],
            time(),
            false
        );
    }

    /**
     * @return void
     */
    public function test_create_with_long_display_string_params(): void {
        global $DB;

        $generator = self::getDataGenerator();

        $user = $generator->create_user(['shortname' => 'user1', 'lastname' => 'User1 last name']);

        $log = event_log_model::create(
            'some_resolver_class',
            extended_context::make_system(),
            $user->id,
            ['fld1' => 'a', 'fld2' => 'b'],
            'some_schedule_class',
            -123,
            'string_key',
            [
                'component' => "totara_mock",
                "params" => [
                    'resolver_title' => 'resolver_title',
                    'user' => 'Tom Jackson',
                    'course' => 'course name is very long'
                ]
            ],
            time(),
        );

        $display_string_params = $DB->get_field('notification_event_log', 'display_string_params', ['id' => $log->get_id()]);

        self::assertStringContainsString(
            '{"component":"totara_mock","params":{"resolver_title":"resolver_title","user":"Tom Jackson","course":"course name is very long"}}',
            $display_string_params
        );

        self::assertEquals(
            strlen('{"component":"totara_mock","params":{"resolver_title":"resolver_title","user":"Tom Jackson","course":"course name is very long"}}'),
            strlen($display_string_params)
        );
    }

}