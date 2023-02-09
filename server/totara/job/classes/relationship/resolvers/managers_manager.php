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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_job
 */

namespace totara_job\relationship\resolvers;

use context;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\tenant_orm_helper;
use totara_core\relationship\relationship_resolver;
use totara_core\relationship\relationship_resolver_dto;
use totara_job\entity\job_assignment;

class managers_manager extends relationship_resolver {

    /**
     * Get a list of fields that can be provided to {@see get_users}
     *
     * @return string[][]
     */
    public static function get_accepted_fields(): array {
        return [
            ['job_assignment_id'],
            ['user_id'],
        ];
    }

    /**
     * Retrieve manager's manager/temp manager's manager list by user id
     *
     * @param array $data
     * @param context $context
     * @return array
     * @throws \coding_exception
     */
    protected function get_data(array $data, context $context): array {
        $repository = job_assignment::repository()->as('user_job');
        if (!empty($data['job_assignment_id'])) {
            $repository->where('user_job.id', $data['job_assignment_id']);
        } else {
            $repository->where('user_job.userid', $data['user_id']);
        }

        return $repository
            ->select_raw('DISTINCT managers_manager_job.userid')
            ->left_join([job_assignment::TABLE, 'manager_job'], manager::COLUMN_MANAGER, 'id')
            ->left_join([job_assignment::TABLE, 'temp_manager_job'], manager::COLUMN_TEMP_MANAGER, 'id')
            ->left_join([job_assignment::TABLE, 'managers_manager_job'], function (builder $builder) {
                $builder->where_field('manager_job.' . manager::COLUMN_MANAGER, 'managers_manager_job.id')
                    ->or_where_field('manager_job.' . manager::COLUMN_TEMP_MANAGER, 'managers_manager_job.id')
                    ->or_where_field('temp_manager_job.' . manager::COLUMN_MANAGER, 'managers_manager_job.id')
                    ->or_where_field('temp_manager_job.' . manager::COLUMN_TEMP_MANAGER, 'managers_manager_job.id');
            })
            ->where_not_null('managers_manager_job.userid')
            ->when(true, function (repository $repository) use ($context) {
                // Make sure we only consider users who are in the same tenant
                // as the user given here
                tenant_orm_helper::restrict_users(
                    $repository,
                    'managers_manager_job.userid',
                    $context
                );
            })
            ->get()
            ->map(function ($item) {
                    return new relationship_resolver_dto($item->userid);
            })
            ->all();
    }
}
