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
 * @package totara_criteria
 */

namespace totara_criteria\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use core_course\user_learning\item;
use Exception;
use totara_competency\helpers\capability_helper;
use totara_criteria\criterion;
use totara_criteria\criterion_not_found_exception;

/**
 * Fetches all achievements for the course related criteria types
 */
abstract class course_achievements extends query_resolver {

    abstract public static function get_criterion(): criterion;

    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG;
        require_once($CFG->dirroot . '/completion/completion_completion.php');

        $instance_id = $args['instance_id'];
        $user_id = $args['user_id'];

        // Currently we only use this for competencies, if the criteria is used
        // later in other areas like goals this need to be refactored
        capability_helper::require_can_view_profile($user_id);

        try {
            $criterion = static::get_criterion();
            $completion_criteria = $criterion::fetch($instance_id);
        } catch (Exception $exception) {
            throw new criterion_not_found_exception();
        }

        $items = [];
        foreach ($completion_criteria->get_item_ids() as $course_id) {
            try {
                $item = ['course' => null];
                $course_record = get_course($course_id);
                // Only add if the user can view the course
                if (totara_course_is_viewable($course_record)) {
                    $item['course'] = item::one($user_id, $course_record);
                }
                $items[] = $item;
            } catch (Exception $exception) {
                // Course not found for some reason, maybe it got deleted?
                // We don't want to return anything in this case.
                continue;
            }
        }

        return [
            'aggregation_method' => $completion_criteria->get_aggregation_method(),
            'required_items' => $completion_criteria->get_aggregation_params()['req_items'] ?? 1,
            'items' => $items,
            'is_valid' => $completion_criteria->is_valid(),
        ];
    }

    public static function is_for_current_user(int $user_id): bool {
        global $USER;
        return $user_id > 0 ? $user_id == $USER->id : false;
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
