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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\entity\repository;
use core\orm\query\builder;
use mod_perform\models\activity\participant_source;
use totara_job\entity\job_assignment;

class subject_static_instance_repository extends repository {

    /**
     * Should one user be able to see the other users profile details in the context of mod perform
     * due relevant static instance records.
     *
     * Will return true if the viewing user has static instance records with the target user as
     * the manager or appraiser.
     *
     * Most of the time this sort of user access check will be covered by either the participant
     * instance check (are viewing user and target user participants in the same activity),
     * or the shared relationship check. This is to cover cases where the manager or appraiser
     * are not participants in an activity.
     *
     * @see participant_instance_repository::user_can_view_other_users_profile
     * @see core_user_access_controller::allow_view_profile_field
     *
     * @param int $viewing_user_id The user requesting to view the target user
     * @param int $target_user_id The user who's
     * @return bool
     */
    public static function user_can_view_other_users_profile(int $viewing_user_id, int $target_user_id): bool {
        $participant_instance_exists_builder = builder::table(participant_instance::TABLE)
            ->where_field('subject_instance_id', 'static_instance.subject_instance_id')
            ->where('participant_source', participant_source::INTERNAL)
            ->where('participant_id', $viewing_user_id);

        return builder::table(subject_static_instance::TABLE, 'static_instance')
            ->left_join([job_assignment::TABLE, 'manager_ja'], 'static_instance.manager_job_assignment_id', 'manager_ja.id')
            ->where_exists($participant_instance_exists_builder)
            ->where(function (builder $builder) use ($target_user_id): void {
                $builder->where('manager_ja.userid', $target_user_id)
                    ->or_where('static_instance.appraiser_id', $target_user_id);
            })
            ->exists();
    }

}