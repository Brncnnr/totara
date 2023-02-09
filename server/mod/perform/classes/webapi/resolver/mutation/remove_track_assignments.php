<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core\webapi\middleware\require_advanced_feature;

use mod_perform\models\activity\track;
use mod_perform\models\activity\track_assignment_type;

use mod_perform\webapi\middleware\require_activity;

use mod_perform\user_groups\grouping;
use mod_perform\webapi\middleware\require_manage_capability;

/**
 * Handles the "mod_perform_remove_track_assignments" GraphQL mutation.
 */
class remove_track_assignments extends mutation_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $assignments = $args['assignments'] ?? null;
        if (!$assignments) {
            throw new \invalid_parameter_exception("no assignments given'");
        };

        $track_id = $assignments['track_id'] ?? null;
        if (!$track_id) {
            throw new \invalid_parameter_exception('invalid track id');
        }

        $track = track::load_by_id($track_id);

        $assignment_type = $assignments['type'] ?? track_assignment_type::ADMIN;
        $groups = $assignments['groups'] ?? [];

        foreach ($groups as $group) {
            $group_id = $group['id'] ?? 0;
            if (!$group_id) {
                throw new \invalid_parameter_exception('invalid group id');
            }

            $group_type = $group['type'];
            $grouping = grouping::by_type($group_type, $group_id);

            $track->remove_assignment($assignment_type, $grouping);
        }

        return $track;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_track_id('assignments.track_id', true),
            require_manage_capability::class
        ];
    }
}
