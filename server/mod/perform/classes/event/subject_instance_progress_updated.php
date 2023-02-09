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
use mod_perform\models\activity\subject_instance;

/**
 * Class subject_instance_progress_updated
 *
 * This event is fired when the progress status of a subject instance changes.
 */
class subject_instance_progress_updated extends progress_updated_event {
    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'perform_subject_instance';
    }

    /**
     * Create event by subject instance.
     *
     * @param subject_instance $subject_instance
     * @param string|null $from_progress_state the name of previous progress state.
     *
     * @return self|base
     * @throws \coding_exception
     */
    public static function create_from_subject_instance(
        subject_instance $subject_instance,
        ?string $from_progress_state = null
    ): self {
        $other = static::data_other(
            $subject_instance->progress_status,
            $from_progress_state
        );

        $data = [
            'objectid' => $subject_instance->get_id(),
            'relateduserid' => $subject_instance->subject_user->id,
            'userid' => \core\session\manager::get_realuser()->id,
            'other' => $other,
            'context' => $subject_instance->get_context(),
        ];

        return static::create($data);
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        $with_progress = $this->get_progress_change_description();

        return "The user with id '$this->userid' progressed the"
             . " subject instance with id '$this->objectid'"
             . " for the user with id '$this->relateduserid'"
             . " $with_progress";
    }
}
