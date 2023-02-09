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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\model\workflow\stage_type\state_manager;

use mod_approval\exception\model_exception;
use mod_approval\model\application\activity\level_ended;
use mod_approval\model\application\activity\level_started;
use mod_approval\model\application\activity\stage_ended;
use mod_approval\model\application\activity\stage_started;
use mod_approval\model\application\application;
use mod_approval\model\application\application_activity;
use mod_approval\model\application\application_state;
use mod_approval\model\workflow\stage_type\approvals as approvals_stage_type;

/**
 * Handles state of applications in an approvals stage type.
 */
class approvals extends base {

    /**
     * @inheritDoc
     */
    protected $stage_type = approvals_stage_type::class;

    /**
     * @inheritDoc
     */
    public function get_next_state(application_state $state): application_state {
        $stage = $state->get_stage();

        $current_stage_id = $state->get_stage_id();
        $current_approval_level = $state->get_approval_level_id();
        $next_approval_level = $stage->feature_manager->approval_levels->get_next($current_approval_level);

        // Application is moving to the next approval level within the same stage.
        if (!is_null($next_approval_level)) {
            return new application_state(
                $stage->id,
                false,
                $next_approval_level->id
            );
        } else {
            // Application is at last approval level, moving to the next stage
            $workflow_version = $stage->workflow_version;
            $next_stage = $workflow_version->get_next_stage($current_stage_id);
            return $next_stage->state_manager->get_initial_state();
        }
    }

    /**
     * @inheritDoc
     */
    public function get_previous_state(application_state $state): application_state {
        $stage = $state->get_stage();

        $workflow_version = $stage->workflow_version;
        $previous_stage = $workflow_version->get_previous_stage($stage->id);

        if (is_null($previous_stage)) {
            throw new model_exception("No previous stage");
        }

        return $previous_stage->state_manager->get_initial_state();
    }

    /**
     * @inheritDoc
     */
    public function get_initial_state(): application_state {
        return new application_state(
            $this->workflow_stage->id,
            false,
            $this->workflow_stage->feature_manager->approval_levels->get_first()->id,
        );
    }

    /**
     * @inheritDoc
     */
    public function get_creation_state(): application_state {
        throw new model_exception('An application can not start in an approval stage');
    }

    /**
     * @inheritDoc
     */
    public function on_application_start(application $application, int $actor_id, $info = []): void {
        throw new model_exception('An application can not start in an approval stage');
    }

    /**
     * @inheritDoc
     */
    public function on_state_entry(application $application, application_state $old_state, ?int $actor_id): void {
        $old_stage = $old_state->get_stage();

        $current_state = $application->current_state;
        $current_stage = $current_state->get_stage();

        if ($old_stage->id !== $current_stage->id) {
            application_activity::create(
                $application,
                $actor_id,
                stage_started::class
            );
        }

        application_activity::create(
            $application,
            $actor_id,
            level_started::class
        );
    }

    /**
     * @inheritDoc
     */
    public function on_state_exit(application $application, application_state $new_state, ?int $actor_id): void {
        application_activity::create(
            $application,
            $actor_id,
            level_ended::class
        );
        $current_state = $application->current_state;
        $current_stage = $current_state->get_stage();

        $new_stage = $new_state->get_stage();

        if ($new_stage->id !== $current_stage->id) {
            application_activity::create(
                $application,
                $actor_id,
                stage_ended::class
            );
        }
    }
}