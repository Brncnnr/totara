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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\state\activity\activity_state as activity_state_model;

/**
 * Note: It is the responsibility of the query to ensure the user is permitted to see an activity.
 */
class activity_state extends type_resolver {

    /**
     * @param string $field
     * @param activity_state_model $activity_state
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     * @throws \coding_exception
     */
    public static function resolve(string $field, $activity_state, array $args, execution_context $ec) {
        if (!$activity_state instanceof activity_state_model) {
            throw new \coding_exception('Expected activity model');
        }

        switch ($field) {
            case 'code':
                $result = $activity_state::get_code();
                break;
            case 'name':
                $result = $activity_state::get_name();
                break;
            case 'display_name':
                $result = $activity_state::get_display_name();
                break;
            default:
                throw new \coding_exception('Unknown field '.$field.' requested in activity_type type resolver');
        }

        return $result;
    }
}