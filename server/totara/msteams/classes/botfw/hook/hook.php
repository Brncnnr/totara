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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\hook;

/**
 * Hook interface.
 */
interface hook {
    /**
     * Called by a bot when it opens a new session.
     *
     * @param string $language
     */
    public function open(string $language): void;

    /**
     * Called by a bot when it closes the current session.
     */
    public function close(): void;

    /**
     * Called through bot::set_user() by a client when it needs to substitute a user session.
     *
     * @param integer $userid
     */
    public function set_user(int $userid): void;
}
