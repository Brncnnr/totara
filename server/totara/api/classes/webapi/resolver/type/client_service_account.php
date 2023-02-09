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
 * @author Scott Davies <scott.davies@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\webapi\resolver\type;

use coding_exception;
use core\webapi\type_resolver;
use totara_api\pdo\client_service_account as model;
use core\webapi\execution_context;

/**
 * GraphQL type resolver for ServiceAccount on an API client.
 */
class client_service_account extends type_resolver {
    /**
     * @param string $field
     * @param $service_account
     * @param array $args
     * @param execution_context $ec
     * @return mixed|void
     */
    public static function resolve(string $field, $service_account, array $args, execution_context $ec) {
        if (!($service_account instanceof model)) {
            throw new coding_exception('Expected client_service_account instance');
        }
        switch ($field) {
            case 'is_valid':
                return $service_account->get_is_valid();
            case 'status':
                return $service_account->get_status();
            case 'user':
                return $service_account->get_user();
            default:
                throw new coding_exception("Unknown field '$field' requested in service_account type resolver");
        }
    }

}
