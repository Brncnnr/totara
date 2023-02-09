<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_date_picker
 */

/**
 * Add explicit year range config to any active date picker elements (new defaults don't match).
 */
function performelement_date_picker_maintain_active_date_picker_year_ranges() {
    global $DB;

    $date_picker_elements = $DB->get_recordset('perform_element', ['plugin_name' => 'date_picker']);

    // Based on previously hard codded values in DatePickerParticipantForm.vue
    //   midrangeYear: 2000,
    //   midrangeYearBefore: 100,
    //   midrangeYearAfter: 50,
    $data_to_add = [
        'yearRangeStart' => 1900, // (equivalent to 2000 midrangeYear - 100 midrangeYearBefore settings)
        'yearRangeEnd' => 2050, // (equivalent to 2000 midrangeYear + 50 midrangeYearAfter settings)
    ];

    foreach ($date_picker_elements as $date_picker_element) {
        $new_value = null;
        if (empty($date_picker_element->data) || $date_picker_element->data === 'null') {
            // Data is completely empty, so don't need to merge anything.
            $new_value = $data_to_add;
        } else {
            $data = json_decode($date_picker_element->data, true);

            if (is_array($data)
                && !array_key_exists('yearRangeStart', $data)
                && !array_key_exists('yearRangeEnd', $data)
            ) {
                $new_value = array_merge($data, $data_to_add);
            }
        }

        if (!empty($new_value)) {
            $DB->set_field(
                'perform_element',
                'data',
                json_encode($new_value, JSON_THROW_ON_ERROR),
                ['id' => $date_picker_element->id]
            );
        }
    }
}
