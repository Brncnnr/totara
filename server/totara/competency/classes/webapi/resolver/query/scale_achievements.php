<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\query;

use core\entity\user;
use core\orm\collection;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use moodle_exception;
use totara_competency\entity\assignment;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\entity\pathway_achievement;
use totara_competency\helpers\capability_helper;
use totara_competency\models\assignment_user;
use totara_competency\pathway;
use totara_competency\pathway_factory;

/**
 * Returns items for each scale value, which could be criteria groups
 */
class scale_achievements extends query_resolver {

    /**
     * Returns the achievement configuration for a specific competency.
     *
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        self::authorize($args, $ec);

        $pathways = self::get_active_single_value_types($args['assignment_id']);

        $scale_result = [];
        foreach ($pathways as $pathway) {
            $pathway_instance = pathway_factory::from_entity($pathway);
            $pathway_achievement = pathway_achievement::get_current($pathway_instance, $args['user_id']);
            // Let's avoid an additional query later
            $pathway_achievement->relate('pathway', $pathway);

            $scale_value = $pathway_instance->get_scale_value();
            if (!$scale_value) {
                throw new \coding_exception('Expected a single value type to have a scale value');
            }
            if (!isset($scale_result[$scale_value->id])) {
                $scale_result[$scale_value->id] = [
                    'scale_value' => $scale_value,
                    'items' => []
                ];
            }
            $scale_result[$scale_value->id]['items'][] = $pathway_achievement;
        }

        return array_values($scale_result);
    }

    /**
     * Check required features, login, capabilities and whether it's a valid assignment or user
     *
     * @param array $args
     * @param execution_context $ec
     */
    private static function authorize(array $args, execution_context $ec) {
        // Check if the user exists
        if (!user::repository()->find($args['user_id'])) {
            throw new moodle_exception('invaliduser');
        }

        $has_capability = capability_helper::can_view_profile($args['user_id']);
        $has_assignment = (new assignment_user($args['user_id']))->has_assignment($args['assignment_id']);
        // Throwing a bit more generic error message here to not expose too much information
        // This should only be the exception as the query should only be called when the user
        // accessed it via the profile page
        if (!$has_capability || !$has_assignment) {
            throw new moodle_exception('error_invalid_assignment', 'totara_competency');
        }
    }

    /**
     * This is quite specific to this GraphQL query so keeping this here for now
     *
     * @param int $assignment_id
     * @return pathway_entity[]|collection
     */
    private static function get_active_single_value_types(int $assignment_id): collection {
        $single_value_types = pathway_factory::get_single_value_types();

        return pathway_entity::repository()
            ->where('path_type', $single_value_types)
            ->where('status', pathway::PATHWAY_STATUS_ACTIVE)
            ->join([assignment::TABLE, 'ass'], 'competency_id', 'competency_id')
            ->where('ass.id', $assignment_id)
            ->with('competency')
            ->order_by('sortorder', 'asc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('competency_assignment'),
        ];
    }

}
