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
 * @package totara_playlist
 */
namespace totara_playlist\pagination;

use core\pagination\offset_cursor;

/**
 * A cursor that has the ability to set the next fetching limitation.
 */
final class cursor extends offset_cursor {
    /**
     * @var int
     */
    public const LIMIT = 50;

    /**
     * Returning the the next limit for the next cursor, and default to
     * 50 for now.
     *
     * @return int
     */
    public function get_limit_next_cursor(): int {
        return static::LIMIT;
    }
}