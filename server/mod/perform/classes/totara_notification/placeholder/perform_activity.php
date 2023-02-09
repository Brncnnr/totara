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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\placeholder;

use coding_exception;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;
use mod_perform\models\activity\activity;

class perform_activity extends single_emptiable_placeholder {
    use placeholder_instance_cache;

    /** @var activity|null $activity */
    private $activity;

    /**
     * Perform placeholder constructor.
     */
    public function __construct(?activity $activity) {
        $this->activity = $activity;
    }

    /**
     * @param int $activity_id
     *
     * @return self
     */
    public static function from_id(int $activity_id): self {

        $instance = self::get_cached_instance($activity_id);
        if (!$instance) {
            $instance = new static(
                activity::load_by_id($activity_id)
            );
            self::add_instance_to_cache($activity_id, $instance);
        }
        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        // Went for notification_placeholder_... to prevent overlap with existing strings
        return [
            option::create('name', get_string('notification_placeholder_activity_name', 'mod_perform')),
            option::create('type', get_string('notification_placeholder_activity_type', 'mod_perform')),
        ];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function is_available(string $key): bool {
        return $this->activity !== null;
    }

    /**
     * @param string $key
     *
     * @return string|null When the result expects an empty string, this should return "",
     * while null should return when the data is not available and will result in "<no data available for $key>".
     */
    public function do_get(string $key): ?string {
        if ($this->activity === null) {
            throw new coding_exception("The performance activity is not available");
        }

        switch ($key) {
            case 'name':
                return $this->activity->name;
            case 'type':
                return $this->activity->get_type()->get_display_name();
        }

        throw new coding_exception("Invalid key '$key'");
    }
}
