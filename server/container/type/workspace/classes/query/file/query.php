<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package container_workspace
 */
namespace container_workspace\query\file;

use container_workspace\query\cursor_query;
use core\pagination\base_cursor;
use core\pagination\offset_cursor;

/**
 * Query for file
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
     * Default to {@see sort::RECENT}
     * @var int
     */
    private $sort;

    /**
     * If extension is null then fetch all the extensions.
     * @var string|null
     */
    private $extension;

    /**
     * By default this property will be true. However, when it comes to fetch the files to do the deletion,
     * this property can be set to false, so that the loader can be optimized to skip finding the alt text and
     * fetch records as fast as it can.
     *
     * @var bool
     */
    private $include_alt_text;

    /**
     * query constructor.
     * @param int $workspace_id
     */
    public function __construct(int $workspace_id) {
        $this->workspace_id = $workspace_id;
        $this->cursor = null;

        $this->sort = sort::RECENT;
        $this->extension = null;

        $this->include_alt_text = true;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function set_include_alt_text(bool $value): void {
        $this->include_alt_text = $value;
    }

    /**
     * @return bool
     */
    public function is_including_alt_text(): bool {
        return $this->include_alt_text;
    }

    /**
     * @return int
     */
    public function get_workspace_id(): int {
        return $this->workspace_id;
    }

    /**
     * @param int $sort
     * @return void
     */
    public function set_sort(int $sort): void {
        if (!sort::is_valid($sort)) {
            debugging("Invalid sort value '{$sort}'", DEBUG_DEVELOPER);
            return;
        }

        $this->sort = $sort;
    }

    /**
     * @return int
     */
    public function get_sort(): int {
        return $this->sort;
    }

    /**
     * @param string $extension
     */
    public function set_extension(string $extension): void {
        $this->extension = $extension;
    }

    /**
     * @return string|null
     */
    public function get_extension(): ?string {
        return $this->extension;
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
     */
    public function set_cursor(base_cursor $cursor): void {
        $this->cursor = $cursor;
    }
}