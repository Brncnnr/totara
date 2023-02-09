<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\data_provider;

use coding_exception;
use core\collection;
use core\orm\entity\entity;
use core\orm\entity\filter\filter;
use core\orm\entity\repository;
use core\orm\pagination\cursor_paginator;
use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use core\pagination\cursor;
use core\orm\entity\filter\filter_factory;
use core\pagination\offset_cursor;

/**
 * Common logic for filtering, fetching and getting data for use in queries etc.
 *
 * @package totara_core\data_provider
 */
abstract class provider implements provider_interface {

    public const DEFAULT_PAGE_SIZE = 20;
    private const VALID_ORDER_DIRECTION = ['asc', 'desc'];

    /** @var repository */
    private $repository;

    /** @var string[] entity fields on which sorting is allowed. */
    private $allowed_order_by_fields = [];

    /** @var string[] current sorting field(s). */
    private $order_by = [];

    /** @var string sort direction. */
    private $order_direction = self::VALID_ORDER_DIRECTION[0];

    /** @var string current pagination size. */
    private $page_size = self::DEFAULT_PAGE_SIZE;

    /** @var int|null user ID */
    private $user_id = null;

    /** @var filter[] filters currently in effect. */
    protected $filters = [];

    /** @var filter_factory factory to return an initialized filter. */
    protected $filter_factory;

    /** @var callable factory to return an initialized repository on demand. */
    private $repository_factory = null;

    /**
     * provider constructor.
     *
     * @param repository $repository repository instance.
     * @param string[] allowed_order_by_fields entity fields on which sorting is allowed.
     * @param filter_factory|null $filter_factory factory that returns the initialized filter if it can be created.
     *        repository. For cases where you need a repository that has joins, etc.
     * @param callable $repository_factory ()->repository method to create a
     *        repository on demand. Note: if this is not provided, then the
     *        explicit $repository value is used and *it makes this provider
     *        instance a single use object*.
     */
    public function __construct(
        repository $repository,
        ?array $allowed_order_by_fields = [],
        ?filter_factory $filter_factory = null,
        ?callable $repository_factory = null
    ) {
        $this->repository = $repository;
        $this->repository_factory = $repository_factory;
        $this->filter_factory = $filter_factory;

        if ($allowed_order_by_fields) {
            $this->allowed_order_by_fields = $allowed_order_by_fields;
            $this->order_by = [reset($allowed_order_by_fields)];
        }
    }

    /**
     * @param int|null $user_id
     *
     * @return provider this object.
     */
    public function set_user_id(?int $user_id = null): provider {
        global $USER;
        $this->user_id = $user_id ?? $USER->id;

        return $this;
    }

    /**
     * Indicates the number of entries retrieved per page.
     *
     * @param int|null $page_size page size.
     *
     * @return provider this object.
     */
    public function set_page_size(?int $page_size = null): provider {
        if (!empty($page_size)) {
            $this->page_size = $page_size;
        }
        return $this;
    }

    /**
     * Indicates the sorting parameters to use when retrieving entities.
     *
     * @param string|null $order_by entity comma separated fields on which to sort.
     * @param string|null $order_direction sorting order either 'asc' or 'desc'.
     *
     * @return provider this object.
     */
    public function set_order(
        ?string $order_by = null,
        ?string $order_direction = null
    ): provider {
        if ($order_by) {
            if (!$this->allowed_order_by_fields) {
                throw new coding_exception("no sorting fields registered");
            }

            $this->order_by = collection::new(explode(',', $order_by))
                ->map(
                    function (string $raw): string {
                        $field = trim(strtolower($raw));

                        if (!in_array($field, $this->allowed_order_by_fields)) {
                            $allowed = implode(', ', $this->allowed_order_by_fields);
                            throw new coding_exception("sort field must be one of these: $allowed");
                        }

                        return $field;
                    }
                )
                ->all();
        }

        if ($order_direction) {
            $order_direction = strtolower($order_direction);
            if (!in_array($order_direction, self::VALID_ORDER_DIRECTION)) {
                $allowed = implode(', ', self::VALID_ORDER_DIRECTION);
                throw new coding_exception("sort direction must be one of these: $allowed");
            }

            $this->order_direction = $order_direction;
        }

        return $this;
    }

    /**
     * Indicates the filters to use when retrieving entities.
     *
     * @param array $filters mapping of entity field names to search values.
     *
     * @return provider this object.
     */
    public function set_filters(array $filters): provider {
        if (!$this->filter_factory) {
            throw new coding_exception("No filter factory registered");
        }

        $new_filters = [];
        foreach ($filters as $key => $value) {
            $filter_value = $this->validate_filter_value($value);
            if (is_null($filter_value)) {
                continue;
            }

            $filter = $this->filter_factory->create($key, $filter_value, $this->user_id);
            if (!$filter) {
                throw new coding_exception("unknown filter: '$key'");
            }

            $new_filters[$key] = $filter;
        }

        $this->filters = $new_filters;
        return $this;
    }

    /**
     * Checks whether the filter value is "valid". "Valid" means:
     * - a non-empty string _after it has been trimmed_
     * - an array _even if it is empty_. An empty array results in a filter that
     *   matches nothing.
     * - int values
     * - non nulls
     *
     * @param mixed $value the value to check.
     *
     * @return mixed the filter value if it is "valid" or null otherwise.
     */
    private function validate_filter_value($value) {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $str_value = trim($value);
            return strlen($str_value) > 0 ? $str_value : null;
        }

        return $value;
    }

    /**
     * Initializes an instance of the repository to be used for querying.
     *
     * @return repository
     */
    private function prepare_repository(): repository {
        $factory = $this->repository_factory;
        $repository = is_callable($factory) ? $factory() : $this->repository;

        if ($this->filters) {
            $repository->set_filters($this->filters);
        }

        if ($this->order_by) {
            foreach ($this->order_by as $sort) {
                $repository->order_by($sort, $this->order_direction);
            }
        }

        return $repository;
    }

    /**
     * Returns the entities meeting the previously set search criteria.
     *
     * @return \core\orm\collection|entity[] the retrieved entities.
     */
    public function fetch(): collection {
        return $this->prepare_repository()->get();
    }

    /**
     * Returns a list of entities meeting the previously set search criteria.
     *
     * @param cursor|null $cursor $cursor indicates which "page" of entities to retrieve.
     * @param callable|null $transform function to transform the result
     *
     * @return entity[] the retrieved entities.
     */
    public function fetch_paginated(?cursor $cursor = null, ?callable $transform = null): array {
        $repository = $this->prepare_repository();

        $pages = $cursor ?: cursor::create()->set_limit($this->page_size);
        $paginator = new cursor_paginator($repository, $pages, true);

        if (is_callable($transform)) {
            $paginator->transform($transform);
        }

        return $paginator->get();
    }

    /**
     * Returns a list of entities meeting the previously set search criteria.
     *
     * @param offset_cursor|null $cursor $cursor indicates which "page" of entities to retrieve.
     * @param callable|null $transform function to transform the result
     *
     * @return entity[] the retrieved entities.
     */
    public function fetch_offset_paginated(?offset_cursor $cursor = null, ?callable $transform = null): array {
        $repository = $this->prepare_repository();
        $pages = $cursor ?: offset_cursor::create()->set_limit($this->page_size)->set_page(1);
        $paginator = new offset_cursor_paginator($repository, $pages);
        if (is_callable($transform)) {
            $paginator->transform($transform);
        }
        return $paginator->get();
    }

    /**
     * @return builder
     */
    public function get_builder(): builder {
        return $this->prepare_repository()->apply_filters()->get_builder();
    }

}