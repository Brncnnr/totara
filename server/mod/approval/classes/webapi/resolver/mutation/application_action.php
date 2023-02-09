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

namespace mod_approval\webapi\resolver\mutation;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use invalid_parameter_exception;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\application\action\action;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action as application_action_model;
use mod_approval\webapi\middleware\require_assignment;

/**
 * application_action mutation.
 */
final class application_action extends mutation_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec): array {
        /** @var application $application */
        $application = $args['application'];
        $args = $args['input'];

        $user = user::logged_in();

        if (empty($args['action'])) {
            throw new invalid_parameter_exception('Missing action');
        }

        $action = action::from_enum($args['action']);

        if (!$action->is_actionable($application->get_interactor($user->id))) {
            throw access_denied_exception::application('Cannot take an action');
        }

        $action->execute($application, $user->id);

        // Reload the application to get new state.
        $application->refresh(true);

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($application->get_context());
        }
        return ['application' => $application];
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
