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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\event;

use core\event\base;
use core\session\manager;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\participant_instance;

class participant_instance_manually_deleted extends base {

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = participant_instance_entity::TABLE;
    }

    /**
     * Create instance of event.
     *
     * @param participant_instance $participant_instance
     * @return self|base
     */
    public static function create_from_participant_instance(participant_instance $participant_instance): self {
        $subject_instance = $participant_instance->subject_instance;
        $data = [
            'objectid' => $participant_instance->id,
            'relateduserid' => $participant_instance->participant_id,
            'userid' => manager::get_realuser()->id,
            'other' => [
                'subject_instance_id' => $subject_instance->id,
            ],
            'context' => $subject_instance->get_context(),
        ];

        return static::create($data);
    }

    /**
     * @inheritDoc
     */
    public static function get_name() {
        return get_string('event_participant_instance_manually_deleted', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        return "The participant instance with id '$this->objectid'"
            . " for the user with id '$this->relateduserid' has been deleted"
            . " by the user with id '$this->userid'";
    }
}