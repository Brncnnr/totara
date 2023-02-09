<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package criteria_childcompetency
 */

namespace criteria_childcompetency\observer;

use core\orm\query\table;
use criteria_childcompetency\childcompetency;
use criteria_childcompetency\items_processor;
use hierarchy_competency\event\competency_created;
use hierarchy_competency\event\competency_deleted;
use hierarchy_competency\event\competency_moved;
use totara_criteria\entity\criteria_item as item_entity;
use totara_criteria\entity\criteria_metadata as metadata_entity;
use totara_criteria\criterion;
use totara_hierarchy\entity\competency as competency_entity;

class competency {

    public static function competency_created(competency_created $event) {
        // Update the items for the new competency's parent
        $competency = new competency_entity($event->objectid);
        if (!empty($competency->parentid)) {
            items_processor::update_items($competency->parentid);
        }
    }

    public static function competency_moved(competency_moved $event) {
        // The event doesn't provide information on the previous parent.
        // We therefore need to find it through the existing items
        $previous_parent_id = static::get_parent_competency_of_item($event->objectid);
        if (!is_null($previous_parent_id)) {
            items_processor::update_items($previous_parent_id);
        }

        // If new parent is not top, update it's items as well
        $competency = new competency_entity($event->objectid);
        if (!empty($competency->parentid)) {
            items_processor::update_items($competency->parentid);
        }
    }

    public static function competency_deleted(competency_deleted $event) {
        // If the deleted competency has a parent, we need to update its parent
        // competency's items
        $previous_parent_id = static::get_parent_competency_of_item($event->objectid);
        if (!is_null($previous_parent_id)) {
            items_processor::update_items($previous_parent_id);
        }
    }

    /**
     * Retrieve the competency_id from metadata for the specific 'competency' item's parent
     * @param  int $child_competency_id Competency id to search for
     * @return int|null Id of parent competency
     */
    private static function get_parent_competency_of_item(int $child_competency_id) {
        $item_type = (new childcompetency())->get_items_type();

        $parent_competency_id = metadata_entity::repository()
            ->join((new table(item_entity::TABLE))->as('item'), 'criterion_id', 'criterion_id')
            ->where('item.item_type', $item_type)
            ->where('item.item_id', $child_competency_id)
            ->where('metakey', criterion::METADATA_COMPETENCY_KEY)
            ->get()
            ->first();

        return !is_null($parent_competency_id) ? $parent_competency_id ->metavalue : null;
    }

}
