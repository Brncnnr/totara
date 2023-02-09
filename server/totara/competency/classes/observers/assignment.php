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

namespace totara_competency\observers;

use totara_competency\event\assignment_activated;
use totara_competency\event\assignment_archived;
use totara_competency\event\assignment_deleted;
use totara_competency\task\expand_assignment_task;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer
 */
class assignment {

    /**
     * Triggered via assignment_archived event.
     *
     * @param assignment_archived $event
     * @return bool true on success
     */
    public static function archived(assignment_archived $event) {
        return true;
    }

    /**
     * Triggered via assignment_activated event.
     *
     * @param assignment_activated $event
     * @return bool true on success
     */
    public static function activated(assignment_activated $event) {
        $data = $event->get_data();
        $assignment_id = $data['objectid'];

        // trigger expand task for the activated assignment
        expand_assignment_task::schedule_for_assignment($assignment_id);

        return true;
    }

    /**
     * Triggered via assignment_deleted event.
     *
     * @param assignment_deleted $event
     * @return bool true on success
     */
    public static function deleted(assignment_deleted $event) {
        return true;
    }

}
