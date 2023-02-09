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

defined('MOODLE_INTERNAL') || die();

use core\event\base;
use mod_perform\models\response\participant_section;
use mod_perform\models\activity\participant_source;

class participant_section_saved_as_draft extends base {

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
     *
     * @return self|base
     */
    public static function create_from_participant_section(
        participant_section $participant_section
    ): self {
        $participant_instance = $participant_section->participant_instance;
        $anonymous = $participant_instance
            ->subject_instance
            ->activity
            ->anonymous_responses;

        $data = [
            'objectid' => $participant_section->id,
            'relateduserid' => $participant_instance->participant_id,
            'userid' => \core\session\manager::get_realuser()->id,
            'other' => [
                'anonymous' => $anonymous,
                'participant_source' => $participant_instance->participant_source
            ],
            'context' => $participant_section->get_context(),
        ];
        return static::create($data);
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        $source = (int)$this->other['participant_source'] === participant_source::EXTERNAL
            ? 'external'
            : 'internal';

        $anonymous = $this->other['anonymous'];
        $participant_id = $anonymous ? 'anonymous' : $this->relateduserid;
        $user_id = $anonymous ? 'anonymous' : $this->userid;

        return "The user with id '$user_id' saved the"
             . " participant section instance with id '$this->objectid'"
             . " for the $source user with id '$participant_id'"
             . " as draft";
    }
}