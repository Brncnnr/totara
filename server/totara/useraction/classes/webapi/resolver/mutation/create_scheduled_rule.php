<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use core_text;
use totara_useraction\action\factory as action_factory;
use totara_useraction\exception\invalid_action_exception;
use totara_useraction\exception\missing_name_field_exception;
use totara_useraction\filter\applies_to;
use totara_useraction\filter\duration;
use totara_useraction\filter\factory as filter_factory;
use totara_useraction\filter\status;
use totara_useraction\model\scheduled_rule;
use totara_useraction\webapi\middleware\require_capability;

/**
 * Mutation to create a single scheduled rule.
 */
class create_scheduled_rule extends mutation_resolver {
    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            require_capability::from_tenant_id('input.tenant_id'),
        ];
    }

    /**
     * @param array $args
     * @param execution_context $ec
     * @return scheduled_rule
     */
    public static function resolve(array $args, execution_context $ec): scheduled_rule {
        $input = $args['input'] ?? [];

        // At this point we have validated the user & tenant id has the capability in the current context.
        $tenant_id = $input['tenant_id'] ?? null;
        $name = $input['name'];
        $description = $input['description'] ?? '';
        $status = $input['status'] ?? false;
        $action = $input['action'] ?? null;
        $filter_status = $input['filter_user_status'] ?? null;
        $filter_duration = $input['filter_duration'] ?? [];
        $filter_applies_to = $input['filter_applies_to'] ?? [];

        if (core_text::strlen($name) === 0) {
            throw new missing_name_field_exception();
        }
        if (!action_factory::is_valid($action)) {
            throw new invalid_action_exception();
        }

        // Expected filters
        // Our list of acceptable filters is limited, so we hard-code here
        $filter_status = filter_factory::create(status::class, $filter_status, true);
        $filter_duration = filter_factory::create(duration::class, $filter_duration, true);
        $filter_applies_to = filter_factory::create(applies_to::class, $filter_applies_to, true);

        return scheduled_rule::create(
            $name,
            $action,
            $filter_status,
            $filter_duration,
            $filter_applies_to,
            $description,
            $tenant_id,
            $status
        );
    }
}
