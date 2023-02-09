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
 * @package block_totara_recommendations
 */

namespace block_totara_recommendations\observer;

use block_totara_recommendations\entity\totara_recommendations_trending as trending;
use block_totara_recommendations\repository\totara_recommendations_trending_repository;
use core_ml\event\interaction_event;

/**
 * For generating the interaction record
 */
final class trending_observer {
    /**
     * Preventing this class from being constructed
     * interaction_observer constructor.
     */
    private function __construct() {
    }

    /**
     * Delete the trending cached records for the provided component
     *
     * @param interaction_event $event
     */
    public static function watch_delete(interaction_event $event): void {
        $component = $event->get_component();
        $item_id = $event->get_item_id();

        /** @var totara_recommendations_trending_repository $repo */
        $repo = trending::repository();
        $repo->delete_for_component($component, $item_id);
    }
}