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
use mod_approval\model\application\activity\withdrawn;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_activity;
use mod_approval\model\application\application_submission;
use mod_approval\model\workflow\interaction\transition\reset;
use mod_approval\model\workflow\interaction\transition\transition_base;
use mod_approval\model\workflow\stage_type\form_submission;

/**
 * 3: Withdraw before submission
 */
final class withdraw_before_submission extends action {
    public static function get_code(): int {
        return 3;
    }

    public static function get_enum(): string {
        return 'WITHDRAW_BEFORE_SUBMISSION';
    }

    public static function get_label(): lang_string {
        return new lang_string('model_application_action_status_withdrawn', 'mod_approval');
    }

    public static function is_actionable(application_interactor $interactor): bool {
        return $interactor->can_withdraw();
    }

    /**
     * Withdraw an application while it is before submission.
     *
     * Does the following:
     * - marks existing actions as superseded
     * - marks existing submissions as superseded and clones the latest submission for the actor
     * - records a "withdraw before submission" action record
     * - records a "withdrawn" activity record
     *
     * @param application $application
     * @param int $actor_id
     */
    public static function execute(application $application, int $actor_id): void {
        $current_state = $application->current_state;
        if ($current_state->is_draft() || !$current_state->is_stage_type(form_submission::get_code())) {
            throw new coding_exception(
                'Cannot withdraw application before submission because the state is not before submission'
            );
        }

        if (!($application->last_action && $application->last_action->code == reject::get_code())) {
            throw new coding_exception('Cannot withdraw application before submission because it was not rejected');
        }

        builder::get_db()->transaction(function () use ($application, $current_state, $actor_id) {
            // Mark all existing actions and submissions as superseded.
            application_action::supercede_actions_for_stage($application, $current_state->get_stage());
            application_submission::supersede_submissions_for_stage($application, $current_state->get_stage(), $actor_id);

            // Record the action first.
            application_action::create(
                $application,
                $actor_id,
                new self()
            );

            // Record the activity.
            application_activity::create(
                $application,
                $actor_id,
                withdrawn::class
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
