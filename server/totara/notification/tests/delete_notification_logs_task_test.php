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
 * @author  Simon Player<simon.player@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use mod_facetoface\event\booking_booked;
use mod_facetoface\seminar;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup_status;
use mod_facetoface\totara_notification\resolver\booking_confirmed;
use totara_core\extended_context;
use totara_notification\entity\notification_event_log as notification_event_log_entity;
use totara_notification\entity\notification_log as notification_log_entity;
use totara_notification\task\delete_notification_logs_task;
use totara_notification\model\notification_delivery_log as model;
use totara_notification\model\notification_event_log as notification_event_log_model;
use totara_notification\model\notification_log as notification_log_model;

class delete_notification_logs_task_testcase extends testcase {

    /**
     * Test the delete_notification_logs_task task
     *
     * @return void
     */
    public function test_delete_notification_logs_task(): void {
        global $DB, $CFG;

        // Ensure we're starting with no existing logs.
        $DB->delete_records('notification_event_log');
        $DB->delete_records('notification_log');
        $DB->delete_records('notification_delivery_log');
        $this->validate_table_count(0);

        // Create 10 notification logs.
        $log_ids = $this->create_notification_logs(10);

        // Check we have the 10.
        self::assertCount(10, $log_ids);
        $this->validate_table_count(10);

        $time = time();

        // Update the time_created database field values.
        $this->update_time_created($log_ids[1], $time);
        $this->update_time_created($log_ids[2], $time - (5 * DAYSECS));
        $this->update_time_created($log_ids[3], $time - (10 * DAYSECS));
        $this->update_time_created($log_ids[4], $time - (15 * DAYSECS));
        $this->update_time_created($log_ids[5], $time - (25 * DAYSECS));
        $this->update_time_created($log_ids[6], $time - (30 * DAYSECS));
        $this->update_time_created($log_ids[7], $time - (35 * DAYSECS));
        $this->update_time_created($log_ids[8], $time - (40 * DAYSECS));
        $this->update_time_created($log_ids[9], $time - (45 * DAYSECS));
        $this->update_time_created($log_ids[10], $time - (50 * DAYSECS));

        // No logs should be deleted when totara_notification_log_days_to_keep is zero.
        $CFG->totara_notification_log_days_to_keep = 0;
        $output = $this->run_task();
        $this->validate_table_count(10);
        self::assertSame("", $output);

        // Set totara_notification_log_days_to_keep to 60. No logs should be deleted.
        $CFG->totara_notification_log_days_to_keep = 60;
        $output = $this->run_task();
        $this->validate_table_count(10);
        self::assertSame("Deleted old notification log records from 'notification_event_log', 'notification_log' and 'notification_delivery_log'", trim($output));

        // Set totara_notification_log_days_to_keep to 36. 3 logs should be deleted, log 8, 9 and 10.
        $CFG->totara_notification_log_days_to_keep = 36;
        $output = $this->run_task();
        $this->validate_table_count(7);
        self::assertSame("Deleted old notification log records from 'notification_event_log', 'notification_log' and 'notification_delivery_log'", trim($output));
        self::assertFalse($DB->record_exists('notification_event_log', ['id' => $log_ids[8]]));
        self::assertFalse($DB->record_exists('notification_event_log', ['id' => $log_ids[9]]));
        self::assertFalse($DB->record_exists('notification_event_log', ['id' => $log_ids[10]]));

        // Set totara_notification_log_days_to_keep to 6. 5 further logs should be deleted, log 3, 4, 5, 6 and 7.
        $CFG->totara_notification_log_days_to_keep = 6;
        $output = $this->run_task();
        $this->validate_table_count(2);
        self::assertSame("Deleted old notification log records from 'notification_event_log', 'notification_log' and 'notification_delivery_log'", trim($output));
        self::assertFalse($DB->record_exists('notification_event_log', ['id' => $log_ids[3]]));
        self::assertFalse($DB->record_exists('notification_event_log', ['id' => $log_ids[4]]));
        self::assertFalse($DB->record_exists('notification_event_log', ['id' => $log_ids[5]]));
        self::assertFalse($DB->record_exists('notification_event_log', ['id' => $log_ids[6]]));
        self::assertFalse($DB->record_exists('notification_event_log', ['id' => $log_ids[7]]));
    }

    /**
     * Create notification logs for the testing
     *
     * @param int $amount The amount of logs to create
     * @return array Array of notification_event_log table ids
     */
    private function create_notification_logs(int $amount = 10) : array {
        $generator = self::getDataGenerator();

        $log_ids = [];

        // Create a course.
        $course = $generator->create_course(['fullname' => 'Course 1']);

        // Create a seminar activity.
        $f2f_gen = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2f_gen->create_instance(['course' => $course->id]);
        $seminar = new seminar($f2f->id);
        $seminarevent = $f2f_gen->create_session_for_course($course);
        $seminarevent->set_facetoface($seminar->get_id())->save();

        // Loop to create the $amount of logs required.
        for ($i = 1; $i <= $amount; $i++) {
            // Create user.
            $user = $generator->create_user(['lastname' => 'User' . $i]);

            // Signup user to seminar.
            $signup = signup::create($user->id, $seminarevent)->save();
            signup_status::create($signup, new booked($signup))->save();
            $cm = $signup->get_seminar_event()->get_seminar()->get_coursemodule();
            $context = context_module::instance($cm->id);
            $event = booking_booked::create_from_signup($signup, $context);
            $event->trigger();

            $event_data = $event->get_data();

            $extended_context = extended_context::make_with_context(
                $context,
                $event_data['component'],
                'seminar',
                $cm->id
            );

            /** @var notification_event_log_entity $notification_event_log */
            $notification_event_log = notification_event_log_model::create(
                booking_confirmed::class,
                $extended_context,
                $user->id,
                $event_data,
                '',
                '',
                '',
                [],
                false
            );

            /** @var notification_log_entity $notification_log */
            $notification_log = notification_log_model::create(
                $notification_event_log->get_id(),
                1,
                $user->id,
                time()
            );

            // Create notification delivery entry.
            model::create(
                $notification_log->get_id(),
                'email',
                time(),
                'user@example.com'
            );

            $log_ids[$i] = $notification_event_log->get_id();
        }

        return $log_ids;
    }

    /**
     * Test the db table counts are what is expected
     *
     * @param int $expected
     */
    private function validate_table_count(int $expected) : void {
        global $DB;

        self::assertEquals($expected, $DB->count_records('notification_event_log'));
        self::assertEquals($expected, $DB->count_records('notification_log'));
        self::assertEquals($expected, $DB->count_records('notification_delivery_log'));
    }

    /**
     * Update the time_created database field
     *
     * @param int $notification_event_log_id
     * @param int $timestamp
     */
    private function update_time_created(int $notification_event_log_id, int $timestamp) : void {
        global $DB;

        $record = $DB->get_record('notification_event_log', ['id' => $notification_event_log_id], '*', MUST_EXIST);
        $record->time_created = $timestamp;
        $DB->update_record('notification_event_log', $record);
    }

    /**
     * Run the delete_notification_logs_task task
     *
     * @return string Output from the task
     */
    private function run_task() : string {
        $sink = $this->redirectEvents();
        $task = new delete_notification_logs_task();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $sink->close();
        return $output;
    }
}