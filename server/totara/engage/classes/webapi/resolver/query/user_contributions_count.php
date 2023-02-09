<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_engage\card\card_loader;
use totara_engage\query\query;

/**
 * Provide a total number of public/accessible contributions..
 * This follows the logic of user_contributions query just excluding the filters
 * that users can apply.
 *
 * @package totara_engage\webapi\resolver\query
 */
class user_contributions_count extends query_resolver {
    /**
     * Area that this endpoint only supports
     */
    private static $valid_areas = [
        'otheruserlib',
    ];

    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return int
     */
    public static function resolve(array $args, execution_context $ec): int {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        $target_user_id = $args['user_id'] ?? null;
        if (empty($target_user_id)) {
            throw new \coding_exception('Query user_contributions_count must specify the "user_id" field');
        }

        // Only support the one area
        if (!in_array($args['area'], self::$valid_areas)) {
            throw new \coding_exception("Query user_contributions_count does not support the '{$args['area']}' area.");
        }

        if (!isset($args['component'])) {
            throw new \coding_exception('Component is a required field.');
        }

        // If the user cannot access the target user, return nothing
        // We silently fail here as we want the parent page to handle it
        $target_user_context = \context_user::instance($target_user_id);
        if ($target_user_context->is_user_access_prevented($USER->id)) {
            return 0;
        }

        $query = new query();
        $query->set_component($args['component']);
        $query->set_area($args['area']);

        // User who owns the cards we want to display
        $query->set_userid($target_user_id);

        // User doing the asking (show the shared)
        $query->set_share_recipient_id($USER->id);

        $loader = new card_loader($query);
        $paginator = $loader->fetch();

        return $paginator->count();
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('engage_resources'),
        ];
    }
}