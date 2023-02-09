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
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use mod_approval\model\assignment\assignment_resolver;
use mod_approval\model\workflow\workflow_type;

/**
 * A list of the approval workflows to which the current user can apply.
 */
class create_new_application_menu extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $context = approval::get_default_category_context();
        $ec->set_relevant_context($context);

        $query = $args['query'] ?? [];
        $creator = user::logged_in();
        $applicant = (!empty($query['applicant_id'])) ? new user($query['applicant_id']) : clone $creator;

        $workflow_type = null;
        if (!empty($query['workflow_type_id'])) {
            $workflow_type = workflow_type::load_by_id($query['workflow_type_id']);
        }

        $resolver = new assignment_resolver($applicant, $creator, $workflow_type);
        $resolver->resolve();
        return $resolver->get_menu_items();
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