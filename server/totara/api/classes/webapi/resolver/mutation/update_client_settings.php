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

namespace totara_api\webapi\resolver\mutation;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use totara_api\exception\update_client_settings_exception;
use totara_api\model\client_settings;
use totara_api\response_debug;
use totara_api\webapi\middleware\require_manage_capability;

/**
 * Class update_client_settings
 * @package totara_api\webapi\resolver\mutation
 */
class update_client_settings extends mutation_resolver {
    /**
     *
     * @param array             $args
     * @param execution_context $ec
     * @return client_settings
     */
    public static function resolve(array $args, execution_context $ec): client_settings {
        $input = $args['input'];

        if (!isset($input['client_id'])) {
            throw new coding_exception('No required parameters being passed');
        }

        if ((isset($input['client_rate_limit']) && $input['client_rate_limit'] < 0) ||
            (isset($input['default_token_expiry_time']) && $input['default_token_expiry_time'] < 0)
        ) {
            throw new update_client_settings_exception('Can not set it to negative');
        }

        if (array_key_exists('response_debug', $input)) {
            $input['response_debug'] = response_debug::get_value($input['response_debug']);
        }

        $input['client_rate_limit'] = $input['client_rate_limit'] ?? null;
        $input['default_token_expiry_time'] = $input['default_token_expiry_time'] ?? null;

        return client_settings::put($input);
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            new require_advanced_feature('api'),
            require_manage_capability::by_client_id('input.client_id', true)
        ];
    }
}