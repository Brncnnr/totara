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

use totara_competency\event\assignment_user;
use totara_competency\event\assignment_user_archived;
use totara_competency\event\assignment_user_assigned;
use totara_competency\event\assignment_user_assigned_bulk;
use totara_competency\models;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer
 */
class user_log {

    /**
     * Triggered via assignment_user_archived event.
     *
     * @param assignment_user $event
     * @return bool true on success
     */
    public static function log(assignment_user $event) {
        $assignment_id = $event->get_assignment_id();
        $competency_id = $event->get_competency_id();
        $assignment_type = $event->get_assignment_type();
        if ($event->is_bulk()) {
            $user_id = $event->get_user_ids();
        } else {
            $user_id = $event->get_user_id();
        }

        if ($assignment_id && $user_id) {
            $log = new models\assignment_user_log($assignment_id, $competency_id, $assignment_type);
            switch (get_class($event)) {
                case assignment_user_archived::class:
                    // Log this action
                    $tracking_continues = $event->get_tracking_continues();
                    $log->log_archive($user_id, $tracking_continues);
                    break;
                case assignment_user_assigned::class:
                case assignment_user_assigned_bulk::class:
                    // Log this action
                    $log->log_assign($user_id);
                    break;
            }
        }

        return true;
    }

}
