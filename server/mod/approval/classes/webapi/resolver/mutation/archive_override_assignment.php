<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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

namespace mod_approval\webapi\resolver\mutation;

use core\entity\user as user_entity;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\assignment;
use mod_approval\webapi\middleware\require_assignment;

/**
 * Archive an override assignment mutation.
 */
class archive_override_assignment extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        /** @var assignment $assignment*/
        $assignment = $args['assignment'];

        if ($assignment->is_default) {
            throw new model_exception("Can not archive default assignment");
        }

        $worklfow_interactor = $assignment->workflow->get_interactor(user_entity::logged_in()->id);

        if (!$worklfow_interactor->has_manage_assignment_overrides_capability()) {
            throw access_denied_exception::application('Cannot archive assignment for the given workflow');
        }

        $assignment->archive();

        return [
            'success' => $assignment->is_archived(),
            'assignment' => $assignment,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_assignment::by_input_assignment_id(),
        ];
    }
}