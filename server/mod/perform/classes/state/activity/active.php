<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state\activity;

use core\event\base;
use mod_perform\event\activity_activated;
use mod_perform\models\activity\activity;
use mod_perform\state\state_event;

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents the "active" state of an activity.
 *
 * @package mod_perform
 */
class active extends activity_state implements state_event {

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return 'ACTIVE';
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public static function get_display_name(): string {
        return get_string('activity_status_active', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_transitions(): array {
        return [];
    }

    /**
     * Trigger event if the activity gets activated
     *
     * @return base
     */
    public function get_event(): base {
        /** @var activity $activity */
        $activity = $this->object;
        return activity_activated::create_from_activity($activity);
    }

}
