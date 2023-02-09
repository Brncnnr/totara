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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package block_totara_recently_viewed
 */

namespace block_totara_recently_viewed;

/**
 * Class settings_helper
 *
 * @package block_totara_recently_viewed
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
     * Default number of items visible
     *
     * @var int
     */
    const DEFAULT_NUMBER_OF_ITEMS = 3;

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
}