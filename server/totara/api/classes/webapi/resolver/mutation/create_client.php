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
use core_text;
use totara_api\exception\create_client_exception;
use totara_api\model\client;
use totara_api\webapi\middleware\require_manage_capability;

/**
 * Class create_client
 * @package totara_api\webapi\resolver\mutation
 */
class create_client extends mutation_resolver {
    /**
     * @var int
     */
    private const NAME_LENGTH = 75;

    /**
     * @var int
     */
    private const DESCRIPTION_LENGTH = 1024;

    /**
     *
     * @param array             $args
     * @param execution_context $ec
     * @return client
     */
    public static function resolve(array $args, execution_context $ec): client{
        $input = $args['input'];
        $name = $input['name'];
        $user_id = $input['user_id'] ?? null;
        $description = $input['description'] ?? '';
        $tenant_id = $input['tenant_id'] ?? null;
        $status = $input['status'] ?? true;

        if (!isset($name) || !isset($user_id)) {
            throw new coding_exception('No required parameters being passed');
        }

        if (core_text::strlen($name) > self::NAME_LENGTH) {
            throw new create_client_exception('Name must not exceed 75 characters.');
        }

        if (core_text::strlen($description) > self::DESCRIPTION_LENGTH) {
            throw new create_client_exception('Description must not exceed 1024 characters.');
        }

        return client::create($name, $user_id, $description, $tenant_id, $status, ['create_client_provider' => true]);
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            new require_advanced_feature('api'),
            require_manage_capability::by_tenant_id('input.tenant_id', true)
        ];
    }
}