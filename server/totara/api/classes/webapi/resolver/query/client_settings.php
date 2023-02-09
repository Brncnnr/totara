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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\webapi\resolver\query;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use totara_api\exception\not_found_client_settings_exception;
use totara_api\global_api_config;
use totara_api\response_debug;
use totara_api\webapi\middleware\require_manage_capability;
use totara_api\model\client_settings as model;
use totara_api\entity\client_settings as entity;

/**
 * Class client_settings
 * @package totara_api\webapi\resolver\query
 */
class client_settings extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        if (!isset($args['client_id'])) {
            throw new coding_exception('No required parameters being passed');
        }

        $entity = entity::repository()->find_by_client_id($args['client_id']);

        if (is_null($entity)) {
            // this is edge case, but we still need to handle it.
            throw new not_found_client_settings_exception('The client settings not found');
        }

        return [
            'client_settings' => model::load_by_entity($entity),
            'global_settings' => global_api_config::get_settings_map()
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            new require_advanced_feature('api'),
            require_manage_capability::by_client_id('client_id', true)
        ];
    }
}