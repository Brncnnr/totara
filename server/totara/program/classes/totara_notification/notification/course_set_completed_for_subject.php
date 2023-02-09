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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */
namespace totara_program\totara_notification\notification;

use lang_string;
use totara_notification\notification\built_in_notification;
use totara_notification\schedule\schedule_on_event;
use totara_notification\recipient\subject;
use totara_program\totara_notification\resolver\course_set_completed;

final class course_set_completed_for_subject extends built_in_notification {
    /**
     * @return string
     */
    public static function get_resolver_class_name(): string {
        return course_set_completed::class;
    }

    /**
     * @return string
     */
    public static function get_title(): string {
        return get_string('notification_course_set_completed_for_subject_title', 'totara_program');
    }

    /**
     * @return string
     */
    public static function get_recipient_class_name(): string {
        return subject::class;
    }

    /**
     * @return lang_string
     */
    public static function get_default_body(): lang_string {
        return new lang_string('notification_course_set_completed_for_subject_body', 'totara_program');
    }

    /**
     * @return lang_string
     */
    public static function get_default_subject(): lang_string {
        return new lang_string('notification_course_set_completed_for_subject_subject', 'totara_program');
    }

    /**
     * @return int
     */
    public static function get_default_schedule_offset(): int {
        return schedule_on_event::default_value();
    }

    /**
     * @return bool
     */
    public static function get_default_enabled(): bool {
        return false;
    }
}
