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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 * @category totara_notification
 */

namespace container_workspace\totara_notification\notification;

use container_workspace\totara_notification\resolver\user_added;
use lang_string;
use totara_notification\notification\built_in_notification;
use totara_notification\recipient\subject;
use totara_notification\schedule\schedule_on_event;

final class added_for_subject extends built_in_notification {
    /**
     * @return lang_string
     */
    public static function get_default_body(): lang_string {
        return new lang_string('added_for_subject_body', 'container_workspace');
    }

    /**
     * @return int
     */
    public static function get_default_schedule_offset(): int {
        return schedule_on_event::default_value();
    }

    /**
     * @return lang_string
     */
    public static function get_default_subject(): lang_string {
        return new lang_string('added_for_subject_subject', 'container_workspace');
    }

    /**
     * @return string
     */
    public static function get_recipient_class_name(): string {
        return subject::class;
    }

    /**
     * @return string
     */
    public static function get_resolver_class_name(): string {
        return user_added::class;
    }

    /**
     * @return string
     */
    public static function get_title(): string {
        return get_string('added_for_subject_title', 'container_workspace');
    }
}