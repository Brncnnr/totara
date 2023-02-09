<?php
/*
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */


namespace mod_perform\task;

use core\task\scheduled_task;
use mod_perform\task\service\manual_participant_progress;

/**
 * Create progress records for selecting manual participants in performance activities.
 * This also syncs existing progress records and makes sure the actual selectors are up-to-date
 */
class create_manual_participant_progress_task extends scheduled_task {

    public function get_name() {
        return get_string('create_manual_participant_progress_task', 'mod_perform');
    }

    public function execute() {
        $expand_task = new manual_participant_progress();
        $expand_task->generate();
    }

}
