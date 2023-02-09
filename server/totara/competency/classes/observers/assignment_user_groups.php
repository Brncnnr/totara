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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\observers;

use core\event\base;
use core\event\cohort_member_added;
use core\event\cohort_member_removed;
use totara_cohort\event\members_updated;
use totara_competency\models\assignment_actions;
use totara_competency\user_groups;
use totara_job\event\job_assignment_created;
use totara_job\event\job_assignment_deleted;
use totara_job\event\job_assignment_updated;

class assignment_user_groups {

    /**
     * When dynamic audience members changes
     *
     * @param base|cohort_member_added|cohort_member_removed|members_updated $event
     */
    public static function cohort_updated(base $event) {
        $valid_events = [
            members_updated::class,
            cohort_member_added::class,
            cohort_member_removed::class
        ];

        if (!in_array(get_class($event), $valid_events)) {
            throw new \coding_exception('Invalid event, expected members_updated, cohort_member_added or cohort_member_removed');
        }

        assignment_actions::create()->mark_for_expansion([[user_groups::COHORT, $event->objectid]]);
    }

    /**
     * When a job assignment changes
     *
     * @param base|job_assignment_created|job_assignment_updated|job_assignment_deleted $event
     */
    public static function job_assignment_updated(base $event) {
        $valid_events = [
            job_assignment_created::class,
            job_assignment_updated::class,
            job_assignment_deleted::class
        ];

        if (!in_array(get_class($event), $valid_events)) {
            throw new \coding_exception('Invalid event, expected job_assignment_created or job_assignment_updated');
        }

        $to_mark = [];
        if ($event->other['oldpositionid'] != $event->other['newpositionid']) {
            if ($event->other['oldpositionid']) {
                $to_mark[] = [user_groups::POSITION, $event->other['oldpositionid']];
            }
            if ($event->other['newpositionid']) {
                $to_mark[] = [user_groups::POSITION, $event->other['newpositionid']];
            }
        }

        if ($event->other['oldorganisationid'] != $event->other['neworganisationid']) {
            if ($event->other['oldorganisationid']) {
                $to_mark[] = [user_groups::ORGANISATION, $event->other['oldorganisationid']];
            }
            if ($event->other['neworganisationid']) {
                $to_mark[] = [user_groups::ORGANISATION, $event->other['neworganisationid']];
            }
        }

        if (!empty($to_mark)) {
            assignment_actions::create()->mark_for_expansion($to_mark);
        }
    }


}