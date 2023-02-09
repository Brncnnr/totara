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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\query\member;

use container_workspace\query\cursor_query;
use core\pagination\base_cursor;
use core\pagination\offset_cursor;
use context_course;

/**
 * Query for members
 */
final class query implements cursor_query {
    /**
     * @var int
     */
    public const ITEMS_PER_PAGE = 20;

    /**
     * It is a course's table id. "ttr_course".id - do not miss using it with the table field "ttr_workspace".id
     * @var int
     */
    private $workspace_id;

    /**
     * Either Active or Suspended value. However, If this is null, then we are going to fetch both.
     * @var int|null
     */
    private $member_status;

    /**
     * Default to {@see sort::NAME}.
     * @var @var int
     */
    private $sort;

    /**
     * User's name.
     * @var string|null
     */
    private $search_term;

    /**
     * @var offset_cursor|null
     */
    private $cursor;

    /**
     * @var bool
     */
    private $include_tenant_users;

    /**
     * query constructor.
     * @param int $workspace_id
     */
    public function __construct(int $workspace_id) {
        $this->workspace_id = $workspace_id;

        $this->search_term = null;
        $this->member_status = null;
        $this->sort = sort::NAME;
        $this->cursor = null;
        $this->include_tenant_users = true;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function set_include_tenant_users(bool $value): void {
        $this->include_tenant_users = $value;
    }

    /**
     * @return bool
     */
    public function include_tenant_users(): bool {
        return $this->include_tenant_users;
    }

    /**
     * @return base_cursor
     */
    public function get_cursor(): base_cursor {
        if (null === $this->cursor) {
            $this->cursor = new offset_cursor();
            $this->cursor->set_limit(static::ITEMS_PER_PAGE);
        }

        return $this->cursor;
    }

    /**
     * @param base_cursor $cursor
     */
    public function set_cursor(base_cursor $cursor): void {
        $this->cursor = $cursor;
    }

    /**
     * @param string $search_term
     * @return void
     */
    public function set_search_term(string $search_term): void {
        $this->search_term = $search_term;
    }

    /**
     * @return string|null
     */
    public function get_search_term(): ?string {
        return $this->search_term;
    }

    /**
     * @return int
     */
    public function get_sort(): int {
        return $this->sort;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_sort(int $value): void {
        if (!sort::is_valid($value)) {
            debugging("The sort value is invalid", DEBUG_DEVELOPER);
            return;
        }

        $this->sort = $value;
    }

    /**
     * Use ENROL_USER_ACTIVE or ENROL_USER_SUSPENDED
     *
     * @param int $status
     * @return void
     */
    public function set_member_status(int $status): void {
        $this->member_status = $status;
    }

    /**
     * @return int|null
     */
    public function get_member_status(): ?int {
        return $this->member_status;
    }

    /**
     * @return int
     */
    public function get_workspace_id(): int {
        return $this->workspace_id;
    }

    /**
     * @return int
     */
    public function get_worksapce_tenant_id(): ?int {
        $context = context_course::instance($this->workspace_id);
        return $context->tenantid;
    }

    /**
     * @return bool
     */
    public function is_workspace_in_tenant(): bool {
        $tenant_id = $this->get_worksapce_tenant_id();
        return null !== $tenant_id;
    }
}