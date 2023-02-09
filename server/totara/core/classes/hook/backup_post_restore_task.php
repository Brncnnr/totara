<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\hook;

use restore_task;

/**
 * Hook for performing any operations after a restore task has been executed via the moodle2 backup API.
 */
final class backup_post_restore_task extends base {

    /**
     * @var restore_task
     */
    private $task;

    /**
     * backup_post_restore_task constructor.
     * @param restore_task $task
     */
    public function __construct(restore_task $task) {
        $this->task = $task;
    }

    /**
     * @return restore_task
     */
    public function get_task(): restore_task {
        return $this->task;
    }

}
