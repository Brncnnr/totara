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
 * @package mod_perform
 */

namespace mod_perform\observers;

use core\event\base;
use core\event\cohort_deleted;
use core\event\cohort_member_added;
use core\event\cohort_member_removed;
use hierarchy_organisation\event\organisation_deleted;
use hierarchy_position\event\position_deleted;
use mod_perform\expand_task;
use mod_perform\models\activity\track;
use mod_perform\models\activity\track_assignment;
use mod_perform\models\activity\track_assignment_type;
use mod_perform\user_groups\grouping;
use mod_perform\track_assignment_actions;
use totara_cohort\event\members_updated;
use totara_job\event\job_assignment_created;
use totara_job\event\job_assignment_deleted;
use totara_job\event\job_assignment_updated;

class track_assignment_user_groups {

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

        track_assignment_actions::create()->mark_for_expansion([grouping::cohort($event->objectid)]);
    }

    /**
     * When a cohort is deleted we need to remove it from track assignments.
     *
     * @param cohort_deleted $event
     */
    public static function cohort_deleted(cohort_deleted $event) {
        self::remove_track_assignments(grouping::cohort($event->objectid));
    }

    /**
     * When an organisation is deleted we need to remove it from track assignments.
     *
     * @param organisation_deleted $event
     */
    public static function organisation_deleted(organisation_deleted $event): void {
        self::remove_track_assignments(grouping::org($event->objectid));
    }

    /**
     * When an position is deleted we need to remove it from track assignments.
     *
     * @param position_deleted $event
     */
    public static function position_deleted(position_deleted $event) {
        self::remove_track_assignments(grouping::pos($event->objectid));
    }

    /**
     * Remove assignments for deleted user group
     *
     * @param grouping $grouping
     */
    private static function remove_track_assignments(grouping $grouping): void {
        $assignments = track_assignment::get_all_for_grouping($grouping);

        /** @var track_assignment $assignment */
        foreach ($assignments as $assignment) {
            $track = track::load_by_id($assignment->track_id);
            $track->remove_assignment(track_assignment_type::ADMIN, $grouping);
        }

        expand_task::create()->delete_orphaned_user_assignments();
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
            throw new \coding_exception('Invalid event, expected job_assignment_[created|updated|deleted]');
        }

        $to_mark = [];
        if ($event->other['oldpositionid'] != $event->other['newpositionid']) {
            if ($event->other['oldpositionid']) {
                $to_mark[] = grouping::POS($event->other['oldpositionid']);
            }
            if ($event->other['newpositionid']) {
                $to_mark[] = grouping::POS($event->other['newpositionid']);
            }
        }

        if ($event->other['oldorganisationid'] != $event->other['neworganisationid']) {
            if ($event->other['oldorganisationid']) {
                $to_mark[] = grouping::ORG($event->other['oldorganisationid']);
            }
            if ($event->other['neworganisationid']) {
                $to_mark[] = grouping::ORG($event->other['neworganisationid']);
            }
        }

        if (!empty($to_mark)) {
            track_assignment_actions::create()->mark_for_expansion($to_mark);
        }
    }

}