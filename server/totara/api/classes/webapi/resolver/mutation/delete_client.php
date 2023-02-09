<?php
/**
 * This file is part of Totara Core
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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\webapi\resolver\mutation;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use totara_api\exception\delete_client_exception;
use totara_api\model\client;
use totara_api\webapi\middleware\require_manage_capability;

/**
 * Class delete_client
 * @package totara_api\webapi\resolver\mutation
 */
class delete_client extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec): bool {
        if (!isset($args['id'])) {
            throw new coding_exception('No required parameters being passed');
        }
        $id = $args['id'];

        $client = client::load_by_id($id);
        if (empty($client)) {
            throw new delete_client_exception('Invalid client id');
        }
        $client->delete();
        return true;
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            new require_advanced_feature('api'),
            require_manage_capability::by_client_id('id', true)
        ];
    }
}