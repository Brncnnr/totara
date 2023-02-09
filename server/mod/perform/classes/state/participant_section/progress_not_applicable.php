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

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents the "not applicable" progress status of a participant section, for view-only participants.
 *
 * @package mod_perform
 */
class progress_not_applicable extends participant_section_progress {

    public static function get_name(): string {
        return 'PROGRESS_NOT_APPLICABLE';
    }

    public static function get_code(): int {
        return 70;
    }

    public function get_transitions(): array {
        return [
        ];
    }

    public function complete(): void {
        // Not applicable. Do nothing.
    }

    public function on_participant_access(): void {
        // Not applicable. Do nothing.
    }

    public function manually_complete(): void {
        // Not applicable. Do nothing.
    }

    public function manually_uncomplete(): void {
        // Not applicable. Do nothing.
    }

}
