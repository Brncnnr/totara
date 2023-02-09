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
 * @package totara_engage
 */
namespace totara_engage\query;

use core\pagination\offset_cursor;

class user_query {
    /**
     * @var int
     */
    private $context_id;

    /**
     * @var array
     */
    private $exclude_users;

    /**
     * @var string|null
     */
    private $search_term;

    /**
     * @var bool
     */
    private $include_deleted;

    /**
     * To tell the loader whether we should include the confirmed users.
     * @var bool
     */
    private $include_confirmed;

    /**
     * @var bool
     */
    private $include_suspended;

    /**
     * @var offset_cursor|null
     */
    private $cursor;

    /**
     * @var user_tenant_query
     */
    private $tenant_query;

    /**
     * user_query constructor.
     * @param int                       $context_id
     * @param user_tenant_query|null    $tenant_query
     */
    public function __construct(int $context_id, ?user_tenant_query $tenant_query = null) {
        if (null === $tenant_query) {
            $tenant_query = new user_tenant_query();
        }

        $this->context_id = $context_id;
        $this->search_term = null;

        $this->include_deleted = false;
        $this->include_suspended = false;
        $this->include_confirmed = true;

        $this->cursor = null;
        $this->tenant_query = $tenant_query;
        $this->exclude_users = [];
    }

    /**
     * @param int $context_id
     * @param user_tenant_query|null $tenant_query
     *
     * @return user_query
     */
    public static function create_with_exclude_guest_user(int $context_id,
                                                          ?user_tenant_query $tenant_query = null): user_query {
        $query = new static($context_id, $tenant_query);
        $query->exclude_guest_user();

        return $query;
    }

    /**
     * @param int $user_id
     * @return void
     */
    public function exclude_user(int $user_id): void {
        $this->exclude_users[] = $user_id;
    }

    /**
     * @param int[] $user_ids
     * @return void
     */
    public function exclude_users(array $user_ids): void {
        foreach ($user_ids as $user_id) {
            $this->exclude_user($user_id);
        }
    }

    /**
     * @return void
     */
    public function clear_exclude_user(): void {
        $this->exclude_users = [];
    }

    /**
     * @return int[]
     */
    public function get_exclude_users(): array {
        return $this->exclude_users;
    }

    /**
     * We are excluding guest user by default
     * @return void
     */
    public function exclude_guest_user(): void {
        global $CFG;
        $this->exclude_user($CFG->siteguest);
    }

    /**
     * @return user_tenant_query
     */
    public function get_tenant_query(): user_tenant_query {
        return $this->tenant_query;
    }

    /**
     * @return bool
     */
    public function is_including_system_user(): bool {
        return $this->tenant_query->is_including_system_user();
    }

    /**
     * @return bool
     */
    public function is_including_participant(): bool {
        return $this->tenant_query->is_including_participant();
    }

    /**
     * @param offset_cursor $cursor
     * @return void
     */
    public function set_cursor(offset_cursor $cursor): void {
        $this->cursor = $cursor;
    }

    /**
     * @return offset_cursor
     */
    public function get_cursor(): offset_cursor {
        if (null === $this->cursor) {
            $this->cursor = new offset_cursor();
        }

        return $this->cursor;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function include_deleted(bool $value = true): void {
        $this->include_deleted = $value;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function include_suspended(bool $value = true): void {
        $this->include_suspended = $value;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function include_confirmed(bool $value): void {
        $this->include_confirmed = $value;
    }

    /**
     * @return bool
     */
    public function is_including_deleted(): bool {
        return $this->include_deleted;
    }

    /**
     * @return bool
     */
    public function is_including_suspended(): bool {
        return $this->include_suspended;
    }

    /**
     * @return bool
     */
    public function is_including_confirmed(): bool {
        return $this->include_confirmed;
    }

    /**
     * @return int
     */
    public function get_context_id(): int {
        return $this->context_id;
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
}