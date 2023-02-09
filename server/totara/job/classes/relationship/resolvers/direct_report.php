<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_job
 */

namespace totara_job\relationship\resolvers;

use context;
use core\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\tenant_orm_helper;
use totara_core\relationship\relationship_resolver;
use totara_core\relationship\relationship_resolver_dto;
use totara_job\entity\job_assignment;

class direct_report extends relationship_resolver {

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
     * Retrieve user list by manager/temp manager id
     *
     * @param array $data
     * @param context $context
     * @return array
     * @throws \coding_exception
     */
    protected function get_data(array $data, context $context): array {
        $repository = job_assignment::repository()->as('direct_report_job_assignment');
        if (!empty($data['job_assignment_id'])) {
            $repository->where('manager_job_assignment.id', $data['job_assignment_id']);
        } else {
            $repository->where('manager_job_assignment.userid', $data['user_id']);
        }

        return $repository
            ->select_raw('DISTINCT direct_report_job_assignment.userid')
            ->left_join([job_assignment::TABLE, 'manager_job_assignment'], function (builder $builder) {
                $builder->where_field('direct_report_job_assignment.' . manager::COLUMN_MANAGER,  'manager_job_assignment.id')
                    ->or_where_field('direct_report_job_assignment.' . manager::COLUMN_TEMP_MANAGER,  'manager_job_assignment.id');
            })
            ->where_not_null('manager_job_assignment.userid')
            ->when(true, function (repository $repository) use ($context) {
                tenant_orm_helper::restrict_users(
                    $repository,
                    'direct_report_job_assignment.userid',
                    $context
                );
            })
            ->order_by('direct_report_job_assignment.userid')
            ->get()
            ->map(function ($item) {
                return new relationship_resolver_dto($item->userid);
            })
            ->all();
    }
}

