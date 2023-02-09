<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\observers;

use core\event\base;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\helpers\participation_sync_settings_helper;
use mod_perform\state\subject_instance\complete;
use mod_perform\state\subject_instance\open;
use totara_job\event\job_assignment_created;
use totara_job\event\job_assignment_deleted;
use totara_job\event\job_assignment_updated;
use totara_job\entity\job_assignment as job_assignment_entity;

class participant_instance_sync {

    /**
     * When the 'job assignment created' event is emitted, update the sync flag the in subject_instance table.
     *
     * @param job_assignment_created $event
     */
    public static function job_assignment_created(job_assignment_created $event): void {
        $instance_ids = self::get_instance_ids(
            self::get_user_ids_when_assignment_created($event),
            true,
            false
        );

        // Set sync flags
        self::set_sync_flag($instance_ids);
    }

    /**
     * When the 'job assignment updated' event is emitted, update the sync flag in the subject_instance table.
     *
     * @param job_assignment_updated $event
     */
    public static function job_assignment_updated(job_assignment_updated $event): void {
        $instance_ids = self::get_instance_ids(
            self::get_user_ids_when_assignment_updated($event),
            true,
            true
        );

        // Set sync flags
        self::set_sync_flag($instance_ids);
    }

    /**
     * When the 'job assignment deleted' event is emitted, update the sync flag in the subject_instance table.
     *
     * @param job_assignment_deleted $event
     */
    public static function job_assignment_deleted(job_assignment_deleted $event): void {
        $instance_ids = self::get_instance_ids(
            self::get_user_ids_when_assignment_deleted($event),
            false,
            true
        );

        // Set sync flags
        self::set_sync_flag($instance_ids);
    }

    /**
     * Get ids for potentially affected users when a new job assignment is created.
     *
     * @param base $event
     * @return array
     */
    private static function get_user_ids_when_assignment_created(base $event): array {
        $potentially_affected_user_ids = [];

        // Adding an appraiser can mean a relationship change only for the JA owner.
        if (!empty($event->other['newappraiserid'])) {
            $potentially_affected_user_ids[] = $event->relateduserid;
        }

        /*
         * Adding a manager can mean a relationship change for
         *  - the JA owner (a new manager enters the relationship)
         *  - the manager (a new direct report enters the relationship)
         *
         * Anyone's "manager's manager" relationship cannot change when a new JA is added, it can only be changed by
         * modifying or deleting an existing job assignment (the link has to be through the same job assignment).
         */
        if (!empty($event->other['newmanagerjaid'])) {
            $potentially_affected_user_ids[] = $event->relateduserid;
            $manager_id = self::get_job_assignment_owner_id($event->other['newmanagerjaid']);
            if ($manager_id) {
                $potentially_affected_user_ids[] = $manager_id;
            }
        }
        if (!empty($event->other['newtempmanagerjaid'])) {
            $potentially_affected_user_ids[] = $event->relateduserid;
            $manager_id = self::get_job_assignment_owner_id($event->other['newtempmanagerjaid']);
            if ($manager_id) {
                $potentially_affected_user_ids[] = $manager_id;
            }
        }

        return array_unique($potentially_affected_user_ids);
    }

    /**
     * Get ids for potentially affected users when a job assignment is updated.
     *
     * @param base $event
     * @return array
     */
    private static function get_user_ids_when_assignment_updated(base $event): array {
        $potentially_affected_user_ids = [];

        // Adding, removing or replacing an appraiser can mean a relationship change only for the JA owner.
        if ($event->other['newappraiserid'] !== $event->other['oldappraiserid']) {
            if (!empty($event->other['newappraiserid'])) {
                $potentially_affected_user_ids[] = $event->relateduserid;
            }
            if (!empty($event->other['oldappraiserid'])) {
                $potentially_affected_user_ids[] = $event->relateduserid;
            }
        }

        return array_unique(
            array_merge($potentially_affected_user_ids, self::get_affected_user_ids_from_manager_change($event))
        );
    }

    /**
     * Get ids for potentially affected users when a job assignment is deleted.
     *
     * @param base $event
     * @return array
     */
    private static function get_user_ids_when_assignment_deleted(base $event): array {
        $potentially_affected_user_ids = [];

        if (!empty($event->other['oldappraiserid'])) {
            $potentially_affected_user_ids[] = $event->relateduserid;
        }

        return array_unique(
            array_merge($potentially_affected_user_ids, self::get_affected_user_ids_from_manager_change($event))
        );
    }

