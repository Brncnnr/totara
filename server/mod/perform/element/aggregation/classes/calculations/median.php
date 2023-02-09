<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package performelement_aggregation
 */

namespace performelement_aggregation\calculations;

use performelement_aggregation\calculation_method;

class median extends calculation_method {
    
    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('calculation_label_median', 'performelement_aggregation');
    }
    
    /**
     * @inheritDoc
     */
    public function aggregate(array $values): float {
        if (count($values) === 0) {
            return 0;
        }

        sort($values, SORT_NUMERIC);
        $count = count($values);
        $median_pos = floor(($count - 1) / 2);

        if ($count % 2 !== 0) {
            // Odd count, return the middle index.
            return $values[$median_pos];
        }

        // Even, average the middle two indexes.
        $low = $values[$median_pos];
        $high = $values[$median_pos + 1];

        return ($low + $high) / 2;
    }

}
