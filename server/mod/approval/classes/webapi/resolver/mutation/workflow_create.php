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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\mutation;

use container_approval\approval as container_approval;
use core\entity\user;
use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use invalid_parameter_exception;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\assignment\assignment;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_type;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\category_interactor;
use hierarchy_organisation\entity\organisation;

/**
 * workflow_create mutation
 */
class workflow_create extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        if (!(new category_interactor(
            container_approval::get_default_category_context(),
            user::logged_in()->id
        ))->can_create_workflow()) {
            throw access_denied_exception::workflow('Cannot create workflow');
        }
        $input = $args['input'];

        if (empty($input['name'])) {
            throw new invalid_parameter_exception('name is required');
        }

        if (empty($input['workflow_type_id'])) {
            throw new invalid_parameter_exception('workflow_type_id is required');
        } else {
            $workflow_type = workflow_type::load_by_id($input['workflow_type_id']);
        }

        if (empty($input['form_id'])) {
            throw new invalid_parameter_exception('form_id is required');
        } else {
            $form = form::load_by_id($input['form_id']);
        }

        if (!isset($input['assignment_type']) || !isset($input['assignment_identifier'])) {
            $org = organisation::repository()->where('parentid', 0)->order_by('id')->first();
            if (is_null($org)) {
                throw access_denied_exception::workflow('No active organisation exists');
            }
            $input['assignment_type'] = assignment_type\organisation::get_enum();
            $input['assignment_identifier'] = $org->id;
        }

        $workflow = builder::get_db()->transaction(
            function () use (
                $workflow_type,
                $form,
                $input
            ) {
                $assignment_type = assignment_type\provider::get_by_enum($input['assignment_type']);
                $workflow = workflow::create(
                    $workflow_type,
                    $form,
                    $input['name'],
                    $input['description'] ?? '',
                    $assignment_type::get_code(),
                    $input['assignment_identifier'],
                    $input['id_number'] ?? ''
                );

                // Create the start stage.
                workflow_stage::create(
                    $workflow->get_latest_version(),
                    get_string('default_workflow_start_stage_name', 'mod_approval'),
                    form_submission::get_enum()
                );

                // Create the end stage.
                workflow_stage::create(
                    $workflow->get_latest_version(),
                    get_string('default_workflow_finished_stage_name', 'mod_approval'),
                    finished::get_enum()
                );

                return $workflow;
            }
        );

        $ec->set_relevant_context($workflow->get_context());

        return ['workflow' => $workflow->refresh(true)];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user()
        ];
    }
}