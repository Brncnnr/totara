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
use mod_perform\task\service\subject_instance_creation;

/**
 * Create subject instance task.
 */
class create_subject_instance_task extends scheduled_task {

    public function get_name() {
        return get_string('create_subject_instance_task', 'mod_perform');
    }

    public function execute() {
        $expand_task = new subject_instance_creation();
        $expand_task->generate_instances();
    }

}
