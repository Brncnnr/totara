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

use core\lock\lock;
use core\lock\lock_config;

trait expand_task_trait {

    private function get_expand_task_lock(): lock {
        $cron_lock_factory = lock_config::get_lock_factory('totara_competency');
        if (!$cron_lock = $cron_lock_factory->get_lock('expand_assignments', 10)) {
            throw new \moodle_exception('locktimeout');
        }
        return $cron_lock;
    }

}