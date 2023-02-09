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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\watcher;

use core\entity\user;
use core\hook\admin_setting_changed;
use core\task\manager;
use mod_perform\task\close_instances_for_suspended_users_task;
use totara_core\advanced_feature;

/**
 * Class suspended_users
 * @package mod_perform\watcher
 */
class suspended_users {

    /**
     * @param admin_setting_changed $hook
     */
    public static function admin_setting_changed(admin_setting_changed $hook): void {
        if (!in_array($hook->name, ['perform_hide_suspended_users', 'perform_close_suspended_user_instances'])) {
            return;
        }

        // This should not be available if Perform is disabled
        if (advanced_feature::is_disabled('performance_activities')) {
            return;
        }

        if ((int)$hook->newvalue === 1) {
            $task = close_instances_for_suspended_users_task::create(user::logged_in()->id);
            manager::queue_adhoc_task($task, true);
        }
    }

}
