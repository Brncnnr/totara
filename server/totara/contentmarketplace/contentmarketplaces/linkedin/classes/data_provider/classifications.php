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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\data_provider;

use contentmarketplace_linkedin\entity\classification as classification_entity;
use contentmarketplace_linkedin\model\classification as classification_model;
use contentmarketplace_linkedin\repository\classification_repository;
use core\collection;
use core\orm\entity\repository;
use core\orm\query\order;
use totara_contentmarketplace\data_provider\provider;

/**
 * Class classifications
 *
 * @package contentmarketplace_linkedin\data_provider
 *
 * @method collection|classification_model[] get
 */
class classifications extends provider {

    public const SORT_BY_ALPHABETICAL = 'ALPHABETICAL';

    /**
     * @var bool
     */
    private $with_children = false;

    /**
     * @var bool
     */
    private $with_parents = false;

    /**
     * @return repository
     */
    protected function build_query(): repository {
        $repository = classification_entity::repository();

        if ($this->with_parents) {
            $repository->with('parents');
        }
        if ($this->with_children) {
            $repository->with('children');
        }

        return $repository;
    }

    /**
     * Set this flag to fetch child classification records too.
     *
     * It is highly recommended to set this if you are wanting to use the children relation,
     * as this improves the performance by only executing one additional DB query.
     *
     * @param bool $with_children
     * @return $this
     */
    public function with_children(bool $with_children = true): self {
        $this->with_children = $with_children;
        return $this;
    }

    /**
     * @return collection
     */
    protected function process_fetched_items(): collection {
        return $this->items->map_to(classification_model::class);
    }

    /**
     * @param classification_repository $repository
     * @param string $locale_language
     *
     * @return void
     */
    protected function filter_query_by_locale_language(
        classification_repository $repository,
        string $locale_language
    ): void {
        $repository->where('locale_language', $locale_language);
    }

    /**
     * @param classification_repository $repository
     * @param array $types
     *
     * @return void
     */
    protected function filter_query_by_classification_types(classification_repository $repository, array $types): void {
        $repository->where_in('type', $types);
    }

    /**
     * @param classification_repository $repository
     * @return void
     */
    protected function sort_query_by_alphabetical(classification_repository $repository): void {
        $repository->order_by('name', order::DIRECTION_ASC);
    }

    /**
     * @return string|null
     */
    protected function get_default_sort_by(): ?string {
        return self::SORT_BY_ALPHABETICAL;
    }
}