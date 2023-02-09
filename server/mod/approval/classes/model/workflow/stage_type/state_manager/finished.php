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

use coding_exception;
use mod_approval\exception\model_exception;
use mod_approval\model\application\activity\finished as finished_activity;
use mod_approval\model\application\application;
use mod_approval\model\application\application_activity;
use mod_approval\model\application\application_state;
use mod_approval\model\workflow\stage_type\finished as finished_stage_type;

/**
 * Handles state of applications in a finished stage type.
 */
class finished extends base {

    /**
     * @inheritDoc
     */
    protected $stage_type = finished_stage_type::class;

    /**
     * @inheritDoc
     */
    public function get_next_state(application_state $state): application_state {
        throw new coding_exception("Finished stages do not have next states");
    }

    /**
     * @inheritDoc
     */
    public function get_previous_state(application_state $state): application_state {
        throw new coding_exception("Finished stages do not have previous stages");
    }

    /**
     * @inheritDoc
     */
    public function get_initial_state(): application_state {
        return new application_state($this->workflow_stage->id);
    }

    /**
     * @inheritDoc
     */
    public function get_creation_state(): application_state {
        throw new model_exception('An application can not start in a finished stage');
    }

    /**
     * @inheritDoc
     */
    public function on_application_start(application $application, int $actor_id, $info = []): void {
        throw new model_exception('An application can not start in a finished stage');
    }

    /**
     * @inheritDoc
     */
    public function on_state_entry(application $application, application_state $old_state, ?int $actor_id): void {
        $application->mark_completed();
        application_activity::create(
            $application,
            $actor_id,
            finished_activity::class
        );
    }

    /**
     * @inheritDoc
     */
    public function on_state_exit(application $application, application_state $new_state, ?int $actor_id): void {
        throw new coding_exception('Exiting a finished state not implemented');
    }
}