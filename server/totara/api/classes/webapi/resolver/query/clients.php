<?php
/**
 * This file is part of Totara Core
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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\webapi\resolver\query;

use core\pagination\cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use totara_api\data_provider\client;
use totara_api\entity\filter\client_filter_factory;
use totara_api\entity\client as entity;
use totara_api\model\client as model;
use totara_api\webapi\middleware\require_manage_capability;

/**
 * Class clients
 * @package totara_api\webapi\resolver\query
 */
class clients extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        $input = $args['input'] ?? [];

        $tenant_id = $input['tenant_id'] ?? null;
        $enc_cursor = $input['pagination']['cursor'] ?? null;
        $cursor = $enc_cursor ? cursor::decode((string) $enc_cursor) : null;
        $limit = $input['pagination']['limit'] ?? client::DEFAULT_PAGE_SIZE;

        return client::create(new client_filter_factory())
            ->set_filters(['tenant_id' => $tenant_id])
            ->set_page_size($limit)
            ->fetch_paginated($cursor, function (entity $entity): model {
                return model::load_by_entity($entity);
            });
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            new require_advanced_feature('api'),
            require_manage_capability::by_tenant_id('input.tenant_id', true)
        ];
    }
}