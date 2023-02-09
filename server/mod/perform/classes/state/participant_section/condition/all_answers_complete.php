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

use mod_perform\entity\activity\element_response;
use mod_perform\models\activity\section_element;
use mod_perform\models\response\participant_section;
use mod_perform\state\condition;

defined('MOODLE_INTERNAL') || die();

/**
 * Class all_answers_complete
 */
class all_answers_complete extends condition {

    public function pass(): bool {
        /** @var participant_section $participant_section */
        $participant_section = $this->object;

        $section_element_responses = $participant_section->section_element_responses;

        $section_elements = $participant_section->section->section_elements;
        $respondable_elements = $section_elements->filter(function (section_element $section_element_entity) {
            return $section_element_entity->element->is_respondable;
        });

        if (count($respondable_elements) !== count($section_element_responses)) {
            return false;
        }

        /** @var element_response $element_response */
        foreach ($section_element_responses as $section_element_response) {
            if (!$section_element_response->validate_response()) {
                return false;
            }
        }

        return true;
    }
}
