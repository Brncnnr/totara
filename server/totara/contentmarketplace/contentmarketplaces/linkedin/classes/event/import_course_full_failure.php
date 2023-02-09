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
 * Notifiable event when there are none of the courses were failed to be created.
 *
 * @method static import_course_full_failure create(array $data)
 */
class import_course_full_failure extends base_import_event implements notifiable_event {
    /**
     * @return array
     */
    public function get_notification_event_data(): array {
        return [
            'user_id' => $this->userid
        ];
    }

    /**
     * @param int|null $actor_id
     * @return import_course_full_failure
     */
    public static function from_actor_id(?int $actor_id = null): import_course_full_failure {
        $actor_id = self::get_user_id($actor_id);

        return static::create(['userid' => $actor_id]);
    }

    /**
     * Returns non-localised string.
     *
     * @return string
     */
    public function get_description(): string {
        return "All the Linkedin Learning learning items were failed to import by user with id {$this->userid}";
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string("import_course_full_failure_title", "contentmarketplace_linkedin");
    }
}