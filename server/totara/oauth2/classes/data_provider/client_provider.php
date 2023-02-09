<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_oauth2
 */

namespace totara_oauth2\data_provider;

use core\collection;
use totara_oauth2\entity\client_provider as entity;
use core\orm\entity\repository;
use totara_oauth2\model\client_provider as model;
use totara_oauth2\repository\client_provider_repository;

class client_provider extends provider {

    /**
     * @inheritDoc
     */
    protected function get_default_sort_by(): ?string {
        return 'name';
    }

    /**
     * @return repository
     */
    protected function build_query(): repository {
        /** @var client_provider_repository $repository */
        $repository = entity::repository();
        return $repository;
    }

    /**
     * @return collection
     */
    protected function process_fetched_items(): collection {
        return $this->items->map_to(model::class);
    }

    /**
     * @param client_provider_repository $repository
     * @param int $id
     */
    protected function filter_query_by_id(repository $repository, int $id): void {
        if (!empty($id)) {
            $repository->where('id', $id);
        }
    }

    /**
     * @param repository $repository
     * @param int $internal Whether to show internal providers created programmatically
     * @return void
     */
    protected function filter_query_by_internal(repository $repository, int $internal): void {
        $repository->where('internal', $internal);
    }

    /**
     * @param client_provider_repository $repository
     *
     * @return void
     */
    protected function sort_query_by_name(repository $repository): void {
        $repository->order_by('name');
    }

}

