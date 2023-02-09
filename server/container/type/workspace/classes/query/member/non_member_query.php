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

/**
 * Query for fetching non member(s).
 *
 * @deprecated since Totara 16. Use container_workspace\data_providers\non_members
 * instead.
 */
final class non_member_query implements cursor_query {
    /**
     * @var int
     */
    private $workspace_id;

    /**
     * @var string|null
     */
    private $search_term;

    /**
     * @var base_cursor|null
     */
    private $cursor;

    /**
     * non_member_query constructor.
     * @param int $workspace_id
     */
    public function __construct(int $workspace_id) {
        debugging(
            'Class non_member_query is deprecated; use container_workspace\data_providers\non_members instead',
            DEBUG_DEVELOPER
        );
        $this->workspace_id = $workspace_id;
        $this->search_term = null;
        $this->cursor = null;
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
     * @return string
     */
    public function get_workspace_id(): string {
        return $this->workspace_id;
    }

    /**
     * @return base_cursor
     */
    public function get_cursor(): base_cursor {
        if (null === $this->cursor) {
            $this->cursor = new offset_cursor();
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
}