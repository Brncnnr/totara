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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\observers;

use hierarchy_organisation\event\organisation_deleted;
use hierarchy_position\event\position_deleted;
use mod_perform\entity\activity\subject_static_instance;
use totara_job\event\job_assignment_deleted;

class subject_static_instance_hierarchy {

    /**
     * React to a job assignment being deleted.
     *
     * @param job_assignment_deleted $event
     */
    public static function job_assignment_deleted(job_assignment_deleted $event): void {
        subject_static_instance::repository()
            ->where('job_assignment_id', $event->objectid)
            ->delete();

        subject_static_instance::repository()
            ->where('manager_job_assignment_id', $event->objectid)
            ->update(['manager_job_assignment_id' => null]);
    }

    /**
     * React to a position being deleted.
     *
     * @param position_deleted $event
     */
    public static function position_deleted(position_deleted $event): void {
        subject_static_instance::repository()
            ->where('position_id', $event->objectid)
            ->update(['position_id' => null]);
    }

    /**
     * React to an organisation being deleted.
     *
     * @param organisation_deleted $event
     */
    public static function organisation_deleted(organisation_deleted $event): void {
        subject_static_instance::repository()
            ->where('organisation_id', $event->objectid)
            ->update(['organisation_id' => null]);
    }

}
