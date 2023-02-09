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
 * @package totara_playlist
 */
namespace totara_playlist\query;

use totara_playlist\pagination\cursor;
use totara_playlist\query\option\playlist_sort;
use totara_playlist\query\option\playlist_source;

/**
 * Query object to hold the filter options for fetching the playlists.
 */
final class playlist_query {
    /**
     * @var int|null
     */
    private $resource_id;

    /**
     * @var cursor|null
     */
    private $cursor;

    /**
     * The target user that we want to fetch playlists against.
     * @var int
     */
    private $user_id;

    /**
     * @var int|null
     */
    private $source;

    /**
     * @var int|null
     */
    private $access;

    /**
     * @var int|null
     */
    private $sort;

    /**
     * resource_playlist constructor.
     * @param int $user_id
     */
    public function __construct(int $user_id) {
        $this->resource_id = null;
        $this->cursor = null;
        $this->user_id = $user_id;
        $this->source = null;
        $this->access = null;
        $this->sort = null;
    }

    /**
     * @param array $parameters
     * @return playlist_query
     */
    public static function from_parameters(array $parameters): playlist_query {
        global $USER;

        $user_id = $USER->id;
        if (!empty($parameters['userid'])) {
            $user_id = $parameters['userid'];
        }

        $query = new static($user_id);
        if (!empty($parameters['source'])) {
            $source = playlist_source::get_value($parameters['source']);
            $query->set_source($source);
        }

        if (isset($parameters['resource_id'])) {
            $query->set_resource_id((int) $parameters['resource_id']);
        }

        if (isset($parameters['cursor'])) {
            $cursor = cursor::decode($parameters['cursor']);
            $query->set_cursor($cursor);
        }

        if (isset($parameters['sort'])) {
            $sort = playlist_sort::get_value($parameters['sort']);
            $query->set_sort($sort);
        }

        return $query;
    }

    /**
     * @return int|null
     */
    public function get_sort(): ?int {
        return $this->sort;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_sort(int $value): void {
        $this->sort = $value;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_source(int $value): void {
        $this->source = $value;
    }

    /**
     * Returning null means that we are looking for both OWN/BOOKMARKED.
     * @return int|null
     */
    public function get_source(): ?int {
        return $this->source;
    }

    /**
     * @return int|null
     */
    public function get_access(): ?int {
        return $this->access;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_access(int $value): void {
        $this->access = $value;
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * @return int|null
     */
    public function get_resource_id(): ?int {
        return $this->resource_id;
    }

    /**
     * @param int $resource_id
     * @return void
     */
    public function set_resource_id(int $resource_id): void {
        $this->resource_id = $resource_id;
    }

    /**
     * @return cursor
     */
    public function get_cursor(): cursor {
        if (null === $this->cursor) {
            $this->cursor = new cursor();
            $this->cursor->set_limit(cursor::LIMIT);
        }

        return $this->cursor;
    }

    /**
     * @param cursor $cursor
     * @return void
     */
    public function set_cursor(cursor $cursor): void {
        $this->cursor = $cursor;
    }
}