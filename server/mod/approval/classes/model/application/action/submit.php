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
use mod_approval\model\application\activity\stage_submitted;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_activity;
use mod_approval\model\workflow\interaction\transition\next;
use mod_approval\model\workflow\interaction\transition\transition_base;
use mod_approval\model\workflow\stage_type\form_submission;

/**
 * 4: Submit
 */
final class submit extends action {
    public static function get_code(): int {
        return 4;
    }

    public static function get_enum(): string {
        return 'SUBMIT';
    }

    public static function get_label(): lang_string {
        return new lang_string('model_application_action_status_submitted', 'mod_approval');
    }

    public static function is_actionable(application_interactor $interactor): bool {
        return $interactor->can_edit();
    }

    /**
     * Submit an application.
     *
     * Does the following:
     * - marks any other actions on the current stage (such as previous rejections) as superseded
     * - if the application has never been submitted then mark the application as submitted (record date and user)
     * - record a "stage submitted" activity record
     * - change the application state to the next state, most likely approvals, and record activities
     *
     * @param application $application
     * @param int $actor_id
     */
    public static function execute(application $application, int $actor_id): void {
        $current_state = $application->current_state;
        if (!$current_state->is_stage_type(form_submission::get_code())) {
            throw new coding_exception('Cannot submit application because the state is not before submission');
        }

        builder::get_db()->transaction(function () use ($application, $current_state, $actor_id) {
            // Mark any actions which might have caused us to get to this state as superseded.
            application_action::supercede_actions_for_stage($application, $current_state->get_stage());

            // Mark the application submitted if this is the first submission.
            if (!$application->is_submitted()) {
                $application->mark_submitted($actor_id);
            }

            application_activity::create(
                $application,
                $actor_id,
                stage_submitted::class
            );

            // Update the application state.
            $new_state = $current_state->get_stage()->state_manager->get_new_state(new self(), $application);
            $application->change_state($new_state, $actor_id);

            // Mark any pre-existing actions in the next stage as superseded.
            if ($current_state->get_stage_id() !== $new_state->get_stage_id()) {
                application_action::supercede_actions_for_stage($application, $new_state->get_stage());
            }
        });
    }

    public static function get_default_transition(): transition_base {
        return new next();
    }
}
