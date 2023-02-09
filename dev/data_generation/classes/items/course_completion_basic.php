<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace degeneration\items;

class course_completion_basic extends course_completion {

    /**
     * Save a user
     *
     * @return bool
     */
    public function save(): bool {
        if (!$this->by) {
            throw new \Exception('You must set user to create completion record');
        }

        if (!$this->for) {
            throw new \Exception('You must set user to create completion record');
        }

        $start = time() - 7200;
        $completed = time() - 3600;

        $completion = [
            'userid' => $this->by,
            'course' => $this->for->get_data()->id,
            'timeenrolled' => $start,
            'timestarted' => $start,
            'timecompleted' => $completed,
            'reaggregate' => 0,
            'status' => COMPLETION_STATUS_COMPLETE
        ];

        $this->data = $completion;

        return true;
    }

}