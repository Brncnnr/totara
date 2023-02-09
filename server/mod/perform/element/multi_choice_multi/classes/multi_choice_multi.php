<?php
/*
 * This file is part of Totara Perform
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package performelement_multi_choice_multi
 */

namespace performelement_multi_choice_multi;

use core\collection;
use coding_exception;
use mod_perform\models\activity\element;
use mod_perform\models\activity\respondable_element_plugin;

class multi_choice_multi extends respondable_element_plugin {
    /**
     * @inheritDoc
     */
    public function validate_response(
        ?string $encoded_response_data,
        ?element $element,
        $is_draft_validation = false
    ): collection {
        $element_data = $element->data ?? null;
        $answer_option = $this->decode_response($encoded_response_data, $element_data);

        $errors = new collection();

        if ($this->fails_required_validation(empty($answer_option), $element, $is_draft_validation)) {
            $errors->append(new answer_required_error());
        }

        return $errors;
    }

    /**
     * Pull the answer text string out of the encoded json data.
     *
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return string|string[]
     * @throws coding_exception
     */
    public function decode_response(?string $encoded_response_data, ?string $encoded_element_data) {
        $response_data = $encoded_response_data === null ? null : json_decode($encoded_response_data, true);
        $element_data = $encoded_element_data === null ? null : json_decode($encoded_element_data, true);

        if ($response_data === null) {
            return null;
        }

        if (!is_array($response_data)) {
            throw new coding_exception('Invalid response data format, expected array of selected options');
        }

        if ($element_data === null || !isset($element_data['options'])) {
            throw new coding_exception('Invalid element data format, expected "options" field');
        }

        if (!is_array($element_data['options'])) {
            throw new coding_exception('Invalid element data format, expected "options" to be an array');
        }

        $responses = [];
        foreach ($element_data['options'] as $i => $option) {
            if (!isset($option['name'], $option['value'])) {
                throw new coding_exception('Invalid element options format, expected "name" and "value" fields');
            }

            foreach ($response_data as $answer_option) {
                if ($option['name'] == $answer_option) {
                    $responses[] = $option['value'];
                }
            }
        }

        return $responses;
    }

    /**
     * @inheritDoc
     */
    public function get_participant_print_component(): string {
        return $this->get_participant_form_component();
    }

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 40;
    }

    /**
     * @inheritDoc
     */
    public function get_example_response_data(): string {
        return '[]';
    }

}
