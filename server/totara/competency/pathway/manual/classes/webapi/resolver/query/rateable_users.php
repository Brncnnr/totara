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
 * @package pathway_manual
 */

namespace pathway_manual\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use pathway_manual\data_providers\rateable_users as rateable_users_provider;
use pathway_manual\models\rateable_user;
use pathway_manual\models\roles\role_factory;

class rateable_users extends query_resolver {

    /**
     * @param array $args
     * @param execution_context $ec
     * @return rateable_user[]
     */
    public static function resolve(array $args, execution_context $ec) {
        $role = role_factory::create($args['role']);
        $filters = $args['filters'] ?? [];

        // Capabilities are checked inside the data provider.
        return rateable_users_provider::for_role($role)
            ->add_filters($filters)
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
