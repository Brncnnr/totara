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

namespace mod_approval\webapi\resolver\query;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\model_exception;
use mod_approval\form_schema\form_schema;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\application;
use mod_approval\model\assignment\assignment;
use mod_approval\model\form\approvalform_base;
use mod_approval\model\form\form_data;
use mod_approval\model\form\form_contents;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\webapi\middleware\require_assignment;

/**
 * Form data for particular application at particular stage
 */
class application_form_schema extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {

        /** @var application $application */
        $application = $args['application'];
        $user = user::logged_in();
        $application_interactor = $application->get_interactor($user->id);

        /** @var assignment $assignment */
        $assignment = $args['assignment'];
        $context = $assignment->get_context();
        $ec->set_relevant_context($context);

        if (!$application_interactor->can_view()) {
            throw access_denied_exception::assignment('Cannot access application');
        }

        if (empty($args['full_schema'])) {
            $purpose = form_contents::EDIT;
            if ($application_interactor->has_edit_full_application()) {
                $purpose = form_contents::ADMINEDIT;
            }
        } else {
            $purpose = form_contents::VIEW;
        }
        $result = form_contents::generate_from_application($application, $user, $purpose);
        return (object)[
            'form_schema' => $result->get_form_schema_as_json(),
            'form_data' => $result->get_form_data_as_json(),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_assignment::by_input_application_id(),
        ];
    }
}
