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

namespace mod_perform\state\participant_section\condition;

use mod_perform\models\response\participant_section;
use mod_perform\state\condition;

defined('MOODLE_INTERNAL') || die();

/**
 * Class no_answers_provided
 */
class no_answers_provided extends condition {

    public function pass(): bool {
        /** @var participant_section $participant_section */
        $participant_section = $this->object;

        $section_element_responses = $participant_section->section_element_responses;

        return $section_element_responses->count() == 0;
    }
}