    /**
     * Adding (to an existing job assignment), removing or replacing a manager can mean a relationship change for
     *  - the JA owner (a manager enters and/or leaves the relationship)
     *  - the JA owner's direct reports (a new manager's manager enters and/or leaves the relationship)
     *  - the manager (a new direct report enters and/or leaves the relationship)
     *
     * @param base $event
     * @return array
     */
    private static function get_affected_user_ids_from_manager_change(base $event): array {
        $affected_user_ids = [];
        $manager_ja_id_pairs = [];
        if ($event->other['newmanagerjaid'] !== $event->other['oldmanagerjaid']) {
            $manager_ja_id_pairs[] = [
                'old' => $event->other['oldmanagerjaid'],
                'new' => $event->other['newmanagerjaid'],
            ];
        }
        if ($event->other['newtempmanagerjaid'] !== $event->other['oldtempmanagerjaid']) {
            $manager_ja_id_pairs[] = [
                'old' => $event->other['oldtempmanagerjaid'],
                'new' => $event->other['newtempmanagerjaid'],
            ];
        }
        foreach ($manager_ja_id_pairs as $manager_ja_ids) {
            if (!empty($manager_ja_ids['new'])) {
                $affected_user_ids[] = [$event->relateduserid];
                $affected_user_ids[] = self::get_direct_report_user_ids($event->objectid);
                $manager_id = self::get_job_assignment_owner_id($manager_ja_ids['new']);
                if ($manager_id) {
                    $affected_user_ids[] = [$manager_id];
                }
            }

            if (!empty($manager_ja_ids['old'])) {
                $affected_user_ids[] = [$event->relateduserid];
                $affected_user_ids[] = self::get_direct_report_user_ids($event->objectid);
                $manager_id = self::get_job_assignment_owner_id($manager_ja_ids['old']);
                if ($manager_id) {
                    $affected_user_ids[] = [$manager_id];
                }
            }
        }
        return array_merge([], ...$affected_user_ids);
    }

    /**
     * Get subject instance ids for the given user ids.
     *
     * Will only get ids for subject instances that are open and not complete.
     *
     * @param array $user_ids
     * @param bool $is_creation_relevant
     * @param bool $is_closure_relevant
     * @return array
     */
    private static function get_instance_ids(array $user_ids, bool $is_creation_relevant, bool $is_closure_relevant): array {
        if (empty($user_ids)) {
            return [];
        }

        $subject_instances = subject_instance::repository()
            ->with('track')
            ->where_in('subject_user_id', $user_ids)
            ->where('availability', open::get_code())
            ->where('progress', '<>', complete::get_code())
            ->get();

        if ($subject_instances->count() < 1) {
            return [];
        }

        $helper = participation_sync_settings_helper::create_from_subject_instances($subject_instances);

        // Remove subject instances that are configured not to be synchronised from the collection.
        $subject_instances = $subject_instances->filter(
            function (subject_instance $subject_instance) use ($helper, $is_creation_relevant, $is_closure_relevant) {
                $activity_id = $subject_instance->track->activity_id;
                return ($is_creation_relevant && $helper->should_instance_creation_be_synced($activity_id))
                    || ($is_closure_relevant && $helper->should_instance_closure_be_synced($activity_id));
            }
        );

        return $subject_instances->pluck('id');
    }

    /**
     * @param array $ids
     * @return void
     */
    private static function set_sync_flag(array $ids): void {
        if (empty($ids)) {
            return;
        }
        subject_instance::repository()
            ->where('id', $ids)
            ->update(['needs_sync' => subject_instance::NEED_SYNC_ENABLE]);
    }

    /**
     * @param int $job_assignment_id
     * @return int|null
     */
    private static function get_job_assignment_owner_id(int $job_assignment_id): ?int {
        $job_assignment = job_assignment_entity::repository()->find($job_assignment_id);
        return $job_assignment->userid ?? null;
    }

    /**
     * @param int $job_assignment_id
     * @return array
     */
    private static function get_direct_report_user_ids(int $job_assignment_id): array {
        return job_assignment_entity::repository()
            ->where('managerjaid', $job_assignment_id)
            ->or_where('tempmanagerjaid', $job_assignment_id)
            ->get()
            ->pluck('userid');
    }
}