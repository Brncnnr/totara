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

namespace mod_approval\webapi\resolver\query;

use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\application\application;
use mod_approval\webapi\middleware\require_assignment;

/**
 * Query to load an application.
 *
 * @package mod_approval\webapi\resolver\query
 */
class load_application extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $user = user::logged_in();

        /** @var application $application*/
        $application = $args['application'];

        if (!$application->get_interactor($user->id)->can_view()) {
            throw access_denied_exception::application();
        }

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($application->get_context());
        }

        return [
            'application' => $application
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
