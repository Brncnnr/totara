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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\interactor\workspace;

use context_coursecat;

/**
 * A helper class that is constructed with the workspace's category context (or default if it doesn't exist yet)
 * and the user's id, which helps to fetch all the available actions that a user can interact with a workspace
 * at the category level.
 *
 * The main purpose is to expose whether a user can create workspaces & what types are available
 */
final class category_interactor {
    /**
     * The workspace category context to check against.
     *
     * @var context_coursecat
     */
    private $category_context;

    /**
     * The user's id that act as an actor interact with the workspace.
     *
     * @var int
     */
    private $user_id;

    /**
     * @param context_coursecat $context
     * @param int|null $user_id If null is set for this field, then user in session will be used.
     */
    public function __construct(context_coursecat $context, ?int $user_id = null) {
        global $USER;

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $this->category_context = $context;
        $this->user_id = $user_id;
    }

    /**
     * @param int $category_id
     * @param int|null $user_id
     *
     * @return category_interactor
     */
    public static function from_category_id(int $category_id, ?int $user_id = null): category_interactor {
        $context = \context_coursecat::instance($category_id);

        return new static($context, $user_id);
    }

    /**
     * Can create public workspaces
     *
     * @return bool
     */
    public function can_create_public(): bool {
        return has_capability('container/workspace:create', $this->category_context, $this->user_id);
    }

    /**
     * Can create private workspaces
     *
     * @return bool
     */
    public function can_create_private(): bool {
        return has_capability('container/workspace:createprivate', $this->category_context, $this->user_id);
    }

    /**
     * Can create hidden workspaces
     *
     * @return bool
     */
    public function can_create_hidden(): bool {
        return has_capability('container/workspace:createhidden', $this->category_context, $this->user_id);
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * @return context_coursecat
     */
    public function get_context(): context_coursecat {
        return $this->category_context;
    }
}