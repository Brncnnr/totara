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
 * @package container_workspace
 */

namespace container_workspace\query\audience;

use container_workspace\query\cursor_query;
use container_workspace\workspace;
use core\pagination\base_cursor;
use core\pagination\offset_cursor;

/**
 * Query class for audience loader.
 */
class query implements cursor_query {

    /**
     * @var base_cursor
     */
    private $cursor;

    /**
     * Name of audience to search for.
     *
     * @var string|null
     */
    private $name;

    /**
     * Workspace.
     *
     * @var workspace|\core_container\container
     */
    private $workspace;

    /**
     * Constructor for query class.
     *
     * @param workspace $workspace
     * @param string|null $name Name filter for audience
     * @param int $page
     * @param int $limit
     */
    public function __construct(workspace $workspace, ?string $name = null, int $page = 1, int $limit = 20) {
        $this->workspace = $workspace;
        $this->name = $name;
        $this->cursor = offset_cursor::create([
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Get workspace id.
     *
     * @return int
     */
    public function get_workspace_id(): int {
        return $this->workspace->id;
    }

    /**
     * Get name filter used.
     *
     * @return string|null
     */
    public function get_name_filter(): ?string {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function get_cursor(): base_cursor {
        return $this->cursor;
    }

    /**
     * @inheritDoc
     */
    public function set_cursor(base_cursor $cursor): void {
        $this->cursor = $cursor;
    }
}
