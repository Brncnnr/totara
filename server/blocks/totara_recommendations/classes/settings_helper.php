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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package block_totara_recommendations
 */

namespace block_totara_recommendations;

/**
 * Helper for admin settings page.
 */
final class settings_helper {
    /**
     * @var int
     */
    const TILE = 0;

    /**
     * @var int
     */
    const LIST = 1;

    /**
     * Default option to display in either tile or list.
     *
     * @var int
     */
    const DEFAULT_DISPLAY_TYPE = self::TILE;

    /**
     * Default option, hide/show the rating/comments/likes
     * 0 = no, 1 = yes
     *
     * @var int
     */
    const DEFAULT_SHOW_RATINGS = 1;

    /**
     * Array of integers to be used for a select list in either ascending
     * or descending sequence.
     *
     * @param int $from Start point for list
     * @param int $to   Desired end point
     * @param int $step Size of jump between points
     * @return array of integers
     */
    public static function get_counter(int $from, int $to, int $step): array {
        $counters = [];
        if ($from < $to) {
            // Ascending list.
            $to += 1;
            for ($i = $from; $i < $to; $i += $step) {
                $counters[$i] = $i;
            }
        } else {
            // Descending list.
            $to -= 1;
            for ($i = $from; $i > $to; $i -= $step) {
                $counters[$i] = $i;
            }
        }

        return $counters;
    }
}
