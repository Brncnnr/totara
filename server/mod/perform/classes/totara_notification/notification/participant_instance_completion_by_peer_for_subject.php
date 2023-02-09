<?php
/**
 * This file is part of Totara Perform
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\notification;

use lang_string;
use mod_perform\totara_notification\recipient\subject;
use mod_perform\totara_notification\resolver\participant_instance_completion_resolver;
use totara_notification\notification\abstraction\additional_criteria_notification;
use totara_notification\notification\built_in_notification;
use totara_notification\schedule\schedule_on_event;

final class participant_instance_completion_by_peer_for_subject extends built_in_notification implements additional_criteria_notification {
    /**
     * @return string
     */
    public static function get_resolver_class_name(): string {
        return participant_instance_completion_resolver::class;
    }

    /**
     * @return string
     */
    public static function get_title(): string {
        return get_string('notification_participant_instance_completion_by_peer_title', 'mod_perform');
    }

    /**
     * @return string
     */
    public static function get_recipient_class_name(): string {
        return subject::class;
    }

    /**
     * Return the recipient class names.
     *
     * @return string[]
     */
    public static function get_recipient_class_names(): array {
        return [
            subject::class,
        ];
    }

    /**
     * @return lang_string
     */
    public static function get_default_body(): lang_string {
        return new lang_string('notification_participant_instance_completion_by_participant_for_subject_body', 'mod_perform');
    }

    /**
     * @return lang_string
     */
    public static function get_default_subject(): lang_string {
        return new lang_string('notification_participant_instance_completion_by_participant_for_subject_subject', 'mod_perform');
    }

    /**
     * @return int
     */
    public static function get_default_schedule_offset(): int {
        return schedule_on_event::default_value();
    }

    /**
     * @return string
     */
    public static function get_default_additional_criteria(): string {
        return '{"submitted_by":["perform_peer"]}';
    }

    /**
     * @return bool
     */
    public static function get_default_enabled(): bool {
        return false;
    }
}