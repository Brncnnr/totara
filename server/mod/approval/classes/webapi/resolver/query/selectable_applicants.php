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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\query;

use container_approval\approval;
use core\entity\user;
use core\pagination\base_paginator;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use mod_approval\data_provider\user\selectable_applicants_for_category;
use mod_approval\data_provider\user\selectable_applicants_for_workflow;
use mod_approval\model\workflow\workflow;
use moodle_exception;

/**
 * Get a list of users that can be used to create an application on behalf.
 *
 * @package mod_approval\webapi\resolver\query
 */
class selectable_applicants extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];
        $user = user::logged_in();

        // Basic capability check.
        if (!self::can_create_application_on_behalf($user->id)) {
            throw new moodle_exception('Cannot create applications on behalf');
        }

        if (!empty($input['workflow_id'])) {
            $id = (int)$input['workflow_id'];
            $workflow = workflow::load_by_id($id);
            $ec->set_relevant_context($workflow->get_context());
            $provider = new selectable_applicants_for_workflow($workflow, $user->id);
        } else {
            $ec->set_relevant_context(approval::get_default_category_context());
            $provider = new selectable_applicants_for_category($user->id);
        }

        $cursor = $input['pagination']['cursor'] ?? null;
        $limit =  $input['pagination']['limit'] ?? base_paginator::DEFAULT_ITEMS_PER_PAGE;
        $filters = $input['filters'] ?? [];

        return $provider
            ->add_filters($filters)
            ->get_page($cursor, $limit);
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

    /**
     * Can create an application on behalf.
     * @param int $user_id
     * @return bool
     */
    private static function can_create_application_on_behalf(int $user_id): bool {
        return has_capability_in_any_context('mod/approval:create_application_any', null, $user_id) ||
            has_capability_in_any_context('mod/approval:create_application_user', null, $user_id);
    }
}