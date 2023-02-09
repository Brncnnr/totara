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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_learning_plan
 */

namespace pathway_learning_plan\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use pathway_learning_plan\models\competency_plan;
use totara_competency\helpers\capability_helper;

class competency_plans extends query_resolver {

    /**
     * Queries learning plans and competency plan values for a given user and assignment
     *
     * @param array $args
     * @param execution_context $ec
     * @return competency_plan
     */
    public static function resolve(array $args, execution_context $ec) {
        $user_id = $args['user_id'];

        capability_helper::require_can_view_profile($user_id);

        return competency_plan::for_assignment($args['assignment_id'], $user_id);
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
