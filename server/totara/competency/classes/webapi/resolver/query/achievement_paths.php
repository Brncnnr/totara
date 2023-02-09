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
use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use moodle_exception;
use totara_competency\entity\assignment;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\helpers\capability_helper;
use totara_competency\models\assignment_user;
use totara_competency\pathway;
use totara_competency\pathway_factory;

/**
 * Returns items for each scale value, which could be criteria groups
 */
class achievement_paths extends query_resolver {
    /**
     * Returns the achivement configuration for a specific competency.
     *
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        self::authorize($args, $ec);

        $classification_enums = [
            pathway::PATHWAY_SINGLE_VALUE => 'SINGLEVALUE',
            pathway::PATHWAY_MULTI_VALUE => 'MULTIVALUE',
        ];

        // For now this is hardcoded, any additional paths will come after those
        $order = [
            'criteria_group',
            'manual',
            'learning_plan'
        ];
        // All single_value class pathways should be grouped together
        $single_value_key = array_search('criteria_group', $order);

        $pathway_types = self::get_active_pathway_types($args['assignment_id']);

        $paths = [];
        foreach ($pathway_types as $pathway_type) {
            /** @var pathway $pathway */
            $pathway_classname = pathway_factory::get_classname($pathway_type);
            $pathway = new $pathway_classname();
            // We want all single value pathways grouped together
            if ($pathway::CLASSIFICATION === pathway::PATHWAY_SINGLE_VALUE) {
                $paths[$single_value_key] = [
                    'class' => $classification_enums[pathway::PATHWAY_SINGLE_VALUE],
                    'type' => null,
                    'name' => $pathway::get_label()
                ];
            } else {
                $order_key = array_search($pathway_type, $order);
                // We have to account for unknown pathways, we add them at the end
                if ($order_key === false) {
                    $order_key = max(count($order) + 1, count($paths) + 1);
                }
                $paths[$order_key] = [
                    'class' => $classification_enums[$pathway::CLASSIFICATION],
                    'type' => $pathway_type,
                    'name' => $pathway::get_label()
                ];
            }
        }

        ksort($paths);

        return array_values($paths);
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

    private static function get_active_pathway_types(int $assignment_id): array {
        return builder::table(pathway_entity::TABLE)
            ->select(['id', 'path_type'])
            ->join([assignment::TABLE, 'ass'], 'competency_id', 'competency_id')
            ->where('status', pathway::PATHWAY_STATUS_ACTIVE)
            ->where('ass.id', $assignment_id)
            ->group_by(['id', 'path_type'])
            ->get()
            ->pluck('path_type');
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
