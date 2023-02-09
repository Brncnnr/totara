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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package performelement_aggregation
 */

namespace performelement_aggregation\formatter;

use mod_perform\formatter\response\element_response_formatter;
use performelement_aggregation\aggregation;

/**
 * @package performelement_aggregation\formatter
 */
class response_formatter extends element_response_formatter {

    /**
     * {@inheritdoc}
     */
    protected function get_default_format($value) {
        $aggregations = json_decode($value, true);
        if (!is_array($aggregations)) {
            // Decoding didn't work just return the original value
            return $value;
        }

        return json_encode(aggregation::get_formatted_response_lines($aggregations), JSON_THROW_ON_ERROR);
    }
}