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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_competency
 */
namespace pathway_criteria_group\observers;

use pathway_criteria_group\criteria_group;
use totara_hierarchy\event\scale_value_deleted;

/**
 * Class scale_value_observer
 *
 * @package pathway_criteria_group\observers
 */
class scale_value_observer {

    /**
     * Observer to delete pathways when a scale value is deleted.
     *
     * @param scale_value_deleted $event Scale value delete Event.
     *
     * @return void
     */
    public static function delete_pathways(scale_value_deleted $event): void {
        $data = $event->get_data();
        $scale_value_id = $data['objectid'];
        criteria_group::delete_pathways_with_scale_value_id($scale_value_id);
    }
}
