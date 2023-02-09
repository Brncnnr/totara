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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\loader\member;

use container_workspace\entity\workspace;
use container_workspace\member\member;
use container_workspace\member\status;
use container_workspace\query\member\query;
use container_workspace\query\member\sort;
use core\entity\enrol;
use core\entity\user;
use core\entity\user_enrolment;
use core\entity\user_repository;
use core\orm\entity\repository;
use core\pagination\offset_cursor_paginator as core_offset_cursor_paginator;
use core\orm\query\builder;
use core\orm\query\order;
use core\user_orm_helper;
use core\entity\cohort_repository;

/**
 * Loader for members within a workspace
 */
final class loader {
    /**
     * Preventing this class from being constructed.
     * member_loader constructor.
     */
    private function __construct() {
    }

    /**
     * @param query $query
     * @return core_offset_cursor_paginator
     */
    public static function get_members(query $query): core_offset_cursor_paginator {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $workspace_id = $query->get_workspace_id();
        $max = PHP_INT_MAX;
        $members_repository = user::repository()->as('u')
            ->join([user_enrolment::TABLE, 'ue'], 'ue.userid', 'u.id')
            ->join([enrol::TABLE, 'e'], 'ue.enrolid', 'e.id')
            // By default we always include the user owner on top.
            ->left_join(
                [workspace::TABLE, 'w'],
                function (builder $join) {
                    $join->where_field('u.id', 'w.user_id')
                        ->where_field('w.course_id', 'e.courseid');
                }
            )
            ->where('e.courseid', $workspace_id)
            ->where('ue.status', ENROL_USER_ACTIVE)
            ->where('u.deleted', 0)
            ->where('u.confirmed', 1)
            ->select_raw("u.id, COALESCE(w.user_id, $max) as user_id, min(ue.timemodified) as timemodified")
            ->group_by([
                'u.id',
                'w.user_id',
                'u.firstnamephonetic',
                'u.lastnamephonetic',
                'u.middlename',
                'u.alternatename',
                'u.firstname',
                'u.lastname',
                'u.email',
                'u.picture',
                'u.imagealt'
            ]);

        // Select user fields
        $user_name_fields = get_all_user_name_fields(false, 'u');
        $members_repository->add_select($user_name_fields)
            ->add_select([
                'u.email',
                'u.picture',
                'u.imagealt'
            ]);

        // Filter by status
        $status = $query->get_member_status();
        if (!is_null($status)) {
            if (status::is_active($status)) {
                $members_repository->where('ue.status', ENROL_USER_ACTIVE);
            } else if (status::is_suspended($status)) {
                $members_repository->where('ue.status', ENROL_USER_SUSPENDED);
            }
        }

        // Filter by Search
        $search_term = $query->get_search_term();
        if (!empty($search_term)) {
            user_orm_helper::filter_by_fullname($members_repository, $search_term, 'u');
        }

        // Filter by tenant
        if (!$query->include_tenant_users() && !$query->is_workspace_in_tenant()) {
            // Only excluding the tenant users when the query is say so, and the workspace is
            // in the system rather than in a tenant.
            $members_repository->where_null('u.tenantid');
        }

        $subquery_builder = (new builder())->from($members_repository->get_builder())
            ->as('um')
            ->results_as_objects()
            ->map_to(function ($record) use ($workspace_id) {
                unset($record->ue_row_number);
                $record->workspace_id = $workspace_id;
                return new user($record, false, true);
            });

        $users_repository = (new user_repository(user::class, $subquery_builder))
            ->order_by('user_id', order::DIRECTION_ASC)
            ->with([
                'user_enrolments' => function (repository $repository) use ($workspace_id) {
                    $repository->as('ue')
                    ->join([enrol::TABLE, 'e'], 'ue.enrolid', 'e.id')
                    ->where('e.courseid', $workspace_id)
                    ->with([
                        'enrol_instance' => function (repository $repository) {
                            return $repository->where('enrol', 'cohort')
                                ->where('status', ENROL_INSTANCE_ENABLED)
                                ->with([
                                    'cohort' => function (cohort_repository $repository) {
                                        $repository->preload_members_count();
                                    },
                                ]);
                        }
                    ]);
                }
            ]);

        // Apply sort
        $sort = $query->get_sort();
        if (sort::is_name($sort)) {
            user_orm_helper::order_by_fullname($users_repository, 'um');
        } else if (sort::is_recent_join($sort)) {
            $users_repository->order_by('timemodified', order::DIRECTION_DESC);
        }

        $cursor = $query->get_cursor();
        return new paginator($users_repository, $cursor);
    }

    /**
     * If null is returned, meaning that the user had not yet been a member of a workspace.
     *
     * @param int $user_id
     * @param int $workspace_id
     *
     * @return member|null
     */
    public static function get_for_user(int $user_id, int $workspace_id): ?member {
        try {
            return member::from_user($user_id, $workspace_id);
        } catch (\dml_missing_record_exception $e) {
            return null;
        }
    }

    /**
     * Count the number of actively enrolled members in this workspace
     *
     * @param int $workspace_id
     * @return int
     */
    public static function count_members(int $workspace_id): int {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $builder = builder::table('user_enrolments', 'ue');

        $builder->join(['enrol', 'e'], 'ue.enrolid', 'e.id');
        $builder->join(['user', 'u'], 'ue.userid', 'u.id');

        $builder->where('e.courseid', $workspace_id);
        $builder->where('ue.status', ENROL_USER_ACTIVE);

        $builder->select([
            "ue.enrolid",
        ]);

        return $builder->count();
    }

    /**
     * @param array $row
     * @return member
     *
     * @internal
     * @deprecated since Totara 16.0
     */
    public static function create_member(array $row): member {
        debugging('method is deprecated, and no longer in use', DEBUG_DEVELOPER);
        $user_fields = get_all_user_name_fields(false, 'u', 'user_');
        $user_fields['email'] = 'user_email';
        $user_fields['id'] = 'user_id';
        $user_fields['picture'] = 'user_picture';
        $user_fields['imagealt'] = 'user_image_alt';

        $enrolment_record = array_filter(
            $row,
            function (string $row_key) use ($user_fields): bool {
                // As long as the keys does not exists in user fields.
                return !in_array($row_key, $user_fields);
            },
            ARRAY_FILTER_USE_KEY
        );

        $member = member::from_record((object) $enrolment_record);

        $user_record = [];
        foreach ($user_fields as $field => $sql_field) {
            if (!array_key_exists($sql_field, $row)) {
                debugging("The array record does not have field '{$sql_field}'", DEBUG_DEVELOPER);
                continue;
            }

            $user_record[$field] = $row[$sql_field];
        }

        $member->set_user_record((object) $user_record);
        return $member;
    }
}