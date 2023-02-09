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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\event;

use core\event\base;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\participant_instance;

/**
 * Class participant_instance_availability_opened event is triggered when a
 * participant instance is created/re-opened.
 *
 * @package mod_perform\event
 */
class participant_instance_availability_opened extends base {

    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = participant_instance_entity::TABLE;
    }

    /**
     * Create instance of the event.
     *
     * @param participant_instance $participant_instance
     *
     * @return participant_instance_availability_opened the instance.
     */
    public static function create_from_participant_instance(
        participant_instance $participant_instance
    ): participant_instance_availability_opened {
        $subject_instance = $participant_instance->subject_instance;
        $data = [
            'objectid' => $participant_instance->id,
            'relateduserid' => $participant_instance->participant_id,
            'userid' => \core\session\manager::get_realuser()->id,
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
        return get_string('event_participant_instance_availability_opened', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        return "The availability of the participant instance with id '$this->objectid'"
             . " for the user with id '$this->relateduserid' has been opened"
             . " by the user with id '$this->userid'";
    }

}
