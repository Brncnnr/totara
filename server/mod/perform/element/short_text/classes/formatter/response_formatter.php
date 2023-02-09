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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace performelement_short_text\formatter;

use coding_exception;
use core\format;
use core\webapi\formatter\field\string_field_formatter;
use mod_perform\formatter\response\element_response_formatter;

/**
 * Formats user entered responses for this element.
 */
class response_formatter extends element_response_formatter {

    /**
     * {@inheritdoc}
     */
    protected function get_default_format($value) {
        if ($value === 'null') {
            return null;
        }

        $decoded_value = json_decode($value, true);

        if (!$decoded_value || !is_string($decoded_value)) {
            return $value;
        }

        $format = $this->format ?? format::FORMAT_PLAIN;
        $formatter = new string_field_formatter($format, $this->context);

        $formatted_value = $formatter->format($decoded_value);
        $formatted_value = json_encode($formatted_value);

        if ($formatted_value === false) {
            throw new coding_exception('Error encoding the formatted response');
        }

        return $formatted_value;
    }

}
