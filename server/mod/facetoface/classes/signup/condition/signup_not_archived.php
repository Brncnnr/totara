<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * Class signup_not_archived
 */
class signup_not_archived extends condition {

    /**
     * Returns true unless $this->signup is flagged as archived.
     * @return boolean
     */
    public function pass() : bool {
        return !$this->signup->get_archived();
    }

    public static function get_description() : string {
        return get_string('state_signup_not_archived_desc', 'mod_facetoface');
    }

    public function get_failure(): array {
        return ['signup_not_archived' => get_string('state_signup_not_archived_fail', 'mod_facetoface')];
    }
}
