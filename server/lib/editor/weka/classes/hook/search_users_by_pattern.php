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
 * @package editor_weka
 */
namespace editor_weka\hook;

use core\entity\user;
use totara_core\hook\base;
use totara_core\identifier\component_area;

/**
 * A hook to allow the plugins override searching users base on their logics.
 */
class search_users_by_pattern extends base {
    /**
     * @var component_area
     */
    private $identifier;

    /**
     * @var int
     */
    private $context_id;

    /**
     * @var user[]
     */
    private $users;

    /**
     * @var bool
     */
    private $db_run;

    /**
     * @var string
     */
    private $pattern;

    /**
     * The user's id of whoever executing this hook.
     * @var int
     */
    private $actor_id;

    /**
     * @var int|null
     */
    private $instance_id;

    /**
     * search_users_by_pattern constructor.
     * @param component_area    $identifier
     * @param string            $pattern
     * @param int               $context_id
     * @param int               $actor_id
     */
    public function __construct(component_area $identifier, string $pattern, int $context_id, int $actor_id) {
        $this->identifier = $identifier;
        $this->context_id = $context_id;
        $this->users = [];
        $this->db_run = false;
        $this->pattern = $pattern;
        $this->actor_id = $actor_id;
        $this->instance_id = null;
    }

    /**
     * @param string    $component
     * @param string    $area
     * @param string    $pattern
     * @param int       $context_id
     * @param int|null  $actor_id
     *
     * @return search_users_by_pattern
     */
    public static function create(string $component, string $area, string $pattern,
                                  int $context_id, ?int $actor_id = null): search_users_by_pattern {
        global $USER;

        if (empty($actor_id)) {
            $actor_id = $USER->id;
        }

        $identifier = new component_area($component, $area);
        return new search_users_by_pattern($identifier, $pattern, $context_id, $actor_id);
    }

    /**
     * @return int|null
     */
    public function get_instance_id(): ?int {
        return $this->instance_id;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_instance_id(int $value): void {
        $this->instance_id = $value;
    }

    /**
     * Returning the user actor who is triggering this hook.
     * @return int
     */
    public function get_actor_id(): int {
        return $this->actor_id;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->identifier->get_component();
    }

    /**
     * @return string
     */
    public function get_area(): string {
        return $this->identifier->get_area();
    }

    /**
     * @return int
     */
    public function get_context_id(): int {
        return $this->context_id;
    }

    /**
     * @return string
     */
    public function get_pattern(): string {
        return $this->pattern;
    }

    /**
     * @param user $user
     * @return void
     */
    public function add_user(user $user): void {
        $this->users[] = $user;
    }

    /**
     * @param user[] $users
     * @return void
     */
    public function add_users(array $users): void {
        foreach ($users as $user) {
            $this->add_user($user);
        }
    }

    /**
     * The watcher should set this field to say that the watcher has run query against
     * the database. This would be helpful for the actual searching users in order to know whether
     * it needs to search for the users again or not.
     *
     * @param bool $db_run
     * @return void
     */
    public function set_db_run(bool $db_run): void {
        $this->db_run = $db_run;
    }

    /**
     * @return void
     */
    public function mark_db_run(): void {
        $this->set_db_run(true);
    }

    /**
     * @return user[]
     */
    public function get_users(): array {
        return $this->users;
    }

    /**
     * Checking whether the watcher has run the database.
     *
     * @return bool
     */
    public function is_db_run(): bool {
        return $this->db_run;
    }
}