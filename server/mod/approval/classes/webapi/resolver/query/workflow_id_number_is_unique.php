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

namespace mod_approval\webapi\resolver\query;

use container_approval\approval;
use core\entity\user as user_entity;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\category_interactor;
use mod_approval\model\workflow\workflow;
use mod_approval\webapi\middleware\require_workflow;
use invalid_parameter_exception;

/**
 * workflow_id_number_is_unique query.
 */
final class workflow_id_number_is_unique extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $ec->set_relevant_context(approval::get_default_category_context());

        if (empty(trim($args['input']['id_number']))) {
            throw new \invalid_parameter_exception('Expected id_number parameter to be a non-empty string.');
        }

        $input = $args['input'];

        $workflow_id = null;
        if (!empty($input['workflow_id'])) {
            $workflow = workflow::load_by_id($input['workflow_id']);
            $workflow_id = $workflow->id;
        }

        // Check access to edit workflows generally.
        if (!category_interactor::from_category_id(
            approval::get_default_category_id(),
            user_entity::logged_in()->id)
            ->can_manage_workflows()) {
            throw access_denied_exception::workflow('Cannot edit workflow');
        }

        return workflow::is_unique_id_number($input['id_number'], $workflow_id);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
        ];
    }
}
