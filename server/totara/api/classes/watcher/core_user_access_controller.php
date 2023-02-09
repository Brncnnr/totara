<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\watcher;

use core\entity\tenant;
use core\orm\query\builder;
use core_user\hook\allow_view_profile;
use totara_api\model\helpers\client_capability_helper;
use totara_core\advanced_feature;

class core_user_access_controller {
    /**
     * @param allow_view_profile $hook
     * @return void
     */
    public static function allow_view_profile(allow_view_profile $hook): void {
        if (advanced_feature::is_disabled('api')) {
            return;
        }

        if ($hook->has_permission()) {
            return;
        }

        $course = $hook->get_course();
        if ($course) {
            return;
        }

        if (self::can_allow_view_profile_by_tenant_user($hook->viewing_user_id, $hook->target_user_id)) {
            $hook->give_permission();
        }
    }

    /**
     * @param int $current_user_id
     * @param int $target_user_id
     * @return bool
     */
    private static function can_allow_view_profile_by_tenant_user(
        int $current_user_id,
        int $target_user_id
    ): bool {
        $target_user = \core_user::get_user($target_user_id, 'tenantid');

        if ($target_user && !empty($target_user->tenantid)) {
            $result = builder::table('cohort_members', 'cm')
                ->join(['tenant', 't'], 't.cohortid', 'cm.cohortid')
                ->where('cm.userid', $current_user_id)
                ->where('t.id', $target_user->tenantid)
                ->exists();

            if ($result) {
                return client_capability_helper::for_tenant(new tenant($target_user->tenantid))
                    ->can_manage();
            }
        }

        return false;
    }
}