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
namespace container_workspace\query\workspace;

use container_workspace\member\status;
use container_workspace\query\cursor_query;
use core\pagination\base_cursor;
use core\pagination\offset_cursor;

/**
 * Query filter implementation for workspaces.
 */
final class query implements cursor_query {
    /**
     * @var int
     */
    public const ITEMS_PER_PAGE = 60;

    /**
     * @param int
     */
    private $source;

    /**
     * The user's workspace that we want to fetch for. This is a target user.
     *
     * @var int
     */
    private $user_id;

    /**
     * The user who runs the query against $user_id. Sometimes it can be the same as $user_id.
     *
     * @var int
     */
    private $actor_id;

    /**
     * This will be works when source is either MEMBER only or both MEMBER_AND_OWNED
     * @var int|null
     */
    private $member_status;

    /**
     * Default to {@see sort::RECENT}
     * @var int
     */
    private $sort;

    /**
     * @var string|null
     */
    private $search_term;

    /**
     * @var int|null
     */
    private $access;

    /**
     * @var offset_cursor|null
     */
    private $cursor;

    /**
     * A flag whether to tell the loader fetching the query that is going to be deleted or not.
     * + FALSE: Do not include the workspaces that are going to be deleted
     * + TRUE: Include the workspaces that are going to be deleted
     * + NULL: Include workspaces that either going to be deleted or not.
     *
     * @var bool|null
     */
    private $to_be_deleted;

    /**
     * query constructor.
     * @param int       $source
     * @param int|null  $user_id    => The user's workspace that we want to fetch against of.
     */
    public function __construct(int $source, ?int $user_id = null) {
        global $USER;

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $this->source = $source;
        if (source::is_member_and_owned($source) || source::is_owned_only($source)) {
            // When source is about member and owned, then the member status need to be set to active.
            // Otherwise, we can leave it null.
            $this->member_status = status::get_active();
        } else {
            $this->member_status = null;
        }

        $this->user_id = $user_id;
        $this->search_term = null;
        $this->sort = sort::RECENT;
        $this->cursor = null;
        $this->access = null;
        $this->to_be_deleted = false;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_access(int $value): void {
        $this->access = $value;
    }

    /**
     * @return int|null
     */
    public function get_access(): ?int {
        return $this->access;
    }

    /**
     * @param base_cursor $cursor
     * @return void
     */
    public function set_cursor(base_cursor $cursor): void {
        $this->cursor = $cursor;
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
     * @param array     $parameters
     * @param int|null  $user_id    The target user's id. If it is not set, then user in session will be used.
     *
     * @return query
     */
    public static function from_parameters(array $parameters, ?int $user_id = null): query {
        global $USER;

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $source = source::ALL;
        if (isset($parameters['source'])) {
            $source = source::get_value($parameters['source']);
        }

        $query = new query($source, $user_id);

        if (isset($parameters['sort'])) {
            $sort = sort::get_value($parameters['sort']);
            $query->set_sort($sort);
        }

        if (isset($parameters['search_term'])) {
            $query->set_search_term($parameters['search_term']);
        }

        if (isset($parameters['cursor'])) {
            $cursor = offset_cursor::decode($parameters['cursor']);
            $query->set_cursor($cursor);
        }

        if (isset($parameters['access'])) {
            $access = access::get_value($parameters['access']);
            $query->set_access($access);
        }

        return $query;
    }

    /**
     * @param string $value
     * @return void
     */
    public function set_search_term(string $value): void {
        $this->search_term = $value;
    }

    /**
     * @return string|null
     */
    public function get_search_term(): ?string {
        return $this->search_term;
    }

    /**
     * @param int $sort
     * @return void
     */
    public function set_sort(int $sort): void {
        $this->sort= $sort;
    }

    /**
     * @return int
     */
    public function get_sort(): int {
        return $this->sort;
    }

    /**
     * @return int|null
     */
    public function get_member_status(): ?int {
        return $this->member_status;
    }

    /**
     * @param int $status
     * @return void
     */
    public function set_member_Status(int $status): void {
        $this->member_status = $status;
    }

    /**
     * Creating a query instance for the user that either be member of or owned a workspace.
     *
     * @param int|null $user_id
     * @return query
     */
    public static function create_for_user(?int $user_id = null): query {
        return new static(source::MEMBER_AND_OWNED, $user_id);
    }

    /**
     * @return int
     */
    public function get_source(): int {
        return $this->source;
    }

    /**
     * Returning the actor's id who is trying to fetch the workspace that he/she is able
     * to see or not.
     *
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * @return int
     */
    public function get_actor_id(): int {
        global $USER;

        if (null === $this->actor_id) {
            return $USER->id;
        }

        return $this->actor_id;
    }

    /**
     * @param int $actor_id
     */
    public function set_actor_id(int $actor_id): void {
        $this->actor_id = $actor_id;
    }

    /**
     * Pass NULL to this function if we want to include both to be deleted and not to be deleted
     * workspaces in the result from loader.
     *
     * @param bool|null $value
     * @return void
     */
    public function set_to_be_deleted(?bool $value): void {
        $this->to_be_deleted = $value;
    }

    /**
     * @return bool|null
     */
    public function get_to_be_deleted(): ?bool {
        return $this->to_be_deleted;
    }
}