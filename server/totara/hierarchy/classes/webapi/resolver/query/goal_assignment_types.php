<?php
/*
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\webapi\resolver\query;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\query_resolver;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;

use hierarchy_goal\company_goal_assignment_type;
use hierarchy_goal\personal_goal_assignment_type;

/**
 * Handles the "totara_hierarchy_goal_assignment_types" GraphQL query.
 */
class goal_assignment_types extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $scope = $args['input']['scope'] ?? null;
        if (!$scope) {
            throw new coding_exception('no goal scope provided');
        }

        switch ($scope) {
            case 'COMPANY':
                return company_goal_assignment_type::all();

            case 'PERSONAL':
                return personal_goal_assignment_type::all();
        }

        throw new coding_exception("unknown goal scope: '$scope'");
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('goals'),
            new require_login()
        ];
    }
}
