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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task\service;

use core\orm\collection;
use core\orm\lazy_collection;
use core\orm\query\builder;
use Iterator;
use mod_perform\dates\constants;
use mod_perform\dates\resolvers\anniversary_of;
use mod_perform\dates\resolvers\date_resolver;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\models\activity\track as track_model;

/**
 * This class is responsible for synchronising track schedule settings to the
 * track_user_assignment records.
 */
class track_schedule_sync {

    public function sync_all(): void {
        $this->sync_active_tracks(false);
    }

    public function sync_all_flagged(): void {
        $this->sync_active_tracks(true);
    }

    /**
     * @param bool $only_flagged
     */
    private function sync_active_tracks(bool $only_flagged): void {
        $tracks = $this->get_active_tracks($only_flagged);
        foreach ($tracks as $track) {
            $this->sync_track_schedule($track);
        }
    }

    /**
     * Synchronize schedule for one track.
     *
     * @param track $track
     */
    private function sync_track_schedule(track $track): void {
        // Get active track_user_assignments and update the start-/end-dates if necessary.
        // Note: If any changes are made to fetching the assignments here, please check if method
        // get_assigned_user_ids_for_track() has to be adjusted.
        $track_user_assignments = track_user_assignment::repository()
            ->filter_by_track_id($track->id)
            ->filter_by_active()
            ->get_lazy();

        // Bulk fetch all the start and end reference dates.
        // Get user and job ids separately so we can use memory-saving lazy loading for the track_user_assignments.
        $date_resolver = (new track_model($track))->get_date_resolver($this->get_assigned_user_and_job_ids_for_track($track));

        builder::get_db()->transaction(function () use ($track, $track_user_assignments, $date_resolver) {
            // Reset the flag.
            $track->schedule_needs_sync = false;
            $track->save();

            self::sync_user_assignment_schedules($date_resolver, $track_user_assignments, $track->schedule_use_anniversary);
        });
    }

    /**
     * Get all the user and job assignment ids for a track.
     *
     * @param track $track
     * @return collection
     */
    private function get_assigned_user_and_job_ids_for_track(track $track): collection {
        return builder::table(track_user_assignment::TABLE)
            ->select('subject_user_id')
            ->add_select('job_assignment_id')
            ->where('track_id', $track->id)
            ->where('deleted', false)
            ->get(true);
    }

    /**
     * @param date_resolver $original_date_resolver
     * @param Iterator|track_user_assignment[] $track_user_assignments
     * @param bool $use_anniversary
     */
    public static function sync_user_assignment_schedules(
        date_resolver $original_date_resolver,
        Iterator $track_user_assignments,
        bool $use_anniversary
    ): void {
        foreach ($track_user_assignments as $assignment) {
            if ($use_anniversary) {
                // It's important to that ordinarily a resolver is used on a set of users so that
                // the dates can be bulk fetched. However the anniversary_of decorator is created
                // once for each assignment, because we must set a different cut of date for
                // each one. Because it uses the original dates, the bulk fetch is still used.
                $date_resolver = new anniversary_of($original_date_resolver, $assignment->created_at);
            } else {
                $date_resolver = $original_date_resolver;
            }

            if ($date_resolver->get_resolver_base() === constants::DATE_RESOLVER_JOB_BASED) {
                $new_start_date = $date_resolver->get_start($assignment->job_assignment_id);
                $new_end_date = $date_resolver->get_end($assignment->job_assignment_id);
            } else {
                $new_start_date = $date_resolver->get_start($assignment->subject_user_id);
                $new_end_date = $date_resolver->get_end($assignment->subject_user_id);
            }

            if ((int)$assignment->period_start_date !== (int)$new_start_date
                || (int)$assignment->period_end_date !== (int)$new_end_date) {
                $assignment->period_start_date = $new_start_date;
                $assignment->period_end_date = $new_end_date;
                $assignment->save();
            }
        }
    }

    /**
     * Get all active tracks of active activities.
     *
     * @param bool $only_flagged  when true: only get tracks flagged up for schedule synchronisation
     * @return lazy_collection
     */
    private function get_active_tracks($only_flagged): lazy_collection {
        $repo = track::repository();
        if ($only_flagged) {
            $repo->filter_by_schedule_needs_sync();
        }
        return $repo->filter_by_active()
            ->filter_by_active_activity()
            ->get_lazy();
    }
}
