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
use core\orm\query\field;
use core\tenant_orm_helper;
use totara_core\relationship\relationship_resolver;
use totara_core\relationship\relationship_resolver_dto;
use totara_job\entity\job_assignment;

class appraiser extends relationship_resolver {

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
     * @inheritDoc
     */
    protected function get_data(array $data, context $context): array {
        $repository = job_assignment::repository();

        if (!empty($data['job_assignment_id'])) {
            $repository->where('id', $data['job_assignment_id']);
        } else {
            $repository->where('userid', $data['user_id']);
        }

        return $repository
            ->select_raw('DISTINCT appraiserid')
            ->where_not_null('appraiserid')
            ->when(true, function (repository $repository) use ($context) {
                tenant_orm_helper::restrict_users(
                    $repository,
                    new field('appraiserid', $repository->get_builder()),
                    $context
                );
            })
            ->get()
            ->map(
                function ($item) {
                    return new relationship_resolver_dto($item->appraiserid);
                }
            )
            ->all();
    }

}
