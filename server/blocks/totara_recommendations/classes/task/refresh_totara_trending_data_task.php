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

namespace block_totara_recommendations\task;

use \core\task\scheduled_task;
use block_totara_recommendations\repository\totara_recommendations_trending_repository;

/**
 * This scheduled task will populate/repopulate the list of trending content.
 */
class refresh_totara_trending_data_task extends scheduled_task {
    /**
     *@inheritDoc
     */
    public function get_name() {
        return get_string('refresh_totara_trending_data_task', 'block_totara_recommendations');
    }

    /**
     *@inheritDoc
     */
    public function execute() {
        global $CFG;

        // Get a fresh list of trending content.
        $totara_trending_repository = new totara_recommendations_trending_repository('block_totara_recommendations\entity\totara_recommendations_trending');

        $components = [
            'totara_playlist',
            'engage_article',
            'engage_survey',
        ];
        $records = $totara_trending_repository->get_trending_components($components, $CFG->block_totara_recommendations_dayctr);
        $trending_items = $totara_trending_repository->from_records($records);

        // Delete existing cache records.
        $totara_trending_repository->truncate_totara_recommendations_trending();

        // Create new cache records.
        $totara_trending_repository->store_trending_items($trending_items);
    }
}
