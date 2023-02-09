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
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\repository;

use core\orm\entity\repository;
use totara_reportedcontent\entity\review as review_entity;
use totara_reportedcontent\review;

/**
 * Repository for entity comment
 */
final class review_repository extends repository {
    /**
     * Helper method to check to see if the user has already reported this item.
     * Will return the ID of the existing report, otherwise a null.
     *
     * @param string $component
     * @param string $area
     * @param int $item_id
     * @param int $context_id
     * @param int $complainer_id
     * @return int|null
     */
    public function get_existing_review_id(string $component, string $area, int $item_id, int $context_id, int $complainer_id): ?int {
        global $DB;

        $review_id = $DB->get_field(review_entity::TABLE, 'id', [
            'component' => $component,
            'area' => $area,
            'item_id' => $item_id,
            'context_id' => $context_id,
            'complainer_id' => $complainer_id,
            'status' => review::DECISION_PENDING,
        ]);

        return $review_id !== false ? $review_id : null;
    }
}