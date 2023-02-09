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

use container_workspace\member\member_request;
use container_workspace\query\member\member_request_query;
use container_workspace\query\member\member_request_status;
use container_workspace\workspace;
use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use container_workspace\entity\workspace_member_request;
use core_container\factory;

/**
 * For loading the member request.
 */
final class member_request_loader {
    /**
     * member_request_loader constructor.
     * Preventing construction on this class.
     */
    private function __construct() {
    }

    /**
     * @param member_request_query $query
     * @return offset_cursor_paginator
     */
    public static function get_member_requests(member_request_query $query): offset_cursor_paginator {
        global $CFG, $DB;

        $workspace_id = $query->get_workspace_id();
        $workspace = factory::from_id($workspace_id);

        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception(
                "Expecting an instance of container_workspace from id '{$workspace_id}'"
            );
        }

        $builder = builder::table(workspace_member_request::TABLE, 'wmr');
        $builder->join(
            ['user', 'u'],
            function (builder $join): void {
                $join->where_field('u.id', 'wmr.user_id');
                $join->where('u.deleted', 0);
                $join->where('u.suspended', 0);
            }
        );

        $builder->where('wmr.course_id', $workspace_id);

        $status = $query->get_member_request_status();
        if (member_request_status::is_pending($status)) {
            $builder->where_null('wmr.time_accepted');
            $builder->where_null('wmr.time_declined');
            $builder->where_null('wmr.time_cancelled');
        } else if (member_request_status::is_cancelled($status)) {
            $builder->where_not_null('wmr.time_cancelled');
            $builder->where_null('wmr.time_declined');
            $builder->where_null('wmr.time_accepted');
        } else if (member_request_status::is_declined($status)) {
            $builder->where_not_null('wmr.time_declined');
            $builder->where_null('wmr.time_accepted');
            $builder->where_null('wmr.time_cancelled');
        } else if (member_request_status::is_accepted($status)) {
            $builder->where_not_null('wmr.time_accepted');
            $builder->where_null('wmr.time_declined');
            $builder->where_null('wmr.time_cancelled');
        }

        $builder->select([
            'wmr.id AS member_request_id',
            'wmr.course_id AS member_request_course_id',
            'wmr.user_id AS member_request_user_id',
            'wmr.time_created AS member_request_time_created',
            'wmr.time_accepted AS member_request_time_accepted',
            'wmr.time_declined AS member_request_time_declined',
            'wmr.time_cancelled AS member_request_time_cancelled',
            'wmr.request_content AS member_request_request_content'
        ]);

        $user_name_fields = get_all_user_name_fields(true, 'u', null, 'user_');
        $builder->add_select_raw($user_name_fields);
        $builder->add_select([
            'u.email AS user_email',
            'u.picture AS user_picture',
            'u.imagealt AS user_image_alt',
            'u.id AS user_id'
        ]);

        $builder->results_as_arrays();
        $builder->map_to([static::class, 'create_member_request']);

        if ($CFG->tenantsenabled) {
            // Multi tenancy is turned on. Might have to check if the workspace is in tenant or not.
            $context = $workspace->get_context();
            $tenant_id = $context->tenantid;

            if (null !== $tenant_id) {
                $cohort_id = $DB->get_field('tenant', 'cohortid', ['id' => $tenant_id]);
                $builder->join(['cohort_members', 'cm'], 'cm.userid', 'wmr.user_id');
                $builder->where('cm.cohortid', $cohort_id);
            } else if ($CFG->tenantsisolated) {
                // When workspace is not in any tenant and isolation mode is on.
                $builder->where_null('u.tenantid');
            }
        }

        $cursor = $query->get_cursor();
        return new offset_cursor_paginator($builder, $cursor);
    }

    /**
     * @internal
     * @param array $record
     * @return member_request
     */
    public static function create_member_request(array $record): member_request {
        $map = [
            'id' => 'member_request_id',
            'course_id' => 'member_request_course_id',
            'user_id' => 'member_request_user_id',
            'time_created' => 'member_request_time_created',
            'time_accepted' => 'member_request_time_accepted',
            'time_declined' => 'member_request_time_declined',
            'time_cancelled' => 'member_request_time_cancelled',
            'request_content' => 'member_request_request_content'
        ];

        $user_fields = get_all_user_name_fields(false, null, 'user_');
        $user_fields['email'] = 'user_email';
        $user_fields['picture'] = 'user_picture';
        $user_fields['imagealt'] = 'user_image_alt';
        $user_fields['id'] = 'user_id';

        // Start constructing user record.
        $user_record = [];
        foreach ($user_fields as $field => $sql_field) {
            if (!array_key_exists($sql_field, $record)) {
                throw new \coding_exception("The result record does not have field '{$sql_field}'");
            }

            $user_record[$field] = $record[$sql_field];
        }

        // Start constructing member_request
        $entity = new workspace_member_request();
        foreach ($map as $field => $sql_field) {
            if (!array_key_exists($sql_field, $record)) {
                throw new \coding_exception("The result record does not have field '{$sql_field}'");
            }

            if ($entity->has_attribute($field)) {
                $entity->set_attribute($field, $record[$sql_field]);
            }
        }

        return member_request::from_entity($entity, (object) $user_record);
    }
}