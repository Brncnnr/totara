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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
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

class manager extends relationship_resolver {

    public const COLUMN_MANAGER = 'managerjaid';
    public const COLUMN_TEMP_MANAGER = 'tempmanagerjaid';

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
     * Retrieve manager/temp manager list by user id
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
            ->select_raw('DISTINCT manager_job.userid')
            ->join([job_assignment::TABLE, 'manager_job'], function (builder $builder) {
                $builder->where_field('user_job.' . self::COLUMN_MANAGER, 'manager_job.id')
                    ->or_where_field('user_job.' . self::COLUMN_TEMP_MANAGER, 'manager_job.id');
            })
            ->where_not_null('manager_job.userid')
            ->when(true, function (repository $repository) use ($context) {
                tenant_orm_helper::restrict_users(
                    $repository,
                    'manager_job.userid',
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
