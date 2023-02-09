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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\application\action;

use coding_exception;
use core\orm\query\builder;
use lang_string;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\activity\approvals_reset;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_activity;
use mod_approval\model\workflow\interaction\transition\reset;
use mod_approval\model\workflow\interaction\transition\transition_base;
use mod_approval\model\workflow\stage_type\approvals;

/**
 * 5: Publish
 */
final class reset_approvals extends action {
    public static function get_code(): int {
        return 6;
    }

    public static function get_enum(): string {
        return 'RESET_APPROVALS';
    }

    public static function get_label(): lang_string {
        return new lang_string('model_application_action_status_approvals_reset', 'mod_approval');
    }

    public static function is_actionable(application_interactor $interactor): bool {
        return $interactor->can_edit();
    }

    /**
     * Reset approvals.
     *
     * Does the following:
     * - marks existing approvals for the current stage as superseded
     * - records an "approvals reset" activity record
     * - moves the application to the first approval level
     *
     * @param application $application
     * @param int $actor_id
     */
    public static function execute(
        application $application,
        int $actor_id
    ): void {
        $current_state = $application->current_state;
        if (!$current_state->is_stage_type(approvals::get_code())) {
            throw new coding_exception('Cannot reset approvals when not in approvals');
        }

        builder::get_db()->transaction(function () use ($application, $current_state, $actor_id) {
            // Supersede only all actions for the current stage.
            application_action::supercede_actions_for_stage($application, $current_state->get_stage());

            // Record the activity.
            application_activity::create(
                $application,
                $actor_id,
                approvals_reset::class
            );

            // Update the application state.
            $new_state = $current_state->get_stage()->state_manager->get_new_state(new self(), $application);
            $application->change_state($new_state, $actor_id);
        });
    }

    public static function get_default_transition(): transition_base {
        return new reset();
    }
}
