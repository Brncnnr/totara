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
use mod_approval\model\application\application;
use mod_approval\model\form\approvalform_base;
use mod_approval\model\workflow\workflow_stage;

/**
 * @internal Do not use this class from outside the module.
 */
abstract class form_schema_merger {
    /** @var application */
    private $application;

    /** @var user */
    private $user;

    /** @var approvalform_base */
    private $plugin;

    /** @var form_schema */
    private $form_schema_base;

    /** @var form_schema */
    private $form_schema_merged;

    /** @var form_data_merger */
    private $form_data_merger;

    /**
     * Constructor.
     *
     * @param application $application
     * @param user $user
     * @param form_data_merger $form_data_merger
     */
    public function __construct(application $application, user $user, form_data_merger $form_data_merger) {
        $this->application = $application;
        $this->user = $user;
        $this->plugin = approvalform_base::from_plugin_name($application->form_version->form->plugin_name);
        $this->form_schema_base = form_schema::from_form_version($application->form_version);
        $this->form_schema_merged = form_schema::create_empty($this->form_schema_base);
        $this->form_data_merger = $form_data_merger;
    }

    /**
     * @return form_schema merged schema
     */
    final public function get_result(): form_schema {
        return $this->form_schema_merged;
    }

    /**
     * Process/merge form schema for the particular stage.
     *
     * @param workflow_stage $stage
     */
    final public function process(workflow_stage $stage): void {
        // Get the schema as filtered by the formviews for this stage.
        $stage_schema = $this->form_schema_base->apply_formviews($stage->formviews);
        // Let the approvalform subplugin adjust the form_schema.
        $interactor = $this->application->get_interactor($this->user->id);
        $stage_schema = $this->plugin->adjust_form_schema_for_application($interactor, $stage_schema);
        // WARNING: assigning new $stage_schema is meaningless because adjust_form_schema_for_application
        // is a destructive operation, which already alters $stage_schema before returning it.
        $this->form_schema_merged = $this->process_form_schema($this->form_schema_merged, $stage_schema, $stage);
        $this->form_data_merger->process($stage_schema, $stage);
    }

    /**
     * Finish processing.
     */
    final public function finalise(): void {
        $this->form_schema_merged = $this->finalise_form_schema($this->form_schema_merged, $this->form_schema_base);
        $this->form_data_merger->finalise($this->form_schema_merged);
    }

    /**
     * @return application
     */
    final protected function get_application(): application {
        return $this->application;
    }

    /**
     * Override this method for schema processing on a particular stage.
     *
     * @param form_schema $merged_schema
     * @param form_schema $stage_schema
     * @param workflow_stage $stage
     * @return form_schema return $merged_schema if nothing is required
     */
    abstract protected function process_form_schema(form_schema $merged_schema, form_schema $stage_schema, workflow_stage $stage): form_schema;

    /**
     * Override this method to provide final processing.
     *
     * @param form_schema $merged_schema processed schema
     * @param form_schema $base_schema unfiltered schema defined in form_version
     * @return form_schema return $merged_schema if nothing is required
     */
    abstract protected function finalise_form_schema(form_schema $merged_schema, form_schema $base_schema): form_schema;
}
