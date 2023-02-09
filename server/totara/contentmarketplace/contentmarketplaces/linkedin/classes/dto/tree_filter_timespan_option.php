<?php
/**
 * This file is part of Totara Core
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
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\dto;

use coding_exception;

final class tree_filter_timespan_option extends tree_filter_select_option {

    /**
     * @param timespan|null $minimum_range
     * @param timespan|null $maximum_range
     * @param string $label
     */
    public function __construct(?timespan $minimum_range, ?timespan $maximum_range, string $label) {
        if ($minimum_range === null && $maximum_range === null) {
            throw new coding_exception("A minimum_range or maximum_range must be specified.");
        }

        $range = [];
        if (isset($minimum_range)) {
            $range['min'] = $minimum_range->get();
        }
        if (isset($maximum_range)) {
            $range['max'] = $maximum_range->get();
        }
        $id = json_encode($range);

        parent::__construct($id, $label);
    }

}
