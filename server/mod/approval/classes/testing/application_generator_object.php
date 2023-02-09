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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\testing;

use coding_exception;
use mod_approval\entity\workflow\workflow_stage;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\model\application\application_state;

/**
 * Class application_generator_object
 *
 * Provides a structured interface for passing properties to the application generator.
 *
 * @package mod_approval\testing
 */
final class application_generator_object {
    public $user_id;
    public $job_assignment_id;
    public $workflow_version_id;
    public $form_version_id;
    public $assignment_id;
    public $creator_id;
    public $current_state;
    public $submitted;
    public $completed;
    public $title;

    /**
     * Application_generator_object constructor, captures required properties.
     *
     * @param int $workflow_version_id
     * @param int $form_version_id;
     * @param int $assignment_id;
     */
    public function __construct(int $workflow_version_id, int $form_version_id, int $assignment_id) {
        if (!workflow_stage::repository()->where('workflow_version_id', $workflow_version_id)->exists()) {
            throw new coding_exception('Workflow version must have at least one stage.');
        }
        $this->workflow_version_id = $workflow_version_id;
        $this->form_version_id = $form_version_id;
        $this->assignment_id = $assignment_id;

        $workflow_version = new workflow_version($workflow_version_id);
        $this->current_state = new application_state($workflow_version->stages->first()->id, true);
    }

    /**
     * Enables the (potentially problematic) faking of a submitted application.
     *
     * If testing applications in states other than draft you should be using model methods that
     * trigger events to update application state.
     *
     * @param null $time
     */
    public function fake_submitted($time = null) {
        $this->current_state = new application_state(
            $this->current_state->get_stage_id(),
            false,
            $this->current_state->get_stage()->approval_levels->first()->id
        );

        if (is_null($time)) {
            $time = time();
        }
        $this->submitted = $time;
    }
}
