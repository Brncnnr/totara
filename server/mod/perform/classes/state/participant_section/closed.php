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

namespace mod_perform\state\participant_section;

use core\event\base;
use mod_perform\event\participant_section_availability_closed;
use mod_perform\models\response\participant_section;
use mod_perform\state\state_event;
use mod_perform\state\transition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents the "closed" availability status of a participant section.
 *
 * @package mod_perform
 */
class closed extends participant_section_availability implements state_event {

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return 'CLOSED';
    }

    /**
     * @inheritDoc
     */
    public static function get_display_name(): string {
        return get_string('participant_section_availability_closed', 'mod_perform');
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function get_transitions(): array {
        return [
            transition::to(new open($this->object)),
        ];
    }

    /**
     * @inheritDoc
     */
    public function close(): void {
        // Already in the correct state.
    }

    /**
     * @inheritDoc
     */
    public function open(): void {
        $this->object->switch_state(open::class);
    }

    /**
     * @inheritDoc
     */
    public function get_event(): base {
        /**
         * @var participant_section $participant_section
         */
        $participant_section = $this->get_object();

        return participant_section_availability_closed::create_from_participant_section($participant_section);
    }
}
