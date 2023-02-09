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
 * @author  Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
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
use totara_notification\entity\notification_event_log as entity;
use totara_notification\model\notification_event_log as model;

class notification_event_log_repository_testcase extends testcase {

    private $extended_context = null;

    /**
     * @return void
     */
    public function test_filter_by_extended_context(): void {
        global $DB;
        $DB->delete_records(entity::TABLE);

        self::assertEquals(0, $DB->count_records(entity::TABLE));

        // Generate event and notification event log entries.
        $this->generate_data();

        self::assertEquals(1, $DB->count_records(entity::TABLE));
        $rows = entity::repository()
            ->filter_by_extended_context($this->extended_context)
            ->get()
            ->to_array();

        self::assertEquals(1, count($rows));
    }

    /**
     * @return void
     */
    private function generate_data() {
        global $DB;
        // Create new context
        $generator = self::getDataGenerator();

        // Create a base user.
        $this->user = $generator->create_user(['lastname' => 'User1 last name']);

        // Create a course.
        $this->course = $generator->create_course(['fullname' => 'The first course']);

        $f2f_gen = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2f_gen->create_instance(['course' => $this->course->id]);

        $this->seminar = new seminar($f2f->id);
        $this->seminarevent = $f2f_gen->create_session_for_course($this->course);
        $this->seminarevent->set_facetoface($this->seminar->get_id())->save();

        $signup = signup::create($this->user->id, $this->seminarevent)->save();
        signup_status::create($signup, new booked($signup))->save();
        $cm = $signup->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = context_module::instance($cm->id);
        $event = booking_booked::create_from_signup($signup, $context);
        $event->trigger();

        $event_data = $event->get_data();
        $this->extended_context = extended_context::make_with_context(
            $context,
            $event_data['component'],
            'seminar',
            $cm->id
        );

        model::create(
            booking_confirmed::class,
            $this->extended_context,
            $this->user->id,
            $event_data,
            '',
            '',
            '',
            [],
            false
        );
    }

    /**
     * @return void
     */
    public function tearDown(): void {

        $this->extended_context = null;

        parent::tearDown();
    }
}