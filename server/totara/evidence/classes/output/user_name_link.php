<?php
/**
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
 * @package totara_evidence
 */

namespace totara_evidence\output;

use core\entity\user;
use core\output\template;
use moodle_url;

class user_name_link extends template {

    /**
     * Create a type name link from an evidence type model
     *
     * @param user $user
     *
     * @return user_name_link
     */
    public static function create_from_user(user $user): self {
        global $CFG;
        require_once("$CFG->dirroot/user/lib.php");

        return new static([
            'can_view' => user_can_view_profile($user->id),
            'user_fullname' => $user->fullname,
            'profile_url' => new moodle_url('/user/profile.php', ['id' => $user->id]),
        ]);
    }

}
