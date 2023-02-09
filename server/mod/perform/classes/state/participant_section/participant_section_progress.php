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

namespace mod_perform\state\participant_section;

use core\event\base;
use mod_perform\event\participant_section_progress_updated;
use mod_perform\models\response\participant_section;
use mod_perform\state\state;
use mod_perform\state\state_event;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract class representing a progress status of a participant section.
 *
 * @package mod_perform
 */
abstract class participant_section_progress extends state implements state_event {

    /**
     * Try to switch progress status to complete.
     */
    abstract public function complete(): void;

    /**
     * Handle the fact that participant has accessed the section.
     */
    abstract public function on_participant_access(): void;

    /**
     * Manually mark a participant section as being complete
     */
    abstract public function manually_complete(): void;

    /**
     * Manually mark a participant section as being not complete
     */
    abstract public function manually_uncomplete(): void;

    /**
     * @inheritDoc
     */
    public static function get_type(): string {
        return 'progress';
    }

    public static function get_display_name(): string {
        return get_string('participant_section_status_' . strtolower(static::get_name()), 'mod_perform');
    }

    public function get_event(): base {
        /** @var participant_section $participant_section */
        $participant_section = $this->get_object();
        $previous_state = $this->previous_state;

        return participant_section_progress_updated::create_from_participant_section(
            $participant_section,
            $previous_state ? $previous_state::get_name() : null
        );
    }
}
