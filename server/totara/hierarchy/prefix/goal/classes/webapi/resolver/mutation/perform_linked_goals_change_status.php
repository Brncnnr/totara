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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\webapi\resolver\mutation;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use hierarchy_goal\models\company_goal_perform_status;
use hierarchy_goal\models\personal_goal_perform_status;
use mod_perform\models\activity\participant_instance;

/**
 * Mutation to change status of a goal assignment in a review question in a performance activity
 */
class perform_linked_goals_change_status extends mutation_resolver {

    /**
     * Change status of the goal assignment
     *
     * @param array $args
     *
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];

        $participant_instance_id = $input['participant_instance_id'];
        $goal_assignment_id = $input['goal_assignment_id'];
        $scale_value_id = $input['scale_value_id'];
        $section_element_id = $input['section_element_id'];

        if ($input['goal_type'] === 'COMPANY') {
            /** @var company_goal_perform_status $perform_status_class */
            $perform_status_class = company_goal_perform_status::class;
        } else if ($input['goal_type'] === 'PERSONAL') {
            /** @var personal_goal_perform_status $perform_status_class */
            $perform_status_class = personal_goal_perform_status::class;
        } else {
            throw new coding_exception("Invalid goal type {$input['goal_type']}");
        }

        $participant_instance = participant_instance::load_by_id($participant_instance_id);
        $existing_status = $perform_status_class::get_existing_status(
            $goal_assignment_id, $participant_instance->subject_instance_id, $participant_instance->core_relationship_id
        );

        if ($existing_status) {
            return [
                'perform_status' => $existing_status,
                'already_exists' => true,
            ];
        }

        $new_status = $perform_status_class::create(
            $goal_assignment_id,
            $scale_value_id,
            $participant_instance_id,
            $section_element_id
        );

        return ['perform_status' => $new_status];
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('goals'),
            new require_advanced_feature('performance_activities'),
        ];
    }

}
