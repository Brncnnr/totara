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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_approval\exception\helper\validation;
use mod_approval\model\application\application_state as application_state_model;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;

/**
 * Resolves mod_approval_workflow_interactor
 */
final class application_state extends type_resolver {
    /**
     * @param string $field
     * @param application_state_model|object $state
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $state, array $args, execution_context $ec) {
        validation::instance_of($state, application_state_model::class);
        if ($field === 'stage') {
            return $state->get_stage();
        }
        if ($field === 'approval_level') {
            return $state->get_approval_level();
        }
        if ($field === 'is_before_submission') {
            return $state->is_stage_type(form_submission::get_code());
        }
        if ($field === 'is_in_approvals') {
            return $state->is_stage_type(approvals::get_code());
        }
        if ($field === 'is_finished') {
            return $state->is_stage_type(finished::get_code());
        }

        if (!method_exists($state, $field)) {
            throw new coding_exception('Tried to access unknown field: ' . $field);
        }

        return $state->$field();
    }
}
