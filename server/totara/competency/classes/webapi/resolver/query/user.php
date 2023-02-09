<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\query;

use context_user;
use core\entity\user as user_entity;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use pathway_manual\models\roles;
use totara_competency\helpers\capability_helper;

/**
 * Gets a single user record for display in the competency profile, manual rating or self assignment pages.
 */
class user extends query_resolver {

    public static function resolve(array $args, execution_context $ec) {
        $user_id = $args['user_id'];

        $context = context_user::instance($user_id);
        $ec->set_relevant_context($context);

        // Must be able to visit one of the 3 competencies pages this query is used on.
        $can_rate_or_assign = capability_helper::can_assign($user_id, $context)
                    || has_capability('totara/competency:rate_other_competencies', $context)
                    || !empty(roles::get_current_user_roles($user_id));
        if (!$can_rate_or_assign) {
            capability_helper::require_can_view_profile($user_id, $context);
        }

        return new user_entity($user_id);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('competency_assignment'),
        ];
    }

}
