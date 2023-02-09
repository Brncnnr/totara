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

namespace mod_perform\state\participant_instance\condition;

use mod_perform\entity\activity\participant_section;
use mod_perform\state\condition;
use mod_perform\state\participant_section\complete;
use mod_perform\state\participant_section\in_progress;
use mod_perform\state\participant_section\not_started;
use mod_perform\state\participant_section\not_submitted;
use mod_perform\state\participant_section\progress_not_applicable;

defined('MOODLE_INTERNAL') || die();

/**
 * Class all_sections_complete
 */
class all_sections_complete extends condition {

    public function pass(): bool {
        /** @var participant_section[] $sections */
        $sections = $this->object->participant_sections->all();

        // If no participant section is found (e.g. no manager assigned, and only manager is involved) then
        // we do NOT automatically mark the participant instance complete.
        $section_found = false;

        foreach ($sections as $section) {
            switch ($section->progress) {
                case not_started::get_code():
                case in_progress::get_code():
                    // The participant section is incomplete, so obviously the participant instance is incomplete.
                    return false;
                case complete::get_code():
                case not_submitted::get_code():
                    $section_found = true;
                    break;
                case progress_not_applicable::get_code():
                    // The participant section is view-only, so treated as if it doesn't exist.
                    break;
                default:
                    throw new \coding_exception('Unexpected participant section progress encountered');
            }
        }

        return $section_found;
    }
}
