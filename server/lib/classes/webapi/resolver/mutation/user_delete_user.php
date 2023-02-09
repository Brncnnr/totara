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
 * @author Angela Kuznetsova <anzhela.kuznetsova@totaralearning.com>
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
use core_user\exception\delete_user_exception;
use core_user\external\user_helper;
use core_user\external\user_interactor;

/**
 * Mutation to delete a user.
 */
class user_delete_user extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        if (!isset($args['target_user'])) {
            throw new coding_exception("Required parameter 'target_user' not being passed.");
        }
        $user_to_delete = $args['target_user'];
        $acting_user = user::logged_in();

        try {
            $user_record_reference = new user_record_reference();
            $target_user = $user_record_reference->get_record($user_to_delete);
        } catch (unresolved_record_reference $exception) {
            throw new delete_user_exception('There was a problem finding a single user record match or you do not have sufficient capabilities.');
        }

        // When using the External API, don't allow an API client service account user to delete him/herself.
        // (This would effectively disable the API client.)
        if (user::logged_in()->id == $target_user->id) {
            throw new delete_user_exception('A service account user is not allowed to delete itself when making a request.');
        }

        $interactor = new user_interactor($acting_user->id, $target_user->id);

        if (!$interactor->can_delete_user()) {
            throw new delete_user_exception('You do not have capabilities to delete a user.');
        }

        return [
            'user_id' => user_helper::delete_user($target_user)
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