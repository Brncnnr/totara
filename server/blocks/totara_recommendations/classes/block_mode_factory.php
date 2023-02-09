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

namespace block_totara_recommendations;

use block_totara_recommendations\mode\block_mode;
use block_totara_recommendations\mode\courses_mode;
use block_totara_recommendations\mode\micro_learning_mode;
use block_totara_recommendations\mode\trending_mode;
use block_totara_recommendations\mode\workspaces_mode;
use totara_core\advanced_feature;

/**
 * Factory to get the correct block mode
 *
 * @package block_totara_recommendations
 */
class block_mode_factory {
    /**
     * @var int
     */
    const BLOCK_TRENDING = 0;

    /**
     * @var int
     */
    const BLOCK_MICRO_LEARNING = 1;

    /**
     * @var int
     */
    const BLOCK_COURSES = 2;

    /**
     * @var int
     */
    const BLOCK_WORKSPACES = 3;

    /**
     * @param int $mode
     * @return block_mode
     */
    public static function get_block_mode(int $mode): block_mode {
        $mode = intval($mode);

        // We have to check the set block mode, as if it is for
        // a feature that's disabled, we need to change the block.
        if ($mode === self::BLOCK_MICRO_LEARNING && advanced_feature::is_enabled('engage_resources')) {
            return new micro_learning_mode();
        } else if ($mode === self::BLOCK_WORKSPACES && advanced_feature::is_enabled('container_workspace')) {
            return new workspaces_mode();
        } else if ($mode === self::BLOCK_COURSES) {
            return new courses_mode();
        }

        // Trending is our default in all cases
        return new trending_mode();
    }
}