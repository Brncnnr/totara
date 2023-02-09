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
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\recipient;

use coding_exception;
use mod_perform\constants;
use mod_perform\entity\activity\manual_relationship_selection_progress;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use totara_core\relationship\relationship as core_relationship;
use totara_job\job_assignment;
use totara_notification\recipient\recipient;

class participant_selector_manager implements recipient {
    public static function get_name(): string {
        return get_string('notification_recipient_participant_selector_manager', 'mod_perform');
    }

    /**
     * @throws coding_exception
     */
    public static function get_user_ids(array $data): array {
        if (!isset($data['subject_user_id'])) {
            throw new coding_exception('Missing subject_user_id');
        }

        if (!isset($data['activity_id'])) {
            throw new coding_exception('Missing activity_id');
        }

        if (!isset($data['subject_instance_id'])) {
            throw new coding_exception('Missing subject_instance_id');
        }

        $manager_selector_relationship_id = core_relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->id;
        $current_managers = job_assignment::get_all_manager_userids($data['subject_user_id']);
        $managers = [];

        if(empty($current_managers)) {
            return [];
        }

        // Subject instance
        /** @var subject_instance_entity $entity */
        $subject_instance = subject_instance_entity::repository()->find($data['subject_instance_id']);

        /** @var manual_relationship_selection_progress $progress */
        foreach($subject_instance->manual_relationship_selection_progress as $progress) {
            // Only for manager
            if ($progress->manual_relationship_selection->selector_relationship_id != $manager_selector_relationship_id) {
                continue;
            }

            // Don't send notification for users who already selected
            if ($progress->status != manual_relationship_selection_progress::STATUS_PENDING) {
                continue;
            }

            foreach ($progress->manual_relationship_selectors as $selector) {
                // Check selector is the current manager
                if (in_array($selector->user_id, $current_managers)) {
                    $managers[] = $selector->user_id;
                }
            }
        }

        // A manager can be a selector for multiple. But only one notification send per subject instance.
        return array_unique($managers);
    }
}