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

use mod_perform\state\participant_section\condition\all_answers_complete;
use mod_perform\state\transition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents the "in progress" status of a participant section.
 *
 * @package mod_perform
 */
class in_progress extends participant_section_progress {

    public static function get_name(): string {
        return 'IN_PROGRESS';
    }

    public static function get_code(): int {
        return 10;
    }

    public function get_transitions(): array {
        return [
            // The participant has completed a section.
            transition::to(new complete($this->object))->with_conditions([
                all_answers_complete::class
            ]),

            // The participant has not completed a section, but it is "done".
            transition::to(new not_submitted($this->object)),
        ];
    }

    public function complete(): void {
        if ($this->can_switch(complete::class)) {
            $this->object->switch_state(complete::class);
        }
    }

    public function on_participant_access(): void {
        // Not relevant when already in progress. Do nothing.
    }

    public function manually_complete(): void {
        $this->object->switch_state(not_submitted::class);
    }

    public function manually_uncomplete(): void {
        // Not relevant when incomplete. Do nothing.
    }

}
