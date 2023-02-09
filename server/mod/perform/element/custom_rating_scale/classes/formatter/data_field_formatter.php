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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package performelement_custom_rating_scale
 */

namespace performelement_custom_rating_scale\formatter;

use core\webapi\formatter\field\base;
use core\webapi\formatter\field\string_field_formatter;

/**
 * This formatter runs the names of the options through the string field formatter
 *
 * @package performelement_custom_rating_scale\formatter
 */
class data_field_formatter extends base {

    /**
     * Goes through all options and runs format on all names
     *
     * @param string $value the json encoded data
     * @return false|string
     */
    protected function get_default_format($value) {
        $formatter = new string_field_formatter($this->format, $this->context);

        $options = json_decode($value, true);
        // Decoding didn't work just return the original value
        if (!is_array($options) || !isset($options['options'])) {
            return $value;
        }

        foreach ($options['options'] as $key => $option) {
            if (!array_key_exists('value', $option)) {
                throw new \coding_exception('Option does not exist');
            }
            if (!isset($option['value']['text'])) {
                throw new \coding_exception('Option text does not exist');
            }
            $options['options'][$key]['value']['text']
                = $formatter->format($option['value']['text']);
        }

        $options = json_encode($options);
        if ($options === false) {
            throw new \coding_exception('Error encoding the formatted options');
        }

        return $options;
    }

}