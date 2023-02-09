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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package performelement_multi_choice_multi
 */

/**
 * Unwraps element_response.response_data json, to simple json encoded strings.
 * This removed the need for unwrapping code in client side components and server side validation and formatting.
 *
 * answer_text: long_text, short_text
 * answer_value: numeric_rating_scale
 * answer_option: custom_rating_scale, multi_choice_single, multi_choice_multi
 * date: date_picker
 */
function performelement_multi_choice_multi_change_data() {
    global $DB;

    $elements = $DB->get_recordset_select('perform_element', "plugin_name = ?", ['multi_choice_multi']);
    foreach ($elements as $element) {
        $decoded_data = json_decode($element->data, true);

        if (!is_array($decoded_data)) {
            continue;
        }

        // move settings to flat structure
        if (isset($decoded_data['settings'])) {
            foreach ($decoded_data['settings'] as $setting) {
                $decoded_data[$setting['name']] = $setting['value'];
            }
            unset($decoded_data['settings']);
        }

        $new_data = json_encode($decoded_data);

        $DB->set_field(
            'perform_element',
            'data',
            $new_data,
            ['id' => $element->id]
        );
    }
}