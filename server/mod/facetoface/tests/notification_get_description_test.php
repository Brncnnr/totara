<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\seminar;

class mod_facetoface_notification_get_description_testcase extends advanced_testcase {
    /**
     * @return seminar
     */
    private function create_facetoface(): seminar {
        $generator = static::getDataGenerator();

        $course = $generator->create_course();

        /** @var \mod_facetoface\testing\generator $f2fgenerator */
        $f2fgenerator = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgenerator->create_instance(['course' => $course->id]);

        return new seminar($f2f->id);
    }

    /**
     * @return void
     */
    public function test_get_condition_description_with_notification_map(): void {

        $seminar = $this->create_facetoface();
        $notification = new facetoface_notification(
            [
                'conditiontype' => MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION,
                'facetofaceid' => $seminar->get_id()
            ]
        );

        $conditiondescription = $notification->get_condition_description();
        static::assertNotEmpty($conditiondescription);

        $notification->conditiontype = 1920;
        static::assertEmpty($notification->get_condition_description());
    }

    /**
     * @return void
     */
    public function test_get_list_of_recipients_with_notification_map(): void {
        $seminar = $this->create_facetoface();

        $notification = new facetoface_notification(
            [
                'facetofaceid' => $seminar->get_id(),
                'conditiontype' => MDL_F2F_CONDITION_TRAINER_CONFIRMATION
            ]
        );

        static::assertNotEmpty($notification->get_recipient_description());
        $notification->conditiontype = 192168;
        $notification->requested = 0;
        $notification->ccmanager = 0;
        $notification->cancelled = 0;
        $notification->booked = 0;

        static::assertEmpty($notification->get_recipient_description());
    }
}
