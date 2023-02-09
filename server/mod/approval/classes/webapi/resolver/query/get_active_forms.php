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
 * @package @mod_approval
 */

namespace mod_approval\webapi\resolver\query;

use container_approval\approval as container_approval;
use context_user;
use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use mod_approval\data_provider\form\form as form_provider;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\category_interactor;

/**
 * Paginated forms query.
 */
class get_active_forms extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['query_options'];
        $user = user::logged_in();
        $user_context = context_user::instance($user->id);
        $ec->set_relevant_context($user_context);

        $category_interactor = new category_interactor(
            container_approval::get_default_category_context(),
            $user->id
        );
        if (!$category_interactor->can_create_workflow()) {
            throw access_denied_exception::workflow();
        }

        return (new form_provider())
            ->add_filters($input['filters'] ?? [])
            ->sort_by('title')
            ->get_page(
                $input['pagination']['page'] ?? 1,
                $input['pagination']['limit'] ?? 10,
            );
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
