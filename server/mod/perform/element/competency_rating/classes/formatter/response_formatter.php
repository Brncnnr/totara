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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package performelement_competency_rating
 *
 */

namespace performelement_competency_rating\formatter;

use core\format;
use core\webapi\formatter\field\string_field_formatter;
use mod_perform\formatter\response\element_response_formatter;

class response_formatter extends element_response_formatter {

    protected function get_default_format($value) {
        if ($value === 'null') {
            return null;
        }

        $format = $this->format ?? format::FORMAT_PLAIN;
        $formatter = new string_field_formatter($format, $this->context);

        return $formatter->format($value);
    }
}