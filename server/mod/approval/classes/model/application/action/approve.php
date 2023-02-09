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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\application\action;

use coding_exception;
use core\orm\query\builder;
use lang_string;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\activity\level_approved;
use mod_approval\model\application\activity\stage_all_approved;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_activity;
use mod_approval\model\workflow\interaction\transition\next;
use mod_approval\model\workflow\interaction\transition\transition_base;
use mod_approval\model\workflow\stage_type\approvals;

/**
 * 1: Approve
 */
final class approve extends action {
    public static function get_code(): int {
        return 1;
    }

    public static function get_enum(): string {
        return 'APPROVE';
    }

    public static function get_label(): lang_string {
        return new lang_string('model_application_action_status_approved', 'mod_approval');
    }

    public static function is_actionable(application_interactor $interactor): bool {
        return $interactor->can_approve();
    }

    /**
     * Perform the "approve" action.
     *
     * Does the following:
     * - creates an "approve" action record
     * - creates an "approved" activity record
     * - if there is another approval level, then move to that level and record activities
     * - if there is no further level, then move to the next stage (or finished) and record activities
     *
     * @param application $application
     * @param int $actor_id
     */
    public static function execute(application $application, int $actor_id): void {
        $current_state = $application->current_state;

        if (!$current_state->is_stage_type(approvals::get_code())) {
            throw new coding_exception('Cannot approve application because the state is not in approvals');
        }

        builder::get_db()->transaction(function () use ($application, $current_state, $actor_id) {
            // Record the action first - records state before any transition occurs.
            application_action::create(
                $application,
                $actor_id,
                new self()
            );

            application_activity::create(
                $application,
                $actor_id,
                level_approved::class
            );

            // Depending on the current approval level, update application state.
            $last_level = $current_state->get_stage()->approval_levels->last();
            if ($current_state->get_approval_level_id() === $last_level->id) {
                application_activity::create(
                    $application,
                    $actor_id,
                    stage_all_approved::class
                );
                // Use standard stage transition.
                $new_state = $current_state->get_stage()->state_manager->get_new_state(new self(), $application);
            } else {
                // Transition to next approval level using get_next_state instead.
                $new_state = $current_state->get_stage()->state_manager->get_next_state($application->current_state);
            }
            $application->change_state($new_state, $actor_id);
        });
    }

    public static function get_default_transition(): transition_base {
        return new next();
    }
}
