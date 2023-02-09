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
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\event;

use totara_notification\event\notifiable_event;

/**
 * Notifiable event when there are learning objects that failed to import to course.
 * Partial completed, and partial failed.
 *
 * @method static import_course_partial_failure create(array $data)
 */
class import_course_partial_failure extends base_import_event implements notifiable_event {
    /**
     * @param array    $learning_object_ids The list of learning object ids that failed to create the course.
     * @param int|null $user_id The actor's id. If null then user in session will be used.
     *
     * @return import_course_partial_failure
     */
    public static function from_list_of_learning_object_ids(
        array $learning_object_ids,
        ?int $user_id = null
    ): import_course_partial_failure {
        $user_id = self::get_user_id($user_id);

        // Everything in here is done as a part of context system.
        return static::create([
            'userid' => $user_id,
            'other' => [
                'learning_object_ids' => $learning_object_ids
            ]
        ]);
    }

    /**
     * @return array
     */
    public function get_notification_event_data(): array {
        return [
            'learning_object_ids' => $this->other['learning_object_ids'],
            'user_id' => $this->userid
        ];
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string("import_course_partial_failure_title", "contentmarketplace_linkedin");
    }

    /**
     * Returns non-localised string.
     *
     * @return string
     */
    public function get_description(): string {
        $learning_object_ids = implode(", ", $this->other['learning_object_ids']);

        return "Several Linkedin Learning learning items ($learning_object_ids) " .
            "were failed to import by user {$this->userid}";
    }
}