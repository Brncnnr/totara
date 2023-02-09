<?php
/*
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

namespace mod_perform\models\activity;

use coding_exception;

/**
 * Trait single_select_element_trait
 *
 * Helper methods for element plugins that have an array of options and accept a single answer (radio buttons).
 *
 * @package mod_perform\models\activity
 */
trait single_select_element_plugin_trait {

    /**
     * Decode the response.
     *
     * @see \mod_perform\models\activity\respondable_element_plugin::decode_response()
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return string|array
     */
    public function decode_response(?string $encoded_response_data, ?string $encoded_element_data) {
        $selected_option = $this->get_selected_option($encoded_response_data, $encoded_element_data);

        return $selected_option ? $selected_option['value'] : null;
    }

    protected function get_selected_option(?string $encoded_response_data, ?string $encoded_element_data): ?array {
        $response_data = is_null($encoded_response_data) ? null : json_decode($encoded_response_data, true);
        $element_data = is_null($encoded_element_data) ? null : json_decode($encoded_element_data, true);

        if ($response_data === null) {
            return null;
        }

        if ($element_data === null || !isset($element_data['options'])) {
            throw new coding_exception('Invalid element data format, expected "options" field');
        }

        foreach ($element_data['options'] as $i => $option) {
            if (!array_key_exists('name', $option) || !array_key_exists('value', $option)) {
                throw new coding_exception('Invalid element options format, expected "name" and "value" fields');
            }

            // Note that name is a generated key, not the user supplied label.
            // The user supplied label is instead an entry in $option (['text' => 'Label', 'score' => '1']).
            if ($option['name'] === $response_data) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function get_example_response_data(): string {
        return '[]';
    }

}
