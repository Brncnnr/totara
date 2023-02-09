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

namespace mod_approval\webapi\resolver\mutation;

use core\entity\user;
use core\entity\user as user_entity;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\application\application;
use mod_approval\webapi\middleware\require_assignment;

/**
 * application_delete mutation.
 */
final class application_delete extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec): bool {
        $input = $args['input'];
        $actor = user_entity::logged_in();

        /** @var application $application */
        $application = $args['application'];
        if (!$application->get_interactor($actor->id)->can_delete()) {
            throw access_denied_exception::application('Cannot delete application');
        }
        $application->delete();
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            // Change it using require_application middleware TL-30926
            require_assignment::by_input_application_id()
        ];
    }
}
