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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\formatter\response;

use core\format;
use core\orm\formatter\entity_model_formatter;
use mod_perform\models\response\section_element_response as section_element_response_model;

/**
 * Class section_element_response
 *
 * @package mod_perform\formatter\response
 * @property section_element_response_model object
 */
class section_element_response extends entity_model_formatter {

    protected function get_map(): array {
        return [
            'section_element_id' => null,
            'element' => null,
            'sort_order' => null,
            'can_respond' => null,
            'response_data' => function ($value, $format) {
                $formatter = element_response_formatter::get_instance($this->object->element, $format);

                if ($this->object->exists()) {
                    $formatter->set_response_id($this->object->id);
                }
                $formatter->set_element($this->object->element);

                return $formatter->format($value);
            },
            'response_data_formatted_lines' => function ($value, $format) {
                $formatter = element_response_lines_formatter::get_instance($this->object->element, $format ?? format::FORMAT_PLAIN);

                if ($this->object->exists()) {
                    $formatter->set_response_id($this->object->id);
                }
                $formatter->set_element($this->object->element);

                return $formatter->format($value);
            },
            'participant_instance' => null,
            'other_responder_groups' => null,
            'visible_to' => null,
            'validation_errors' => null,
        ];
    }

}