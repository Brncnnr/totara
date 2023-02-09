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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task;

use coding_exception;
use core\task\adhoc_task;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\participant_instance as participant_instance;
use mod_perform\totara_notification\resolver\participant_instance_created_resolver;
use totara_notification\external_helper;

/**
 * Class send_participant_instance_creation_notifications_task
 * Adhoc task to queue notifications to participants when an instance has just been created.
 *
 * @package mod_perform\task
 */
class send_participant_instance_creation_notifications_task extends adhoc_task {

    /**
     * Create adhoc task for sending notifications to participant instances.
     * @param array $participant_instance_ids
     * @return send_participant_instance_creation_notifications_task
     */
    public static function create_for_new_participants(array $participant_instance_ids): send_participant_instance_creation_notifications_task {
        if (empty($participant_instance_ids)) {
            throw new coding_exception('No participant instance ids set.');
        }
        $task = new self();
        $task->set_component('mod_perform');
        $task->set_custom_data(['participant_instance_ids' => $participant_instance_ids]);

        return $task;
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        $custom_data = $this->get_custom_data();

        if (empty($custom_data->participant_instance_ids)) {
            throw new coding_exception('No participant instance ids set.');
        }
        $participant_instances = participant_instance_entity::repository()
            ->where_in('id', $custom_data->participant_instance_ids)
            ->get()
            ->all();

        foreach ($participant_instances as $entity) {
            $participant_instance = new participant_instance($entity);
            if (empty($subject_instances[$participant_instance->subject_instance_id])) {
                $subject_instance = $participant_instance->get_subject_instance();

                // Triger a participant instances created notification event for each subject instance.
                $activity = $subject_instance->get_activity();
                $data = [
                    'activity_id' => $activity->id,
                    'subject_user_id' => $subject_instance->subject_user_id,
                    'subject_instance_id' => $subject_instance->id,
                    'created_at' => $participant_instance->created_at,
                    'participant_instance_id' => $participant_instance->get_id(),
                    'participant_id' => $participant_instance->participant_id,
                    'participant_source' => $participant_instance->participant_source,
                ];

                $resolver = new participant_instance_created_resolver($data);
                external_helper::create_notifiable_event_queue($resolver);
            }
        }
    }
}
