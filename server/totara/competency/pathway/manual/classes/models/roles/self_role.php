<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

namespace pathway_manual\models\roles;

use coding_exception;
use core\entity\user;

class self_role extends role {

    /**
     * Get the internal system name for this role.
     *
     * @return string
     */
    public static function get_name(): string {
        return 'self';
    }

    /**
     * Get the display name for this role.
     *
     * @return string
     */
    public static function get_display_name(): string {
        return get_string('role_self', 'pathway_manual');
    }

    /**
     * Get the position in which the self role should be displayed in a list.
     *
     * @return int
     */
    public static function get_display_order(): int {
        return 10; // 1st in list.
    }

    /**
     * The current logged in user always has the self role.
     *
     * @param int $subject_user
     * @return bool
     */
    public static function has_for_user(int $subject_user): bool {
        if (is_null(user::logged_in())) {
            // Not logged in
            return false;
        }

        return $subject_user == user::logged_in()->id;
    }

    /**
     * The user can only have the self role in relation to themselves, and no other users, so this is always false.
     *
     * @return bool
     */
    public static function has_for_any(): bool {
        return false;
    }

    /**
     * The capability required to rate themselves.
     *
     * @return string
     */
    protected static function get_capability_name(): string {
        return 'totara/competency:rate_own_competencies';
    }

    public static function apply_role_restriction_to_builder($builder) {
        throw new coding_exception('There is no logical reason to use ' . __FUNCTION__ . '() on ' . __CLASS__ . '!');
    }

}
