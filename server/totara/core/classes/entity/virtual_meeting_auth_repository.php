<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\entity;

use core\orm\entity\repository;
use core\orm\query\builder;

/**
 * Virtual meeting auth token repository
 */
class virtual_meeting_auth_repository extends repository {
    /**
     * Find a virtual_meeting_auth entity.
     *
     * @param string $plugin plugin name
     * @param integer $userid userid or 0 for the current user
     * @param boolean $strict blow up if a record not found
     * @return virtual_meeting_auth|null
     */
    public function find_by_plugin_and_user(string $plugin, int $userid = 0, bool $strict = false): ?virtual_meeting_auth {
        global $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        return builder::table($this->get_table())
            ->where('plugin', $plugin)
            ->where('userid', $userid)
            ->map_to(virtual_meeting_auth::class)
            ->one($strict);
    }
}
