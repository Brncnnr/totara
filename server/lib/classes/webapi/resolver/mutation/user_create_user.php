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
 * @package core
 */

namespace core\webapi\resolver\mutation;

use core\entity\user;
use core\exception\unresolved_record_reference;
use core\reference\tenant_record_reference;
use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use core_user\exception\create_user_exception;
use core_user\external\user_helper;
use core_user\external\user_interactor;
use totara_tenant\exception\unresolved_tenant_reference;

/**
 * Mutation to create a new user.
 */
class user_create_user extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        $user = user::logged_in();
        $input = $args['input'];

        $interactor = new user_interactor($user->id);
        if (array_key_exists('tenant', $input)) {
            try {
                $tenant_record_reference = new tenant_record_reference();
                $target_tenant = $tenant_record_reference->get_record($input['tenant']);
                unset($input['tenant']);
                $input['tenantid'] = $target_tenant->id;
            } catch (unresolved_record_reference $exception) {
                throw new unresolved_tenant_reference('Tenant reference must identify exactly one tenant.');
            }
            if (!$interactor->can_create_tenant_user($input['tenantid'])) {
                throw new create_user_exception('You do not have capabilities to create a user for the tenant.');
            }
        } else {
            if (!$interactor->can_create_system_user()) {
                throw new create_user_exception('You do not have capabilities to create a user.');
            }
        }

        if (isset($input['generate_password']) && $input['generate_password']) {
            if (isset($input['password']) && mb_strlen($input['password']) >= 0) {
                throw new create_user_exception('You cannot set password and generate password at the same time.');
            }
        }

        return [
            'user' => user_helper::create_user($input)
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user()
        ];
    }
}