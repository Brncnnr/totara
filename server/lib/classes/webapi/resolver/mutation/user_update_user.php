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

use coding_exception;
use core\entity\user;
use core\exception\unresolved_record_reference;
use core\reference\user_record_reference;
use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use core_user\exception\update_user_exception;
use core_user\external\user_helper;
use core_user\external\user_interactor;

/**
 * Mutation to update the user.
 */
class user_update_user extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        if (!isset($args['target_user'])) {
            throw new coding_exception("Required parameter 'target_user' not being passed");
        }

        if (!isset($args['input'])) {
            throw new coding_exception("Required parameter 'input' not being passed");
        }

        $user_to_update = $args['target_user'];
        $acting_user = user::logged_in();

        try {
            $user_record_reference = new user_record_reference();
            $target_user = $user_record_reference->get_record($user_to_update);
        } catch (unresolved_record_reference $exception) {
            throw new update_user_exception('There was a problem finding a single user record match or you do not have sufficient capabilities.');
        }

        $interactor = new user_interactor($acting_user->id, $target_user->id);

        if (!$interactor->can_update_user()) {
            throw new update_user_exception('You do not have capabilities to update a user.');
        }

        $auth_plugin = get_auth_plugin($target_user->auth);
        if (!$auth_plugin->can_edit_profile()) {
            throw new update_user_exception('The authentication method does not support editing profile.');
        }

        $input = $args['input'];
        if ((isset($input['username']) || isset($input['auth'])) && !$interactor->has_user_update_capability()) {
            throw new update_user_exception('You do not have capabilities to update username or auth plugin.');
        }

        if (isset($input['tenant']) && !$interactor->has_tenancy_update_capability()) {
            throw new update_user_exception('You do not have capabilities to update user tenancy membership.');
        }

        if (isset($input['password']) && mb_strlen($input['password']) >= 0) {
            if (!$auth_plugin->can_change_password()) {
                throw new update_user_exception('The authentication method does not support password changes.');
            }
            if (is_siteadmin($target_user)) {
                throw new update_user_exception('You can not update password for the admin user.');
            }
            if (isset($input['generate_password']) && $input['generate_password']) {
                throw new update_user_exception('You cannot set new password and generate password at the same time.');
            }
            if (!$interactor->has_user_managelogin_capability()) {
                throw new update_user_exception('You do not have capabilities to update a user password.');
            }
        }

        if (isset($input['suspended'])) {
            if (is_siteadmin($target_user)) {
                throw new update_user_exception('The admin user cannot be suspended.');
            }
            // When using the External API, don't allow an API client service account user to suspend him/herself.
            // (This would effectively disable the API client.)
            if (user::logged_in()->id == $target_user->id && $input['suspended'] == true) {
                throw new update_user_exception('A service account user is not allowed to suspend itself when making a request.');
            }
            if (!$interactor->has_user_update_capability() || !$interactor->has_user_managelogin_capability()) {
                throw new update_user_exception('You do not have capabilities to suspend a user.');
            }
        }

        return [
            'user' => user_helper::update_user($target_user, $args['input'])
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
        ];
    }
}