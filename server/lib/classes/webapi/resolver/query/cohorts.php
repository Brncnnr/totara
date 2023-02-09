<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core_cohort
 */

namespace core\webapi\resolver\query;

use coding_exception;
use cohort;
use context;
use context_system;
use core\data_providers\cohorts as cohorts_provider;
use core\pagination\cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles the "core_cohorts" GraphQL query.
 */
class cohorts extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/cohort/lib.php');

        $query = $args['query'] ?? [];
        $leaf_context_id = $query['leaf_context_id'] ?? null;
        $tenant_scope = $query['tenant_scope'] ?? false;
        $order_by = $query['order_by'] ?? 'name';
        $order_dir = $query['order_dir'] ?? 'ASC';
        $result_size = $query['result_size'] ?? cohorts_provider::DEFAULT_PAGE_SIZE;

        $enc_cursor = $query['cursor'] ?? null;
        $cursor = $enc_cursor ? cursor::decode($enc_cursor) : null;

        $filters = $query['filters'] ?? [];
        $type = $filters['type'] ?? null;
        if ($type) {
            switch ($type) {
                case 'DYNAMIC':
                    $filters['type'] = cohort::TYPE_DYNAMIC;
                    break;

                case 'STATIC':
                    $filters['type'] = cohort::TYPE_STATIC;
                    break;

                default:
                    throw new coding_exception("invalid cohort type: '$type'");
            }
        }

        if (!empty($leaf_context_id)) {
            $context = context::instance_by_id($leaf_context_id);
        } else {
            $context = context_system::instance();
        }

        if (!$context instanceof context_system) {
            $ec->set_relevant_context($context);
        }

        return (new cohorts_provider($context, $tenant_scope))
            ->set_page_size($result_size)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated($cursor);
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }

}
