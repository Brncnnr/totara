<?php
/*
 * This file is part of Totara Perform
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\middleware;

use Closure;
use core\entity\user;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use moodle_exception;
use mod_perform\util;

/**
 * Graphql interceptor that checks if a target user is allowed to manage
 * activity participation.
 *
 * If the check passes, the target user is stored in the incoming payload under
 * the require_manage_participants_capability::TARGET_USER_ID key.
 */
class require_manage_participants_capability implements middleware {
    public const TARGET_USER_ID = 'manage_participation_user_id';

    /**
     * @var int user who is going to manage activity participation.
     */
    private $user_id = 0;

    /**
     * Default constructor.
     *
     * @param int $user_id user who is going to manage activity participation.
     *        If unspecified, defaults to the currently logged in user.
     */
    public function __construct(?int $user_id = null) {
        $this->user_id = $user_id;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        // Some phpunit tests create this class without logging in first; that
        // results in a null user if the target user resolution is done in the
        // constructor. Hence this code snippet here.
        $target_user = $this->user_id;
        if (!$target_user) {
            $user = user::logged_in(); // NB: this can return null if logged out!
            if (!$user) {
                throw new require_login_exception('You are not logged in');
            }

            $target_user = $user->id;
        }

        if (!util::can_potentially_manage_participants($target_user)) {
            throw new moodle_exception('cannot_manage_participant', 'mod_perform');
        }

        $payload->set_variable(self::TARGET_USER_ID, $target_user);
        return $next($payload);
    }
}
