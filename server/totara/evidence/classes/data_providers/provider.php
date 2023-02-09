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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\data_providers;

use coding_exception;
use core\collection;
use core\orm\entity\entity;
use core\orm\entity\filter\filter;
use core\orm\entity\repository;
use core\orm\pagination\cursor_paginator;
use core\pagination\cursor;

/**
 * Common logic for filtering, fetching and getting data for use in queries etc.
 *
 * @package totara_evidence\data_providers
 */
abstract class provider {

    public const DEFAULT_PAGE_SIZE = 20;
    private const VALID_ORDER_DIRECTION = ['asc', 'desc'];

    /** @var callable ()->repository method to create a ORM repository. */
    private $repo_factory = null;

    /** @var string[] entity fields on which sorting is allowed. */
    private $allowed_order_by_fields = [];

    /** @var string current sorting field. */
    private $order_by = null;

    /** @var string sort direction. */
    private $order_direction = self::VALID_ORDER_DIRECTION[0];

    /** @var string current pagination size. */
    private $page_size = self::DEFAULT_PAGE_SIZE;

    /** @var filter[] filters currently in effect. */
    private $filters = [];

    /** @var callable function to return an initialized filter. */
    private $filter_factory = null;

    /**
     * provider constructor.
     *
     * @param string entity class. Used to create a repository if $repo_factory is not provided.
     * @param string[] allowed_order_by_fields entity fields on which sorting is allowed.
     * @param callable|null a (string, mixed)->?filter method that takes a filter name and the
     *        filter value and returns the initialized filter if it can be created.
     * @param callable|null $repo_factory ()->repository function to create a single use ORM
     *        repository. For cases where you need a repository that has joins, etc.
     */
    public function __construct(
        string $entity_class,
        array $allowed_order_by_fields = [],
        ?callable $filter_factory = null,
        ?callable $repo_factory = null
    ) {
        $this->repo_factory = $repo_factory
            ?: function () use ($entity_class): repository {
                return $entity_class::repository();
            };

        $this->filter_factory = $filter_factory;

        if ($allowed_order_by_fields) {
            $this->allowed_order_by_fields = $allowed_order_by_fields;
            $this->order_by = reset($allowed_order_by_fields);
        }
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
     * @param string|null $order_by entity field on which to sort.
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

            $order_by = strtolower($order_by);
            if (!in_array($order_by, $this->allowed_order_by_fields)) {
                $allowed = implode(', ', $this->allowed_order_by_fields);
                throw new coding_exception("sort field must be one of these: $allowed");
            }

            $this->order_by = $order_by;
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
            throw new coding_exception("no filter factory registered");
        }
        $create_filter = $this->filter_factory;

        $new_filters = [];
        foreach ($filters as $key => $value) {
            $filter_value = $this->validate_filter_value($value);
            if (is_null($filter_value)) {
                continue;
            }

            $filter = $create_filter($key, $filter_value);
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
     * - a non empty string _after it has been trimmed_
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
        $factory = $this->repo_factory;
        $repository = $factory();

        if ($this->filters) {
            $repository = $repository->set_filters($this->filters);
        }

        if ($this->order_by) {
            $repository = $repository->order_by($this->order_by, $this->order_direction);
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

}
