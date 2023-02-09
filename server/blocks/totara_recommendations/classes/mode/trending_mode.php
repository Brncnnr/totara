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

use block_totara_recommendations\entity\totara_recommendations_trending;
use block_totara_recommendations\repository\totara_recommendations_trending_repository;

/**
 * Populate the block with trending content
 *
 * @package block_totara_recommendations
 */
class trending_mode implements block_mode {
    /**
     * @var totara_recommendations_trending_repository
     */
    private $trending_repo;

    /**
     * Constructor
     */
    public function __construct() {
        $this->trending_repo = new totara_recommendations_trending_repository(totara_recommendations_trending::class);
    }

    /**
     * @return bool
     */
    public function hide_if_empty(): bool {
        return true;
    }

    /**
     * @return string
     * @throws \coding_exception
     */
    public function get_title(): string {
        return get_string('config:block_trending', 'block_totara_recommendations');
    }

    /**
     * @param int $count
     * @param int $user_id
     * @return array
     */
    public function get_items(int $count, int $user_id): array {
        return $this->trending_repo->get_cached_trending_content($count);
    }
}