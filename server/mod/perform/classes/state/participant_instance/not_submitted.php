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

namespace mod_perform\state\participant_instance;

use mod_perform\state\participant_instance\condition\at_least_one_section_started;
use mod_perform\state\participant_instance\condition\no_sections_complete;
use mod_perform\state\participant_instance\condition\not_all_sections_complete;
use mod_perform\state\transition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents the "not_submitted" progress status of a participant instance.
 *
 * Not submitted indicates that progress has been halted before completion has occurred.
 *
 * @package mod_perform
 */
class not_submitted extends participant_instance_progress {

    public static function get_name(): string {
        return 'NOT_SUBMITTED';
    }

    public static function get_code(): int {
        return 50;
    }

    public function get_transitions(): array {
        return [
            transition::to(new in_progress($this->object))->with_conditions([
                not_all_sections_complete::class,
                at_least_one_section_started::class,
            ]),

            transition::to(new not_started($this->object))->with_conditions([
                no_sections_complete::class,
            ]),
        ];
    }

    public function update_progress(): void {
        // Doesn't matter if someone updates progress, we leave it not submitted.
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
