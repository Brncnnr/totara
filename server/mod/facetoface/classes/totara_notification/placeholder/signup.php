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
 * @author  Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_facetoface
 * @category totara_notification
 */

namespace mod_facetoface\totara_notification\placeholder;

use coding_exception;
use mod_facetoface\seminar_event;
use mod_facetoface\signup as signup_object;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

class signup extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /** @var ?signup_object */
    private $signup;

    /**
     * event constructor.
     * @param signup_object|null $signup
     */
    public function __construct(?signup_object $signup = null) {
        $this->signup = $signup;
    }

    /**
     * @param int $event_id
     * @param int $user_id
     *
     * @return self
     */
    public static function from_event_id_and_user_id(int $event_id, int $user_id): self {
        $cache_key = $event_id . ':' . $user_id;

        $instance = self::get_cached_instance($cache_key);
        if (!$instance) {
            $event = new seminar_event($event_id);
            $signup = signup_object::create($user_id, $event);
            $instance = new static($signup);
            self::add_instance_to_cache($cache_key, $instance);
        }

        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        // Went for notification_placeholder_... to prevent overlap with existing strings
        return [
            option::create('cost', get_string('notification_placeholder_signup_cost', 'mod_facetoface')),
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return $this->signup !== null;
    }

    /**
     * @param string $key
     * @return string
     */
    public function do_get(string $key): string {
        if ($this->signup === null) {
            throw new coding_exception("The seminar signup is empty");
        }

        switch ($key) {
            case 'cost':
                return $this->signup->get_cost();
        }

        throw new coding_exception("Invalid key '$key'");
    }
}