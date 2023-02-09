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
use mod_approval\model\workflow\interaction\transition\provider;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\webapi\middleware\require_workflow;

/**
 * A workflow stage.
 */
class workflow_stage_move_to extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        // Check that user has capability to manage transactions.
        /** @var workflow $workflow */
        $workflow = $args['workflow'];
        if (!$workflow->get_interactor(user::logged_in()->id)->has_manage_transitions_capability()) {
            throw access_denied_exception::workflow('Cannot manage transition in this workflow');
        }

        /** @var workflow_stage $workflow_stage */
        $workflow_stage = $args['workflow_stage'];

        $provider = new provider();

        return [
            'options' => $provider->get_resolver_options_for_stage($workflow_stage),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_workflow::by_input_workflow_stage_id(true),
        ];
    }
}