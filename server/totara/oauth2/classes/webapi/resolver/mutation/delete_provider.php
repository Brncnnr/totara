<?php
/**
 * This file is part of Totara Core
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_oauth2
 */

namespace totara_oauth2\webapi\resolver\mutation;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\middleware\require_system_capability;
use core\webapi\mutation_resolver;
use totara_oauth2\exception\delete_provider_exception;
use totara_oauth2\model\client_provider;

/**
 * Class delete_provider
 * @package totara_oauth2\webapi\resolver\mutation
 */
class delete_provider extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec): bool {
        $id = $args['id'];
        if (!isset($id)) {
            throw new coding_exception('No required parameters being passed');
        }

        $client_provider = client_provider::load_by_id($id);
        if (!empty($client_provider->internal)) {
            throw new delete_provider_exception('error_delete_internal_provider', 'totara_oauth2');
        }

        $client_provider->delete();
        return true;
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_system_capability('totara/oauth2:manageproviders')
        ];
    }
}