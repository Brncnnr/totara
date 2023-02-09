<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\observer;

use core\event\cohort_updated;
use hierarchy_organisation\event\organisation_updated;
use hierarchy_position\event\position_updated;
use mod_approval\entity\assignment\assignment;
use mod_approval\model\assignment\assignment_type;

/**
 * Observer class for changes in assignment used to update module name & idnumber.
 */
class assignment_name_observer {

    /**
     * Update organisation assignment module name & idnumber on update event
     *
     * @param organisation_updated $event
     * @return void
     */
    public static function update_organisation_names(organisation_updated $event): void {
        $organisation = assignment_type\organisation::instance($event->objectid);

        assignment::repository()
            ->where('assignment_type', $organisation::get_code())
            ->where('assignment_identifier', $event->objectid)
            ->update([
                'name' => $organisation->get_name(),
            ]);
    }

    /**
     * Update position assignment module name & idnumber on update event
     *
     * @param position_updated $event
     * @return void
     */
    public static function update_position_names(position_updated $event): void {
        $position = assignment_type\position::instance($event->objectid);

        assignment::repository()
            ->where('assignment_type', $position::get_code())
            ->where('assignment_identifier', $event->objectid)
            ->update([
                'name' => $position->get_name(),
            ]);
    }

    /**
     * Update cohort assignment module name & idnumber on update event
     *
     * @param cohort_updated $event
     * @return void
     */
    public static function update_cohort_names(cohort_updated $event): void {
        $cohort = assignment_type\cohort::instance($event->objectid);

        assignment::repository()
            ->where('assignment_type', $cohort::get_code())
            ->where('assignment_identifier', $event->objectid)
            ->update([
                'name' => $cohort->get_name(),
            ]);
    }
}