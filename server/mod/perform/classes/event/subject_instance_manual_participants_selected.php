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

namespace mod_perform\event;

use core\event\base;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\subject_instance;

/**
 * Class subject_instance_manual_participants_selected
 *
 * This event is fired when a user selects the participant users for a subject instance.
 */
class subject_instance_manual_participants_selected extends base {

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = subject_instance_entity::TABLE;
    }

    /**
     * Create event from selected participants and the subject instance.
     *
     * @param int[][] $relationships_and_participants Array of $relationship_id => [$participant_user_id]
     * @param subject_instance $subject_instance
     * @return self|base
     */
    public static function create_from_selected_participants(
        array $relationships_and_participants,
        subject_instance $subject_instance
    ): self {
        $data = [
            'objectid' => $subject_instance->get_id(),
            'relateduserid' => $subject_instance->subject_user->id,
            'other' => [
                'relationships_and_participants' => $relationships_and_participants,
            ],
            'context' => $subject_instance->get_context(),
        ];

        return static::create($data);
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_subject_instance_manual_participants_selected', 'mod_perform');
    }

    /**
     * Get the subject instance, the selector user, and the selected users for each relationship for admin auditing purposes.
     *
     * @return string
     */
    public function get_description(): string {
        $description =
            "Selector user with id {$this->userid} made the following participant selections" .
            " for subject instance with id {$this->data['objectid']}:\n\n";

        foreach ($this->data['other']['relationships_and_participants'] as $relationship_and_participants) {
            $relationship_id = $relationship_and_participants['manual_relationship_id'];
            $users = $relationship_and_participants['users'];

            $users = implode(', ', array_map(static function (array $user) {
                if (isset($user['user_id'])) {
                    // Internal User IDs.
                    return "user with id {$user['user_id']}";
                }

                // External User emails and names.
                return "user with email {$user['email']} and name '{$user['name']}'";
            }, $users));

            $description .= "\nRelationship with id {$relationship_id}: {$users}.";
        }

        return $description;
    }

}
