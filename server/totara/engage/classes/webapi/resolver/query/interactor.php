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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_engage\access\access_manager;
use totara_engage\interactor\interactor as resource_interactor;
use totara_engage\resource\resource_factory;
use totara_engage\interactor\interactor_factory;

class interactor extends query_resolver {

    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return resource_interactor
     */
    public static function resolve(array $args, execution_context $ec): resource_interactor {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        // First check if article really exists.
        try {
            $resource = resource_factory::create_instance_from_id($args['resource_id']);
        } catch (\dml_exception $e) {
            throw new \coding_exception("No article found");
        }

        // Check if the user has access to this article.
        if (!access_manager::can_access($resource, $USER->id)) {
            throw new \coding_exception("User with id '{$USER->id}' does not have access to this article");
        }

        // Get the interactor.
        return interactor_factory::create_from_accessible($resource, $USER->id);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('engage_resources'),
        ];
    }

}