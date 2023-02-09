<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\webapi\resolver\query;

use context_user;
use core\entity\user;
use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_user_capability;
use core\webapi\query_resolver;
use core\webapi\middleware\require_login;
use hierarchy_goal\entity\company_goal;
use hierarchy_goal\entity\company_goal_framework;

class company_goal_frameworks extends query_resolver {

    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(context_user::instance(user::logged_in()->id));
        }

        // Default will retrieve the goal framework which has been assigned to a company goal.
        $repo = company_goal_framework::repository();
        if (!isset($args['input']['check_goal_exist']) || $args['input']['check_goal_exist'] !== 0) {
            $repo->where_exists(
                    builder::table(company_goal::TABLE)
                        ->where_field(company_goal_framework::TABLE . '.id', company_goal::TABLE . '.frameworkid')
                );
        }

        return ['items' => $repo->get()->all()];
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('goals'),
            new require_user_capability('totara/hierarchy:viewgoalframeworks'),
        ];
    }
}