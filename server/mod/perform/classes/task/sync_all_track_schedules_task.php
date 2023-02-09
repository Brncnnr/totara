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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task;

use core\task\scheduled_task;
use mod_perform\task\service\track_schedule_sync;

/**
 * Synchronise all track schedules to track_user_assignments.
 *
 * This will go through all tracks of active activities and sync the schedule settings to track_user_assignment records.
 * Therefore, it should not be run often.
 *
 * This is for cases that are not covered by sync_track_schedule_changes_task.
 */
class sync_all_track_schedules_task extends scheduled_task {

    public function get_name() {
        return get_string('sync_track_schedule_task', 'mod_perform');
    }

    public function execute() {
        (new track_schedule_sync())->sync_all();
    }

}
