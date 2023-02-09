<?php
/**
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core_cohort
 */

namespace core\data_providers;

use coding_exception;
use context;
use context_system;
use core\entity\cohort;
use core\entity\cohort_filters;
use core\orm\entity\repository;
use core\orm\pagination\cursor_paginator;
use core\pagination\cursor;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * "Model" for dealing with collections of cohorts.
 */
class cohorts {
    public const DEFAULT_PAGE_SIZE = 20;

    private const VALID_ORDER_BY_FIELDS = ['id', 'name'];
    private const VALID_ORDER_DIRECTION = ['asc', 'desc'];

    private $page_size = self::DEFAULT_PAGE_SIZE;
    private $order_by = 'name';
    private $order_direction = 'asc';
    private $filters = [];
    private $mandatory_filters = [];
    private $include_tenant_cohorts = true;

    /**
     * Default constructor.
     *
     * @param context|null $context $context the context to use when retrieving cohorts. Only
     *        cohorts in this and its parents will be considered.
     * @param bool $tenant_scope If true, system cohorts would be excluded.
     */
    public function __construct(?context $context = null, ?bool $tenant_scope = false) {
        global $CFG;
        $this->mandatory_filters = ['visible' => true];
        $leaf_context = $context ?? context_system::instance();
        $limit_scope_to_tenant = $tenant_scope && $CFG->tenantsenabled;

        $contexts = [];

        /** @var context $context*/
        foreach ($leaf_context->get_parent_contexts(true) as $id => $context) {
            if ($limit_scope_to_tenant && $leaf_context->tenantid !== $context->tenantid) {
                continue;
            }
            if ($this->can_view_cohorts($context)) {
                $contexts[] = $id;
            }
        }

        if (empty($contexts)) {
            // Make sure we get an empty result
            $contexts = [0];
        }

        $this->mandatory_filters['context_ids'] = cohort_filters::for('context_ids', $contexts);

        $this->set_filters([]);
    }

    /**
     * Indicates whether the specified context gives the current user the rights
     * to see its associated cohorts.
     *
     * @param context $context the context to check.
     *
     * @return boolean true if the user can view the cohorts in this context.
     */
    private function can_view_cohorts(context $context): bool {
        return has_capability('moodle/cohort:view', $context);
    }

    /**
     * Indicates the number of entries retrieved per page.
     *
     * @param int $page_size page size.
     *
     * @return cohorts this object.
     */
    public function set_page_size(int $page_size): cohorts {
        $this->page_size = $page_size > 0 ? $page_size : self::DEFAULT_PAGE_SIZE;
        return $this;
    }

    /**
     * Indicates the sorting parameters to use when retrieving cohorts.
     *
     * @param string $order_by cohort field on which to sort.
     * @param string $order_direction sorting order either 'ASC' or 'DESC'.
     *
     * @return cohorts this object.
     */
    public function set_order(string $order_by = 'name', string $order_direction = 'asc'): cohorts {
        $order_by = strtolower($order_by);
        if (!in_array($order_by, self::VALID_ORDER_BY_FIELDS)) {
            $allowed = implode(', ', self::VALID_ORDER_BY_FIELDS);
            throw new coding_exception("ordering can only be by these fields: $allowed");
        }
        $this->order_by = $order_by;

        $order_direction = strtolower($order_direction);
        if (!in_array($order_direction, self::VALID_ORDER_DIRECTION)) {
            $allowed = implode(', ', self::VALID_ORDER_DIRECTION);
            throw new coding_exception("order must be one of these: $allowed");
        }
        $this->order_direction = $order_direction;

        return $this;
    }

    /**
     * By default tenant cohorts are included, use this method to exclude them
     *
     * @return cohorts
     */
    public function exclude_tenant_cohorts(): cohorts {
        $this->include_tenant_cohorts = false;

        return $this;
    }

    /**
     * Indicates the filters to use when retrieving cohorts.
     *
     * @param array $filters mapping of cohort fields to search values.
     *
     * @return cohorts this object.
     */
    public function set_filters(array $filters): cohorts {
        $new_filters = [];
        foreach ($filters as $key => $value) {
            $filter_value = $this->validate_filter_value($value);
            if (is_null($filter_value)) {
                continue;
            }

            $filter = cohort_filters::for($key, $filter_value);
            if (!$filter) {
                throw new coding_exception("unknown cohort filter: '$key'");
            }

            $new_filters[$key] = $filter;
        }

        $this->filters = array_merge($this->mandatory_filters, $new_filters);
        return $this;
    }

    /**
     * Checks whether the filter value is "valid". "Valid" means:
     * - a non empty string _after it has been trimmed_
     * - an array _even if it is empty_. An empty array results in a filter that
     *   matches nothing.
     * - int values that are not zeros
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
            return $str_value ? $str_value : null;
        }

        if (is_int($value)) {
            return $value !== 0 ? $value : null;
        }

        return $value;
    }

    /**
     * Returns a list of cohorts meeting the previously set search criteria.
     *
     * @param cursor|null $cursor $cursor indicates which "page" of cohorts to retrieve.
     *
     * @return array[cohort] the retrieved cohort entities.
     */
    public function fetch_paginated(?cursor $cursor = null): array {
        $repository = cohort::repository()
            ->when(!$this->include_tenant_cohorts, function (repository $repository) {
                $repository->where('component', '<>', 'totara_tenant');
            })
            ->set_filters($this->filters)
            ->order_by($this->order_by, $this->order_direction);

        $pages = $cursor ? $cursor : cursor::create()->set_limit($this->page_size);
        $paginator = new cursor_paginator($repository, $pages, true);

        return $paginator->get();
    }
}