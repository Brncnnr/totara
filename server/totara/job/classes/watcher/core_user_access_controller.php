<?php
/**
 *
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @author Sam Hemelyrk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 *
 */

namespace totara_job\watcher;

use totara_job\job_assignment;
use \core_user\hook\allow_view_profile;
use \core_user\hook\allow_view_profile_field;

class core_user_access_controller {

    public static function allow_view_profile(allow_view_profile $hook) {
        if ($hook->has_permission()) {
            // Someone has already given access - no need for us to resolve this further.
            return;
        }
        if (self::users_share_relationships($hook->target_user_id, $hook->viewing_user_id)) {
            $hook->give_permission();
        }
    }

    public static function allow_view_profile_field(allow_view_profile_field $hook) {
        if ($hook->has_permission()) {
            // Someone has already given access - no need for us to resolve this further.
            return;
        }

        switch ($hook->field) {
            case 'email':
            case 'fullname':
            case 'profileimageurl':
            case 'profileimageurlsmall':
            case 'profileimagealt':
            case 'imagealt':
                if (self::users_share_relationships($hook->target_user_id, $hook->viewing_user_id)) {
                    $hook->give_permission();
                }
                break;
        }
    }

    private static function users_share_relationships($user_a_id, $user_b_id): bool {
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'totara_job', 'users_share_relationships');
        $key = $user_a_id . '_' . $user_b_id;
        $value = $cache->get($key);
        if ($value === false) {
            $value = (int)job_assignment::users_share_relation($user_a_id, $user_b_id);
            $cache->set($key, $value);
        }
        return (bool)$value;
    }
}