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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\event;

defined('MOODLE_INTERNAL') || die();

use core\event\base;
use mod_perform\models\response\participant_section;

class participant_section_progress_updated extends progress_updated_event {
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
     * @param string|null $from_progress_state the name of previous progress state.
     *
     * @return self|base
     */
    public static function create_from_participant_section(
        participant_section $participant_section,
        ?string $from_progress_state = null
    ): self {
        $participant_instance = $participant_section->participant_instance;
        $other = static::data_other(
            $participant_section->progress_status,
            $from_progress_state,
            $participant_instance->anonymise_responses,
            $participant_instance->participant_source
        );

        $data = [
            'objectid' => $participant_section->id,
            'relateduserid' => $participant_instance->participant_id,
            'userid' => \core\session\manager::get_realuser()->id,
            'other' => $other,
            'context' => $participant_section->get_context(),
        ];

        return static::create($data);
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        $with_progress = $this->get_progress_change_description();
        $source = $this->get_participant_source();
        $participant_id = $this->get_anonymised_user($this->relateduserid);
        $user_id = $this->get_anonymised_user($this->userid);

        return "The user with id '$user_id' progressed the"
             . " participant instance with id '$this->objectid'"
             . " for the $source user with id '$participant_id'"
             . " $with_progress";
    }
}
