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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state\participant_section;

use mod_perform\state\participant_section\condition\no_answers_provided;
use mod_perform\state\participant_section\condition\not_no_answers_provided;
use mod_perform\state\transition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents the "not_submitted" progress status of a participant section.
 *
 * Not submitted indicates that progress has been halted before completion has occurred.
 *
 * @package mod_perform
 */
class not_submitted extends participant_section_progress {

    public static function get_name(): string {
        return 'NOT_SUBMITTED';
    }

    public static function get_code(): int {
        return 50;
    }

    public function get_transitions(): array {
        return [
            // An admin has manually moved progress backwards.
            transition::to(new in_progress($this->object))->with_conditions([
                not_no_answers_provided::class,
                // Could replace with "viewed".
            ]),

            // An admin has manually moved progress backwards.
            transition::to(new not_started($this->object))->with_conditions([
                no_answers_provided::class,
            ]),
        ];
    }

    public function complete(): void {
        // Must be moved out of not submitted state before being marked complete.
    }

    public function on_participant_access(): void {
        // Already in the correct state. Do nothing.
    }

    public function manually_complete(): void {
        // Already in the correct state. Do nothing.
    }

    public function manually_uncomplete(): void {
        foreach ([in_progress::class, not_started::class] as $to_state) {
            if ($this->can_switch($to_state)) {
                $this->object->switch_state($to_state);
                break;
            }
        }
    }

}
