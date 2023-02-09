<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace totara_useraction\model;

use coding_exception;
use context;
use core\entity\user;
use core\entity\user_repository;
use core\orm\lazy_collection;
use core\orm\query\field;
use core\tenant_orm_helper;
use totara_useraction\filter\filter_contract;
use totara_useraction\model\scheduled_rule\execution_data;

/**
 * The user_search class applies the filters from scheduled rules.
 */
class user_search {

    /**
     * User repository.
     *
     * @var user_repository
     */
    private user_repository $repository;

    /**
     * List of filters applied.
     *
     * @var array
     */
    private array $applied_filters = [];

    /**
     * @param context $context
     */
    public function __construct(context $context) {
        $repository = user::repository()
            ->where('username', '!=', 'guest')
            ->where('deleted', 0);
        tenant_orm_helper::restrict_users(
            $repository,
            new field('id', $repository->get_builder()),
            $context
        );

        $this->repository = $repository;
    }

    /**
     * Get all the users.
     *
     * @return lazy_collection
     */
    public function get_all(): lazy_collection {
        return $this->repository->get_lazy();
    }

    /**
     * Apply a filter
     *
     * @param filter_contract $filter
     * @param execution_data $execution_data
     *
     * @return $this
     */
    public function apply_filter(filter_contract $filter, execution_data $execution_data): self {
        if (in_array(get_class($filter), $this->applied_filters)) {
            throw new coding_exception("Filter already applied");
        }
        $this->applied_filters[] = get_class($filter);
        $filter->apply($this->repository, $execution_data);

        return $this;
    }

    /**
     * Apply filters
     *
     * @param filter_contract[]|array $filters
     * @param execution_data $execution_data
     * @return $this
     */
    public function apply_filters(array $filters, execution_data $execution_data): self {
        /** @var filter_contract[] $filters */
        foreach ($filters as $filter) {
            $this->apply_filter($filter, $execution_data);
        }

        return $this;
    }
}
