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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\webapi\resolver\query;

use context_system;
use context_user;
use core\entity\user;
use core\pagination\cursor;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_core\hook\component_access_check;
use totara_core\user_learning\item_helper;
use totara_core\user_learning\learning_items_helper;
use totara_job\job_assignment;

/**
 * Query to return my programs.
 */
class user_learning_items_selected extends query_resolver {

    /**
     * Returns the user's current learning items.
     *
     * @param array $args
     * @param execution_context $ec
     *
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        global $USER;
        $user_id = $args['input']['user_id'] ?? $USER->id;

        // Return empty result when not authorized.
        if (!self::authorize($user_id)) {
            return [
                'items' => [],
                'total' => 0,
                'next_cursor' => '',
            ];
        }

        // Organise type ID's.
        $type_ids = [];
        foreach ($args['input']['filters']['ids'] as $id) {
            [$id, $type] = explode('-', $id);
            $type_ids[$type][] = $id;
        }

        // Get data providers.
        $data_providers = [];
        foreach ($type_ids as $type => $ids) {
            $data_provider = item_helper::get_data_provider($type);
            $data_provider->set_filters([
                'user_id' => $user_id,
                'ids' => $ids
            ]);
            $data_providers[] = $data_provider;
        }

        // Create cursor.
        if (!empty($args['input']['cursor'])) {
            $cursor = cursor::decode($args['input']['cursor']);
        } else {
            $cursor = cursor::create()->set_limit($args['input']['result_size'] ?? 20);
        }

        // Fetch and return user learning items.
        return learning_items_helper::get_learning_items($data_providers, $user_id, $cursor);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }

    /**
     * Check whether the user can access the learning
     *
     * @param int $target_user_id
     *
     * @return bool
     */
    private static function authorize(int $target_user_id): bool {
        global $USER;

        // Guests do not have learning items.
        if (isguestuser()) {
            return false;
        }

        // Users can only view their own and their staff's pages or if they are an admin.
        $access = $target_user_id == $USER->id
            || job_assignment::is_managing($USER->id, $target_user_id)
            || has_capability('totara/plan:accessanyplan', context_system::instance(), $USER->id)
            || has_capability('totara/core:viewrecordoflearning', context_user::instance($target_user_id), $USER->id);

        if (!$access) {
            $hook = new component_access_check(
                'user_learning',
                user::logged_in()->id,
                $target_user_id,
                []
            );
            $access = $hook->execute()->has_permission();
        }

        return $access;
    }

}
