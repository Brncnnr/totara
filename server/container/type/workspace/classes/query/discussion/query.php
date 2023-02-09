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
namespace container_workspace\query\discussion;

use container_workspace\query\cursor_query;
use core\pagination\base_cursor;
use core\pagination\offset_cursor;

/**
 * Query class for discussion loader.
 */
final class query implements cursor_query {
    /**
     * @var offset_cursor|null
     */
    private $cursor;

    /**
     * Workspace's id
     * @var int
     */
    private $workspace_id;

    /**
     * Sort option
     * @var int
     */
    private $sort;

    /**
     * @var string|null
     */
    private $search_term;

    /**
     * If this is null, then we will include all pinned and none pinned discussion. Otherwise, one or the other.
     * @var null|bool
     */
    private $pinned;

    /**
     * query constructor.
     * @param int $workspace_id
     */
    public function __construct(int $workspace_id) {
        $this->workspace_id = $workspace_id;

        $this->sort = sort::RECENT;
        $this->search_term = null;
        $this->pinned = null;
        $this->cursor = null;
    }

    /**
     * @return base_cursor
     */
    public function get_cursor(): base_cursor {
        if (null === $this->cursor) {
            $this->cursor = offset_cursor::create();
        }

        return $this->cursor;
    }

    /**
     * @param base_cursor $cursor
     * @return void
     */
    public function set_cursor(base_cursor $cursor): void {
        $this->cursor = $cursor;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function set_pinned(bool $value): void {
        $this->pinned = $value;
    }

    /**
     * @return bool|null
     */
    public function get_pinned_value(): ?bool {
        return $this->pinned;
    }

    /**
     * @return string|null
     */
    public function get_search_term(): ?string {
        return $this->search_term;
    }

    /**
     * @param string $value
     * @return void
     */
    public function set_search_term(string $value): void {
        $this->search_term = $value;
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
            debugging("Invalid sort value '{$value}'", DEBUG_DEVELOPER);
            return;
        }

        $this->sort = $value;
    }

    /**
     * @return int
     */
    public function get_workspace_id(): int {
        return $this->workspace_id;
    }
}