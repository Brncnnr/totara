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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\task;

use core\task\scheduled_task;
use totara_competency\aggregation_task;
use totara_competency\aggregation_users_table;

/**
 * Aggregates competency achievements for all users and competencies
 * currently queued in the totara_competency_aggregation_queue table.
 *
 * This task only aggregates competencies for users where actual changes
 * happen so this is not a long running task and can be run
 * more regularly.
 */
class competency_aggregation_queue extends scheduled_task {

    public function get_name() {
        return get_string('aggregate_queued_competencies_task', 'totara_competency');
    }

    public function execute() {
        // Make sure our process key is unique in case we run it in parallel
        $process_key = md5(uniqid(rand(), true));

        $table = new aggregation_users_table();
        $table->set_process_key_value($process_key);
        $table->claim_process();

        $task = new aggregation_task($table, false);
        $task->execute();

        $table->delete();
    }

}
