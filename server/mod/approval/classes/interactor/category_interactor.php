<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\interactor;

use context_coursecat;

/**
 * A helper class that is constructed with an approval workflow's category context (or default if it doesn't exist yet)
 * and a user's id, which helps to fetch all the available actions that the user can perform in relation to an approval
 * workflow at the category level.
 *
 * The main purpose is to expose whether a user can create approval workflows.
 */
final class category_interactor {
    /**
     * The approval workflow category context to check against.
     *
     * @var context_coursecat
     */
    private $category_context;

    /**
     * The user id of the user who is interacting with the approval workflow category.
     *
     * @var int
     */
    private $user_id;

    /**
     * @param context_coursecat $approval_category_context e.g. approval::get_default_category_context()
     * @param int|null $interactor_user_id
     */
    public function __construct(
        context_coursecat $approval_category_context,
        int $interactor_user_id
    ) {
        $this->category_context = $approval_category_context;
        $this->user_id = $interactor_user_id;
    }

    /**
     * @param int $approval_category_context_id
     * @param int $interactor_user_id
     *
     * @return category_interactor
     */
    public static function from_category_id(
        int $approval_category_context_id,
        int $interactor_user_id
    ): category_interactor {
        $approval_category_context = context_coursecat::instance($approval_category_context_id);

        return new self($approval_category_context, $interactor_user_id);
    }

    /**
     * Can create a workflow from a template.
     * CW01
     *
     * @return bool
     */
    public function can_create_workflow_from_template(): bool {
        return has_capability(
            'mod/approval:create_workflow_from_template',
            $this->category_context,
            $this->user_id
        );
    }

    /**
     * Can create a workflow.
     * CW02
     *
     * @return bool
     */
    public function can_create_workflow(): bool {
        return has_capability(
            'mod/approval:create_workflow',
            $this->category_context,
            $this->user_id
        );
    }

    /**
     * Has the clone workflow capability in this category context.
     * CW03
     *
     * @return bool
     */
    public function has_clone_workflow_capability(): bool {
        return has_capability(
            'mod/approval:clone_workflow',
            $this->category_context,
            $this->user_id
        );
    }

    /**
     * Can create a workflow template.
     * CW08
     *
     * @return bool
     */
    public function can_create_workflow_template(): bool {
        return has_capability(
            'mod/approval:create_workflow_template',
            $this->category_context,
            $this->user_id
        );
    }

    /**
     * Can move an application to a different workflow.
     * CW19
     *
     * @return bool
     */
    public function can_move_application_between_workflows(): bool {
        return has_capability(
            'mod/approval:move_application_between_workflows',
            $this->category_context,
            $this->user_id
        );
    }

    /**
     * Can manage the workflows.
     * CW20
     *
     * @return bool
     */
    public function can_manage_workflows(): bool {
        return has_capability(
            'mod/approval:manage_workflows',
            $this->category_context,
            $this->user_id
        );
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