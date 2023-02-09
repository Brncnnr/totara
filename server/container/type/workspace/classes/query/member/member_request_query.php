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
 * Query object for applying all the options to fetch member requests.
 */
final class member_request_query implements cursor_query {
    /**
     * @var int
     */
    private $status;

    /**
     * @var int
     */
    private $workspace_id;

    /**
     * @var offset_cursor|null
     */
    private $cursor;

    /**
     * member_request_query constructor.
     * @param int $workspace_id
     */
    public function __construct(int $workspace_id) {
        $this->workspace_id = $workspace_id;
        $this->status = member_request_status::PENDING;
        $this->cursor = null;
    }

    /**
     * @param int $status
     * @return void
     */
    public function set_member_request_status(int $status): void {
        if (!member_request_status::is_valid($status)) {
            throw new \coding_exception("Invalid member request status");
        }

        $this->status = $status;
    }

    /**
     * @return int
     */
    public function get_member_request_status(): int {
        return $this->status;
    }

    /**
     * @return int
     */
    public function get_workspace_id(): int {
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