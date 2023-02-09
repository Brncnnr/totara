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

namespace mod_perform\event;

defined('MOODLE_INTERNAL') || die();

use core\event\base;
use mod_perform\models\response\participant_section;

/**
 * Class participant_section_availability_closed event is triggered when a participant_section is closed.
 *
 * @package mod_perform\event
 */
class participant_section_availability_closed extends base {

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'perform_participant_section';
    }

    /**
     * Create instance of event.
     *
     * @param participant_section $participant_section
     * @return self|base
     */
    public static function create_from_participant_section(participant_section $participant_section): self {
        $data = [
            'objectid' => $participant_section->get_id(),
            'relateduserid' => $participant_section->get_participant_instance()->participant_id,
            'userid' => \core\session\manager::get_realuser()->id,
            'other' => [
                'participant_instance_id' => $participant_section->get_participant_instance()->get_id(),
            ],
            'context' => $participant_section->get_context(),
        ];

        return static::create($data);
    }

    /**
     * @inheritDoc
     */
    public static function get_name() {
        return get_string('event_participant_section_availability_closed_name', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        return "The availability of the participant section with id '$this->objectid'"
             . " for the user with id '$this->relateduserid' has been closed"
             . " by the user with id '$this->userid'";
    }
}
