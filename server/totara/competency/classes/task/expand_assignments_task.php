<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace totara_competency\task;

use totara_competency\expand_task;

/**
 * Update competency user assignment table
 */
class expand_assignments_task extends \core\task\scheduled_task {

    use expand_task_trait;

    public function get_name() {
        return get_string('expand_assignments_task', 'totara_competency');
    }

    public function execute() {
        global $DB;

        $lock = $this->get_expand_task_lock();

        try {
            $expand_task = new expand_task($DB);
            $expand_task->expand_all();
            $lock->release();
        } catch (\Exception $exception) {
            $lock->release();
            throw $exception;
        }
    }

}
