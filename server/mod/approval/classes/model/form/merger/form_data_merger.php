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

namespace mod_approval\model\form\merger;

use core\entity\user;
use mod_approval\form_schema\form_schema;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\application;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\workflow_stage;

/**
 * @internal Do not use this class from outside the module.
 */
abstract class form_data_merger {
    /** @var application */
    private $application;

    /** @var user */
    private $user;

    /** @var form_data */
    private $form_data_merged;

    /**
     * Constructor.
     *
     * @param application $application
     * @param user $user
     */
    public function __construct(application $application, user $user) {
        $this->application = $application;
        $this->user = $user;
        $this->form_data_merged = form_data::create_empty();
    }

    /**
     * @return form_data
     */
    final public function get_result(): form_data {
        return $this->form_data_merged;
    }

    /**
     * Process/merge form data for the particular stage.
     *
     * @param form_schema $form_schema
     * @param workflow_stage $stage
     */
    final public function process(form_schema $form_schema, workflow_stage $stage): void {
        // Get the last submission and apply it to the schema.
        $last_submission = $this->application->get_last_submission_for($stage->id);
        if ($last_submission === null) {
            // No submission, no data.
            return;
        }

        $last_form_data = form_data::from_instance($last_submission);
        $interactor = $this->application->get_interactor($this->user->id);
        $this->form_data_merged = $this->process_form_data($this->form_data_merged, $last_form_data, $form_schema, $interactor);
    }

    /**
     * Finish processing.
     *
     * @param form_schema $form_schema
     */
    final public function finalise(form_schema $form_schema): void {
        $interactor = $this->application->get_interactor($this->user->id);
        $this->form_data_merged = $this->finalise_form_data($this->form_data_merged, $form_schema, $interactor);
    }

    /**
     * Override this method for data processing on a particular stage.
     *
     * @param form_data $merged_form_data
     * @param form_data $form_data
     * @param form_schema $form_schema
     * @param application_interactor $interactor
     * @return form_data return $merged_form_data if nothing is required
     */
    abstract protected function process_form_data(form_data $merged_form_data, form_data $form_data, form_schema $form_schema, application_interactor $interactor): form_data;

    /**
     * Override this method to provide final processing.
     *
     * @param form_data $form_data
     * @param form_schema $form_schema
     * @param application_interactor $interactor
     * @return form_data return $form_data if nothing is required
     */
    abstract protected function finalise_form_data(form_data $form_data, form_schema $form_schema, application_interactor $interactor): form_data;
}
