<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core\webapi\middleware\require_advanced_feature;
use mod_perform\webapi\middleware\require_activity;
use moodle_exception;
use container_perform\perform as perform_container;

class delete_activity extends mutation_resolver {
    /**
     * This hard deletes the activity, instances, assignments, responses and elements (if not used by other activities).
     *
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $details = $args['input'] ?? null;
        if (!$details) {
            throw new \invalid_parameter_exception('activity details not given');
        }

        $activity = $args['activity'] ?? null;
        if (empty($activity) || !$activity->can_delete()) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }

        perform_container::from_activity($activity)->delete_activity();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_activity_id('input.activity_id', true)
        ];
    }
}
