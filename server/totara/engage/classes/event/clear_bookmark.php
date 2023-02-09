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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\event;

use totara_core\identifier\instance_identifier;

interface clear_bookmark {
    /**
     * Sharing instance identifier to search for the share records.
     *
     * @return instance_identifier
     */
    public function get_instance_identifier(): instance_identifier;

    /**
     * Whether we are to clear the book mark or not.
     *
     * @return bool
     */
    public function is_to_clear(): bool;

    /**
     * The list of target user's id that we want to clear bookmark for.
     *
     * @return int[]
     */
    public function get_target_user_ids(): array;
}