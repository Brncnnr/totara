<?php
/**
 * This file is part of Totara Core
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
 * @package mod_facetoface
 */

use core\entity\adhoc_task;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_facetoface\seminar_event;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\task\send_user_message_adhoc_task;
use mod_facetoface\testing\generator;
use mod_facetoface\seminar_session;

class mod_facetoface_sending_room_notification_testcase extends testcase {
    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp(): void {
        parent::setUp();
        set_config('facetoface_allow_legacy_notifications', 1);
    }

    /**
     * This test is to make sure that we are sending the room's link including the session's date id.
     *
     * @dataProvider data_provider_test_sending
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     * @return void
     */
    public function test_send_confirm_booking_with_room_that_has_session_date_id(bool $site_allow_legacy, bool $use_legacy): void {
        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $user_one = $generator->create_user();
        $generator->enrol_user($user_one->id, $course->id);

        // Create a seminar room with virtual url.
        $seminar_generator = generator::instance();
        $room_record = $seminar_generator->add_site_wide_room([
            "url" => "http://example.com"
        ]);

        // Create a seminar, then add an event with session date to it.
        $f2f_record = $generator->create_module("facetoface",
            ["course" => $course->id, 'legacy_notifications' => (int)$use_legacy]
        );
        $current_time = time();

        $event_id = $seminar_generator->add_session(
            [
                "facetoface" => $f2f_record->id,
                "sessiondates" => [
                    [
                        "timestart" => $current_time + HOURSECS,
                        "timefinish" => $current_time + (HOURSECS * 2),
                        "roomids" => [$room_record->id]
                    ],
                    [
                        "timestart" => $current_time + DAYSECS + HOURSECS,
                        "timefinish" => $current_time + DAYSECS + (HOURSECS * 2),
                        "roomids" => [$room_record->id]
                    ]
                ]
            ]
        );

        // Create a sign up for user one. But first check that the there are no adhoc tasks
        // to send the message to the user.
        $db = builder::get_db();
        $class_name = '\\' . send_user_message_adhoc_task::class;
        self::assertEquals(0, $db->count_records(adhoc_task::TABLE, ["classname" => $class_name]));

        $signup = signup::create($user_one->id, $event_id);
        self::assertTrue(signup_helper::can_signup($signup));
        signup_helper::signup($signup);

        // Check the adhoc tasks that got queued up.
        $expected = (!$site_allow_legacy || ($site_allow_legacy && !$use_legacy)) ? 0 : 1;
        self::assertEquals($expected, $db->count_records(adhoc_task::TABLE, ["classname" => $class_name]));
        $message_sink = self::redirectMessages();

        self::assertEquals(0, $message_sink->count());
        self::assertEmpty($message_sink->get_messages());

        self::executeAdhocTasks();
        $messages = $message_sink->get_messages();

        if (!$site_allow_legacy || ($site_allow_legacy && !$use_legacy)) {
            self::assertEmpty($messages);
            $message_sink->clear();
            return;
        }

        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);

        $message = reset($messages);
        self::assertIsObject($message);

        self::assertObjectHasAttribute("useridto", $message);
        self::assertEquals($user_one->id, $message->useridto);

        self::assertObjectHasAttribute("fullmessage", $message);
        self::assertObjectHasAttribute("fullmessagehtml", $message);

        // Checks that the room link gonna exist in the url or not.
        $session_dates = seminar_event::seek($event_id)->get_sessions();

        /** @var seminar_session $first_session_date */
        $first_session_date = $session_dates->get_first();
        $room_view_link = new moodle_url(
            "/mod/facetoface/reports/rooms.php",
            [
                "roomid" => $room_record->id,
                "sdid" => 0,
                "b" => "/mod/facetoface/view.php?f={$f2f_record->id}"
            ]
        );

        self::assertStringContainsString(
            $room_view_link->out(true, ["sdid" => $first_session_date->get_id()]),
            $message->fullmessagehtml
        );

        /** @var seminar_session $second_session_date */
        $second_session_date = $session_dates->get_last();
        self::assertStringContainsString(
            $room_view_link->out(true, ["sdid" => $second_session_date->get_id()]),
            $message->fullmessagehtml
        );
    }

    public function data_provider_test_sending(): array {
        return [
            [true, true],
            [true, false],
            [false, true],
            [false, false],
        ];
    }
}