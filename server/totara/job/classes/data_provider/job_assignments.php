<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totara.com>
 * @package totara_job
 */
namespace totara_job\data_provider;

use core\collection;
use core\orm\entity\repository;
use totara_job\data_provider\application\filter\tenant_id;
use totara_job\data_provider\provider as local_provider;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_job\job_assignment;

/**
 * A data provider to return a collection of job_assignment results.
 */
class job_assignments extends local_provider {

    protected $sort_by_fields = [
        'id',
        'userid',
        'shortname',
        'startdate',
        'endate',
        'position',
        'organisation',
        'managerjaid',
        'tempmanagerjaid',
        'tempmanagerexpirydate',
        'appraiserid',
        'staffcount',
        'tempstaffcount'
    ];

    /**
     * Build query for job assignments, possibly limited by users in this tenant.
     *
     * @return repository
     */
    protected function build_query(): repository {

        $repo = job_assignment_entity::repository()
            ->as('job_assignment')
            ->join(['user', 'ja_user'], 'job_assignment.userid', '=', 'id')
            ->select(['*']);

        return $repo;
    }

    /**
     * Map the job_assignment entities to the job_assignment class.
     *
     * @return collection|job_assignment[]
     */
    protected function process_fetched_items(): collection {
        return $this->items->map_to([job_assignment::class, 'from_entity']);
    }

    /**
     * Filter the repository by tenant id.
     *
     * @param repository $repository
     * @param int $tenant_id
     */
    protected function filter_query_by_tenant_id(repository $repository, int $tenant_id): void {
        $repository->set_filter(
            (new tenant_id('ja_user'))->set_value($tenant_id)
        );
    }
}
