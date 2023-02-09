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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\observers;

use core\event\base;
use core\event\user_deleted;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance;
use mod_perform\state\participant_instance\open as participant_instance_open;
use mod_perform\state\subject_instance\closed as subject_instance_closed;
use mod_perform\state\subject_instance\pending;
use totara_core\advanced_feature;
use totara_core\event\user_suspended;

class user {

    /**
     * @param user_deleted $event
     * @return void
     */
    public static function user_deleted(user_deleted $event): void {
        // Set all track user assignments to deleted to make sure
        // no new subject instance get created
        track_user_assignment::repository()
            ->where('subject_user_id', $event->objectid)
            ->update([
                'deleted' => 1,
                'updated_at' => time()
            ]);

        self::handle_user_suspended_or_deleted($event);
    }

    /**
     * @param user_suspended $event
     * @return void
     */
    public static function user_suspended(user_suspended $event): void {
        if (get_config(null, 'perform_close_suspended_user_instances')
            || get_config(null, 'perform_hide_suspended_users')
        ) {
            self::handle_user_suspended_or_deleted($event);
        }
    }

    private static function handle_user_suspended_or_deleted(base $event): void {
        $user_id = $event->objectid;

        // Close all subject instances
        /** @var subject_instance[] $subject_instances */
        $subject_instances = subject_instance_entity::repository()
            ->where('subject_user_id', $user_id)
            ->where('availability', '<>', subject_instance_closed::get_code())
            ->get()
            ->map_to(subject_instance::class);

        foreach ($subject_instances as $subject_instance) {
            if ($subject_instance->manual_state instanceof pending && $event instanceof user_deleted) {
                // For the user_deleted event we delete pending subject instances. This is not strictly necessary anymore
                // since we can close them now. But it's done here to preserve existing behaviour.
                subject_instance_entity::repository()->where('id', $subject_instance->id)->delete();
            } else {
                $subject_instance->manually_close(true);
            }
        }

        // Now close all participant instance not closed yet
        /** @var participant_instance[] $participant_instances */
        $participant_instances = participant_instance_entity::repository()
            ->where('participant_id', $user_id)
            ->where('participant_source', participant_source::INTERNAL)
            // Only open instance can be closed
            ->where('availability', participant_instance_open::get_code())
            ->get()
            ->map_to(participant_instance::class);

        foreach ($participant_instances as $participant_instance) {
            $participant_instance->manually_close();
        }
    }
}
