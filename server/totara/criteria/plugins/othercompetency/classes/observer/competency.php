<?php
/*
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @author Marco Song <marco.song@totaralearning.com>

 * @package criteria_othercompetency
 */

namespace criteria_othercompetency\observer;

use hierarchy_competency\event\competency_deleted;
use totara_criteria\criterion_factory;
use totara_criteria\hook\criteria_validity_changed;
use totara_criteria\entity\criterion as criterion_entity;

class competency {

    public static function competency_deleted(competency_deleted $event) {
        // If the deleted competency is an item in a othercompetency criterion,
        // we need to mark all these criteria as invalid if they are currently considered as valid
        $criteria = criterion_entity::repository()
            ->from_item_ids('competency', $event->objectid)
            ->where('plugin_type', 'othercompetency')
            ->get();

        $affected_criteria = [];
        foreach ($criteria as $criterion_entity) {
            $criterion = criterion_factory::fetch_from_entity($criterion_entity);
            // Not checking anything here - this criterion refers to a deleted course
            // If already marked as invalid - nothing to do
            if ($criterion_entity->valid) {
                $criterion->set_valid(false);
                $criterion->save_valid();

                $affected_criteria[] = $criterion_entity->id;
            }
        }

        if (!empty($affected_criteria)) {
            $hook = new criteria_validity_changed($affected_criteria);
            $hook->execute();
        }
    }

}
