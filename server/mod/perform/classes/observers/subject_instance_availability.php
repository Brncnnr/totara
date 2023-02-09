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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\observers;

use core\event\base;
use mod_perform\event\subject_instance_progress_updated;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\subject_instance;
use mod_perform\state\subject_instance\complete;

class subject_instance_availability {

    /**
     * When progress status of a subject instance is updated to complete,
     * the availability of subject instance is closed is close on completion is enabled.
     *
     * @param base|subject_instance_progress_updated $event
     */
    public static function close_completed_subject_instance(base $event): void {
        $subject_instance = subject_instance::load_by_id($event->objectid);

        if (self::can_switch($subject_instance)) {
            $subject_instance->get_availability_state()->close();
        }
    }

    /**
     * Conditions to allow closing the availability.
     *
     * @param subject_instance $subject_instance
     * @return bool
     */
    private static function can_switch(subject_instance $subject_instance): bool {
        $can_close = (bool)$subject_instance
            ->activity
            ->settings
            ->lookup(activity_setting::CLOSE_ON_COMPLETION);

        return $subject_instance->progress_state instanceof complete
            && $can_close;
    }

}
