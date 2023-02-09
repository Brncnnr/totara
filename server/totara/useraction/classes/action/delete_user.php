<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\action;

use core\entity\user;

/**
 * Action performed by delete user
 */
class delete_user implements action_contract {
    /**
     * Get the localised name of this action.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('action_delete_user', 'totara_useraction');
    }

    /**
     * Delete the specific user.
     *
     * @param user $user
     * @return action_result
     */
    public function execute(user $user): action_result {
        global $CFG;

        if ($user->deleted) {
            return action_result::failure('User was previously deleted');
        }

        require_once $CFG->dirroot . '/user/lib.php';

        try {
            $user_result = user_delete_user($user->to_record());
        } catch (\Exception $ex) {
            debugging('Could not delete: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return action_result::failure($ex->getMessage());
        }

        if (!$user_result) {
            return action_result::failure('Unknown');
        }

        return action_result::success();
    }
}
