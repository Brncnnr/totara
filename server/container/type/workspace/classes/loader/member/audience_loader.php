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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\loader\member;

use container_workspace\workspace;
use core\entity\cohort_member;
use core\entity\user_enrolment;
use core\entity\user_repository;
use core\orm\query\builder;
use core\orm\query\field;
use core\tenant_orm_helper;

/**
 * Loader for members within a workspace
 * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
 */
final class audience_loader {

    /**
     * Preventing this class from being constructed.
     * member_loader constructor.
     */
    private function __construct() {
    }

    /**
     * Returns users who are members of the given audiences but not yet enrolled in the workspace
     *
     * @param workspace $workspace
     * @param array $audience_ids
     *
     * @return int[] the user ids of unenrolled members.
     * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
     */
    public static function get_bulk_members_to_add(workspace $workspace, array $audience_ids): array {
        debugging("audience_loader::get_bulk_members_to_add has been deprecated.", DEBUG_DEVELOPER);
        return self::unenrolled_member_query($workspace, $audience_ids)
            ->get()
            ->pluck('userid');
    }

    /**
     * Returns number of users who are members of the given audiences but not yet enrolled in the workspace
     *
     * @param workspace $workspace
     * @param array $audience_ids
     * @return int
     * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
     */
    public static function get_bulk_members_to_add_count(workspace $workspace, array $audience_ids): int {
        debugging("audience_loader::get_bulk_members_to_add_count has been deprecated.", DEBUG_DEVELOPER);
        return self::unenrolled_member_query($workspace, $audience_ids)->count();
    }

    /**
     * Formulates the ORM builder to retrieve the cohort members who are not
     * members in a workspace.
     *
     * @param workspace $workspace
     * @param array $audience_ids
     *
     * @return builder the builder.
     */
    private static function unenrolled_member_query(workspace $workspace, array $audience_ids): builder {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        return builder::table(cohort_member::TABLE)
            ->as('cm')
            ->select_raw('DISTINCT cm.userid')
            ->where_not_exists(
                builder::table(user_enrolment::TABLE)
                    ->as('ue')
                    ->join(['enrol', 'e'], 'enrolid', 'id')
                    ->where('status', ENROL_USER_ACTIVE)
                    ->where('e.courseid', $workspace->id)
                    ->where_field('userid', 'cm.userid')
            )
            ->when(true, function (builder $builder) use ($workspace) {
                tenant_orm_helper::restrict_users(
                    $builder,
                    'cm.userid',
                    $workspace->get_context()
                );
            })
            ->where('cohortid', $audience_ids);
    }
}