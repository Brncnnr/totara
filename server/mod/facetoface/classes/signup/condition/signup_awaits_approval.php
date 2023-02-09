<?php
/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup\condition;

use mod_facetoface\signup\state\{requested, requestedadmin, requestedrole};

defined('MOODLE_INTERNAL') || die();

/**
 * Class signup_awaits_approval
 */
class signup_awaits_approval extends condition {

    /**
     * Returns true if the signup status is one of manager approval states.
     * @return boolean
     */
    public function pass() : bool {
        if (in_array($this->signup->get_state()->get_code(), [requested::get_code(), requestedadmin::get_code(), requestedrole::get_code()])) {
            return true;
        }
        return false;
    }

    public static function get_description() : string {
        return get_string('state_signup_awaits_approval_desc', 'mod_facetoface');
    }

    public function get_failure(): array {
        return ['signup_awaits_approval' => get_string('state_signup_awaits_approval_fail', 'mod_facetoface')];
    }
}
