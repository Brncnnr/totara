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

namespace totara_useraction\webapi\resolver\query;

use core\pagination\offset_cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use totara_useraction\data_provider\scheduled_rule;
use totara_useraction\entity\filter\scheduled_rule_filter_factory;
use totara_useraction\entity\scheduled_rule as entity;
use totara_useraction\model\scheduled_rule as model;
use totara_useraction\webapi\middleware\require_capability;

/**
 * Query a list of scheduled rules
 */
class scheduled_rules extends query_resolver {
    /**
     * Default page size if nothing was specified.
     */
    const DEFAULT_PAGE_SIZE = 30;

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
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        $input = $args['input'] ?? [];
        $tenant_id = $input['tenant_id'] ?? null;

        // Handle our pagination (we support both methods) with boilerplate
        if (!empty($input['pagination']['cursor'])) {
            // Cursor-based, use it instead
            $cursor = offset_cursor::decode($input['pagination']['cursor']);
        } else {
            $cursor = offset_cursor::create([
                'page' => $input['pagination']['page'] ?? 1,
                'limit' => $input['pagination']['limit'] ?? self::DEFAULT_PAGE_SIZE
            ]);
        }

        return scheduled_rule::create(new scheduled_rule_filter_factory())
            ->set_filters(['tenant_id' => $tenant_id])
            ->set_order('updated, id', 'desc')
            ->fetch_offset_paginated($cursor, function (entity $rule): model {
                return model::load_by_entity($rule);
            });
    }
}
