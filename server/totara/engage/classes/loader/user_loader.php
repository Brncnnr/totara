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
 * @package totara_engage
 */
namespace totara_engage\loader;

use context;
use core\entity\user;
use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use core\user_orm_helper;
use totara_engage\query\user_query;

/**
 * A generic loader to load the users with multi-tenancy compatible.
 */
class user_loader {
    /**
     * Preventing this class from construction.
     * user_loader constructor.
     */
    private function __construct() {
    }

    /**
     * @param user_query $query
     * @return offset_cursor_paginator
     */
    public static function get_users(user_query $query): offset_cursor_paginator {
        global $CFG, $DB;

        $builder = builder::table(user::TABLE, 'u');
        $builder->map_to(user::class);
        $builder->select('u.*');

        if ($CFG->tenantsenabled) {
            $context = context::instance_by_id($query->get_context_id());
            $include_system_user = $query->is_including_system_user();
            $include_participant = $query->is_including_participant();

            switch (true) {
                case ($context->tenantid && !empty($CFG->tenantsisolated)):
                    // For this case, $include_system_user is dis-regard.
                    $builder->when(
                        $include_participant,
                        function (builder $inner_builder) use ($DB, $context): void {
                            $cohort_id = $DB->get_field(
                                'tenant',
                                'cohortid',
                                ['id' => $context->tenantid],
                                MUST_EXIST
                            );

                            $inner_builder->join(
                                ['cohort_members', 'cm'],
                                function (builder $join) use ($cohort_id): void {
                                    $join->where_field('u.id', 'cm.userid');
                                    $join->where('cm.cohortid', $cohort_id);
                                }
                            );
                        },
                        function (builder $inner_builder) use ($context): void {
                            $inner_builder->where('u.tenantid', $context->tenantid);
                        }
                    );

                    break;

                case ($context->tenantid && empty($CFG->tenantsisolated)):
                    if (!$include_participant && !$include_system_user) {
                        $builder->where("u.tenantid", $context->tenantid);
                        break;
                    }

                    if ($include_participant && $include_system_user) {
                        $builder->where_raw(
                            "(u.tenantid = :tenant_id OR u.tenantid IS NULL)",
                            ['tenant_id' => $context->tenantid]
                        );

                        break;
                    }

                    $cohort_id = $DB->get_field(
                        'tenant',
                        'cohortid',
                        ['id' => $context->tenantid],
                        MUST_EXIST
                    );

                    if (!$include_participant && $include_system_user) {
                        // We are excluding all the participant but including the system user
                        // and the user member(s) only.

                        $builder->left_join(
                            ['cohort_members', 'cm'],
                            function (builder $join) use ($cohort_id): void {
                                $join->where_field('u.id', 'cm.userid');
                                $join->where('cm.cohortid', $cohort_id);
                            }
                        );

                        // This is to capture all those system user.
                        $builder->where_raw(
                            "((cm.userid IS NULL AND u.tenantid IS NULL) OR u.tenantid = :tenant_id)",
                            ['tenant_id' => $context->tenantid]
                        );

                        break;
                    }

                    if ($include_participant && !$include_system_user) {
                        $builder->join(
                            ['cohort_members', 'cm'],
                            function (builder $join) use ($cohort_id): void {
                                $join->where_field('u.id', 'cm.userid');
                                $join->where('cm.cohortid', $cohort_id);
                            }
                        );
                    }

                    break;

                case !empty($CFG->tenantsisolated):
                    $builder->where_null('u.tenantid');
                    break;
            }
        }

        $builder->where('u.deleted', $query->is_including_deleted());
        $builder->where('u.suspended', $query->is_including_suspended());
        $builder->where('u.confirmed', $query->is_including_confirmed());

        $exclude_users = $query->get_exclude_users();
        $builder->when(
            !empty($exclude_users),
            function (builder $inner_builder) use ($exclude_users): void {
                $inner_builder->where_not_in('u.id', $exclude_users);
            }
        );

        $search_term = $query->get_search_term();
        if (!empty($search_term)) {
            user_orm_helper::filter_by_fullname($builder, $search_term, 'u');
        }

        user_orm_helper::order_by_fullname($builder, 'u');

        $cursor = $query->get_cursor();
        return new offset_cursor_paginator($builder, $cursor);
    }
}