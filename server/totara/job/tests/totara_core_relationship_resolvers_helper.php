<?php
/*
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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package totara_job
 */

trait totara_core_relationship_resolvers_helper {
    /** @var \core\testing\generator */
    private $generator;
    /** @var array */
    private $users;

    /**
     * Helper function for creating users
     *
     * Example:
     * $users = $this->create_users(['user1', 'user2', 'user3' ...]);
     * OR
     * $users = $this->create_users([
     *      'user1' => ['firstname' => 'John', 'lastname' => 'Hill' ...],
     *      'user2' => ['firstname' => 'Zoe', 'lastname' => 'Zhou' ...],
     *          .
     *          .
     *          .
     * ]);
     *
     * @param array $user_names
     * @return array
     */
    private function create_users(array $user_names): array {
        $users = [];
        foreach ($user_names as $key => $value) {
            if (is_integer($key)) {
                $users[$value] = $this->generator->create_user();
            } else {
                $users[$key] = $this->generator->create_user($value);
            }
        }
        return $users;
    }

    /**
     * Reset properties
     *
     * @return void
     */
    private function reset_helper_properties(): void {
        $this->generator = null;
        $this->users = null;
    }
}