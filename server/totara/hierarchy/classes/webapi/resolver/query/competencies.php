<?php
/*
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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\webapi\resolver\query;

use coding_exception;
use core\pagination\offset_cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\middleware\require_user_capability;
use core\webapi\query_resolver;
use hierarchy_competency\data_providers\competency_provider;
use totara_hierarchy\entity\filters\competency_filters;

/**
 * Handles the "totara_hierarchy_competencies" GraphQL query.
 */
class competencies extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            new require_advanced_feature('competencies'),
            new require_user_capability('totara/hierarchy:viewcompetency'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'] ?? [];
        $filters = self::process_filters($input);

        extract(self::process_order($input));

        $cursor = self::process_pagination($input);

        $result = competency_provider::create(new competency_filters())
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_offset_paginated($cursor);

        if (!empty($input['filters']['parent_id'])) {
            $path = competency_provider::get_all_parent_competencies($input['filters']['parent_id']);
            $result['parents'] = $path['parents'];
            $result['current_level'] = $path['current_level'];
        }

        return $result;
    }

    /**
     * process filters
     *
     * @param array|null $input
     * @return array
     */
    private static function process_filters(?array $input = null): array {
        $result = [];
        if (empty($input['filters'])) {
            return $result;
        }

        foreach ($input['filters'] as $key => $value) {
            $condition = !empty($value);
            $condition .= $key === competency_filters::FILTER_NO_HIERARCHY && is_int($value);
            if ($condition) {
                $result[$key] = $value;
            }
        }

        // check if no hierarchy and parent id were both exist
        if (isset($result[competency_filters::FILTER_NO_HIERARCHY]) && isset($result[competency_filters::FILTER_PARENT_ID])) {
            unset($result[competency_filters::FILTER_NO_HIERARCHY]);
        }
        return $result;
    }

    /**
     * process orders
     *
     * @param array|null $input
     * @return array
     * @throws coding_exception
     */
    private static function process_order(?array $input = null): array {
        $raw_order_by = strtolower($input['order_by'] ?? competency_provider::SORT_COMPETENCY_NAME);
        $order_by = competency_provider::SORT_FIELDS[$raw_order_by] ?? null;
        if (!$order_by) {
            throw new coding_exception("unknown sort order: $raw_order_by");
        }

        // if the no path filter set 'true' and sort order set to 'path', the filter will overwrite sort order,
        // the sort order will default to competency_name.
        if (!empty($input['filters'][competency_filters::FILTER_NO_PATH]) && $order_by === competency_provider::SORT_FIELDS[competency_provider::SORT_PATHWAY]) {
            $order_by = competency_provider::SORT_FIELDS[competency_provider::SORT_COMPETENCY_NAME];
        }

        return [
            'order_by' => $order_by,
            'order_dir' => $input['order_dir'] ?? 'ASC'
        ];
    }

    /**
     * process the pagination
     *
     * @param array|null $input
     * @return offset_cursor
     */
    private static function process_pagination(?array $input = null): offset_cursor {
        if (!empty($input['pagination']['cursor'])) {
            return offset_cursor::decode($input['pagination']['cursor']);
        }
        return offset_cursor::create([
            'page' => $input['pagination']['page'] ?? 1,
            'limit' => $input['pagination']['limit'] ?? competency_provider::DEFAULT_PAGE_SIZE
        ]);
    }
}