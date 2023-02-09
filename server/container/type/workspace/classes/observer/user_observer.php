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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\observer;

use container_workspace\interactor\workspace\interactor;
use container_workspace\workspace;
use core\event\user_deleted;
use core_container\factory;
use totara_core\event\bulk_enrolments_ended;

final class user_observer {
    /**
     * @param user_deleted $event
     * @return void
     */
    public static function on_user_deleted(user_deleted $event): void {
        global $DB;

        $sql = 'UPDATE "ttr_workspace" SET user_id = NULL WHERE user_id = :user_id';

        $user_id = $event->objectid;
        $DB->execute($sql, ['user_id' => $user_id]);
    }

    /**
     * Listen for any bulk member changes (done via audience sync) and fix up the
     * workspace user_id afterwards if needed.
     *
     * We're checking the workspace as a whole rather than the individual user event to cut the checks down to
     * once per removal.
     *
     * @param bulk_enrolments_ended $event
     * @return void
     */
    public static function on_users_removed(bulk_enrolments_ended $event): void {
        /** @var workspace $workspace */
        $workspace = factory::from_id($event->courseid);
        if (!$workspace->is_typeof('container_workspace')) {
            return;
        }

        $user_id = $workspace->get_user_id();
        if (empty($user_id)) {
            return;
        }

        // Is the workspace user still enrolled in the workspace?
        if ((new interactor($workspace, $user_id))->is_owner()) {
            return;
        }

        // Nope, so remove the workspace user.
        $workspace->remove_user();
    }
}