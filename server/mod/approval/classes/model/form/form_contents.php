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

namespace mod_approval\model\form;

use coding_exception;
use core\entity\user;
use core\orm\collection;
use mod_approval\form_schema\form_schema;
use mod_approval\model\application\application;
use mod_approval\model\form\merger\form_data_merger;
use mod_approval\model\form\merger\form_data_merger_edit;
use mod_approval\model\form\merger\form_data_merger_preview;
use mod_approval\model\form\merger\form_data_merger_view;
use mod_approval\model\form\merger\form_schema_merger;
use mod_approval\model\form\merger\form_schema_merger_edit;
use mod_approval\model\form\merger\form_schema_merger_preview;
use mod_approval\model\form\merger\form_schema_merger_view;
use mod_approval\model\workflow\workflow_stage;

/**
 * Deal with form contents.
 */
final class form_contents {
    const VIEW = 2;
    const EDIT = 3;
    const PREVIEW = 4;
    const ADMINEDIT = 5;

    /** @var form_schema */
    private $form_schema;

    /** @var form_data */
    private $form_data;

    /**
     * Private constructor.
     *
     * @param form_schema $form_schema
     * @param form_data $form_data
     */
    private function __construct(form_schema $form_schema, form_data $form_data) {
        $this->form_schema = $form_schema;
        $this->form_data = $form_data;
    }

    /**
     * @return form_schema
     */
    public function get_form_schema(): form_schema {
        return $this->form_schema;
    }

    /**
     * @return form_data
     */
    public function get_form_data(): form_data {
        return $this->form_data;
    }

    /**
     * @return string JSON string
     */
    public function get_form_schema_as_json(): string {
        return $this->form_schema->to_json();
    }

    /**
     * @return string JSON string
     */
    public function get_form_data_as_json(): string {
        return $this->form_data->to_json();
    }

    /**
     * Generate application form schema and form data.
     *
     * @param application $application
     * @param user $user
     * @param integer $purpose One of follows:
     *   - VIEW: generate the merged schema of all stages
     *   - EDIT: generate the schema of only the current stage
     *           (the function will blow up if the application is already finished)
     *   - PREVIEW: generate print preview contents
     * @return self
     */
    public static function generate_from_application(application $application, user $user, int $purpose): self {
        $stages = self::get_working_stages($application);
        [$form_schema_merger, $form_data_merger] = self::create_mergers($application, $user, $purpose);
        /** @var form_data_merger $form_data_merger */
        /** @var form_schema_merger $form_schema_merger */
        self::process_merger($form_schema_merger, $stages);
        return new self($form_schema_merger->get_result(), $form_data_merger->get_result());
    }

    /**
     * @param application $application
     * @param user $user
     * @param integer $purpose
     * @return array of [form_schema_merger, form_data_merger]
     */
    private static function create_mergers(application $application, user $user, int $purpose): array {
        if (!in_array($purpose, [self::VIEW, self::EDIT, self::PREVIEW, self::ADMINEDIT])) {
            throw new coding_exception('Unknown purpose: ' . $purpose);
        }
        if ($purpose === self::VIEW || $purpose === self::ADMINEDIT) {
            $form_data_merger = new form_data_merger_view($application, $user);
            $form_schema_merger = new form_schema_merger_view($application, $user, $form_data_merger);
        } else if ($purpose === self::PREVIEW) {
            $form_data_merger = new form_data_merger_preview($application, $user);
            $form_schema_merger = new form_schema_merger_preview($application, $user, $form_data_merger);
        } else {
            $form_data_merger = new form_data_merger_edit($application, $user);
            $form_schema_merger = new form_schema_merger_edit($application, $user, $form_data_merger);
        }
        return [$form_schema_merger, $form_data_merger];
    }

    /**
     * @param form_schema_merger $form_schema_merger
     * @param collection|workflow_stage[] $stages
     */
    private static function process_merger(form_schema_merger $form_schema_merger, collection $stages): void {
        /** @var workflow_stage $stage */
        foreach ($stages as $stage) {
            // Empty formviews, means there is no form to edit at this stage. Frontend should handle this.
            if ($stage->formviews->count() == 0) {
                continue;
            }
            $form_schema_merger->process($stage);
        }
        $form_schema_merger->finalise();
    }

    /**
     * Return the collection of stages that are finished or in progress.
     *
     * @param application $application
     * @return collection|workflow_stage[]
     */
    private static function get_working_stages(application $application): collection {
        $stages = $application->workflow_version->stages;
        if (!$application->current_state->get_stage_id()) {
            return clone $stages;
        }
        $current_stage_number = $application->current_stage->ordinal_number;
        return $stages->filter(function (workflow_stage $stage) use ($current_stage_number) {
            return $stage->ordinal_number <= $current_stage_number;
        });
    }
}
