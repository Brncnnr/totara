<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\observers;

use core\event\base;
use mod_perform\event\participant_instance_progress_updated;
use mod_perform\models\activity\participant_instance;
use mod_perform\totara_notification\resolver\participant_instance_completion_resolver;
use totara_core\relationship\relationship;
use totara_notification\external_helper;

class totara_notification {

    /**
     * Trigger the participation submission notification when the participant instance is completed.
     *
     * @param participant_instance_progress_updated|base $event
     * @return void
     */
    public static function participant_section_submitted(base $event) {
        $participant_instance = participant_instance::load_by_id($event->objectid);
        if ($participant_instance->is_complete()) {
            /** @var relationship $core_relationship */
            $subject_instance = $participant_instance->get_subject_instance();
            $data = [
                'activity_id' => $subject_instance->activity->id,
                'subject_user_id' => $subject_instance->subject_user_id,
                'subject_instance_id' => $subject_instance->id,
                'participant_instance_id' => $participant_instance->id,
                'participant_id' => $participant_instance->participant_id,
                'participant_source' => $participant_instance->participant_source,
                'user_id' => $event->userid
            ];

            $resolver = new participant_instance_completion_resolver($data);
            external_helper::create_notifiable_event_queue($resolver);
        }
    }
}