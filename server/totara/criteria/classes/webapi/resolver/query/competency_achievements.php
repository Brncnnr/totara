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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_criteria
 */


namespace totara_criteria\webapi\resolver\query;

use core\entity\user;
use core\orm\collection;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use Exception;
use totara_competency\helpers\capability_helper;
use totara_competency\models\assignment_user;
use totara_criteria\criterion;
use totara_criteria\criterion_not_found_exception;
use totara_hierarchy\entity\competency;

/**
 * Fetches all achievements for the competency related criteria types
 */
abstract class competency_achievements extends query_resolver {

    /**
     * get competency items
     *
     * @param criterion $completion_criteria
     * @param int       $user_id
     *
     * @return collection
     */
    abstract protected static function get_competencies(criterion $completion_criteria,
                                                        int $user_id): collection;

    /**
     * get criterion by id
     *
     * @param int $criterion_id
     *
     * @return criterion
     */
    abstract protected static function fetch_criterion(int $criterion_id): criterion;

    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        $instance_id = $args['instance_id'];
        $user_id = $args['user_id'];

        $can_assign = static::authorize($user_id);

        try {
            $completion_criteria = static::fetch_criterion($instance_id);
        } catch (Exception $exception) {
            throw new criterion_not_found_exception();
        }

        $items = static::get_competencies($completion_criteria, $user_id)
            ->map(
                function (competency $competency) use ($can_assign, $user_id) {
                    return self::map_item($competency, $can_assign, $user_id);
                }
            )
            ->key_by('id');

        return [
            'aggregation_method' => $completion_criteria->get_aggregation_method(),
            'required_items'     => $completion_criteria->get_aggregation_params()['req_items'] ?? 1,
            'current_user'       => static::is_for_current_user($user_id),
            'items'              => $items,
            'is_valid'           => $completion_criteria->is_valid(),
        ];
    }

    final protected static function map_item(competency $competency, bool $can_assign, int $user_id) {
        // We need to figure out whether these competencies are assigned to the current user or not.
        // The cheapest way to do so is to check achievement if a competency has an achievement,
        // well it must be assigned to the user...
        $ass_user = new assignment_user($user_id);

        if ($competency->achievement) {
            $assigned = true;
        } else {
            $assigned = $ass_user->has_active_assignments($competency->id);
        }

        return [
            'competency'      => $competency,
            'value'           => $competency->achievement->value ?? null,
            'assigned'        => $assigned,
            'self_assignable' => static::can_assign($competency, $ass_user->get_id(), $can_assign),
            'id'              => $competency->id,
        ];
    }

    /**
     * Check whether a user is able to assign a competency.
     *
     * @param competency $competency Competency id
     * @param int        $user_id    User id
     * @param bool       $can_assign Permission
     *
     * @return bool
     */
    protected static function can_assign(competency $competency, $user_id, $can_assign) {
        if (!$can_assign) {
            return false;
        }

        if (!$competency->visible) {
            return false;
        }

        if (static::is_for_current_user($user_id)) {
            $availability = competency::ASSIGNMENT_CREATE_SELF;
        } else {
            $availability = competency::ASSIGNMENT_CREATE_OTHER;
        }

        return in_array($availability, $competency->availability->pluck('availability'));
    }

    /**
     * Check whether this is for current user
     *
     * @param int $user_id
     *
     * @return bool
     */
    protected static function is_for_current_user(int $user_id): bool {
        return $user_id == user::logged_in()->id;
    }

    /**
     * Authorize a user to make sure he can list and optionally self-assign competencies
     *
     * @param int $user_id
     * @return bool
     */
    public static function authorize(int $user_id): bool {
        capability_helper::require_can_view_profile($user_id);

        return capability_helper::can_assign($user_id);
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
