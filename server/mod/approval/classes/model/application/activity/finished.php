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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\application\activity;

use mod_approval\event\application_completed;
use mod_approval\model\application\application;
use mod_approval\model\application\application_activity;

/**
 * Type 48: (workflow) finished.
 */
class finished extends activity {
    /**
     * @param application_activity $activity
     */
    protected function __construct(application_activity $activity) {
        $this->by_user('model_application_activity_type_finished_desc', $activity->user);
    }

    public static function get_type(): int {
        return 48;
    }

    protected static function get_label_key(): string {
        return 'model_application_activity_type_finished';
    }

    public static function trigger_event(application $application, ?int $actor_id, array $activity_info): void {
        application_completed::create_from_application($application, $actor_id)->trigger();
    }

}
