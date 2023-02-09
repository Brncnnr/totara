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

use context_module;
use context_user;

/**
 * A helper class that is constructed with an approval workflow assignment's context and a user's id, which helps
 * to fetch all the available actions that the user can perform in relation to an approval workflow assignment.
 *
 * The main purpose is to expose whether a user can create applications within the approval workflow assignment.
 */
final class assignment_interactor {
    /**
     * The approval workflow assignment context to check against.
     *
     * @var context_module
     */
    private $activity_context;

    /**
     * The user id of the user who is interacting with the approval workflow assignment.
     *
     * @var int
     */
    private $applicant_user_id;

    /**
     * The applicant user context to check against.
     *
     * @var context_user
     */
    private $applicant_context;

    /**
     * The user id of the user who is interacting with the approval workflow assignment.
     *
     * @var int
     */
    private $interactor_user_id;

    /**
     * @param context_module $assignment_activity_context
     * @param int $applicant_user_id
     * @param int $interactor_user_id
     */
    public function __construct(
        context_module $assignment_activity_context,
        int $applicant_user_id,
        int $interactor_user_id
    ) {
        $this->activity_context = $assignment_activity_context;
        $this->applicant_user_id = (int)$applicant_user_id;
        $this->applicant_context = context_user::instance($applicant_user_id);
        $this->interactor_user_id = (int)$interactor_user_id;
    }

    /**
     * @param int $assignment_activity_context_id
     * @param int $applicant_user_id
     * @param int $interactor_user_id
     *
     * @return assignment_interactor
     */
    public static function from_assignment_activity_context_id(
        int $assignment_activity_context_id,
        int $applicant_user_id,
        int $interactor_user_id
    ): assignment_interactor {
        $assignment_activity_context = context_module::instance($assignment_activity_context_id);

        return new self(
            $assignment_activity_context,
            $applicant_user_id,
            $interactor_user_id
        );
    }

    /**
     * Can create an application for the applicant.
     * CA02, CU02, CO02
     *
     * @return bool
     */
    public function can_create_application(): bool {
        if (has_capability(
            'mod/approval:create_application_any',
            $this->activity_context,
            $this->interactor_user_id
        )) {
            return true;
        }

        if (has_capability(
            'mod/approval:create_application_user',
            $this->applicant_context,
            $this->interactor_user_id
        )) {
            return true;
        }

        return ($this->applicant_user_id === $this->interactor_user_id) && has_capability(
            'mod/approval:create_application_applicant',
            $this->activity_context,
            $this->interactor_user_id
        );
    }

    /**
     * @return int
     */
    public function get_interactor_user_id(): int {
        return $this->interactor_user_id;
    }

    /**
     * @return int
     */
    public function get_applicant_user_id(): int {
        return $this->applicant_user_id;
    }

    /**
     * @return context_module
     */
    public function get_context(): context_module {
        return $this->activity_context;
    }
}