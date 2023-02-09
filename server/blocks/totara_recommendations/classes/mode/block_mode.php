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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package block_totara_recommendations
 */

namespace block_totara_recommendations\mode;

/**
 * Helper for admin settings page.
 */
interface block_mode {
    /**
     * Whether or not to hide this block if there are no elements
     *
     * @return bool
     */
    public function hide_if_empty(): bool;

    /**
     * The subtitle of the block
     *
     * @return string
     */
    public function get_title(): string;

    /**
     * Get the collection of interaction items
     *
     * @param int $count
     * @param int $user_id
     * @return array
     */
    public function get_items(int $count, int $user_id): array;
}