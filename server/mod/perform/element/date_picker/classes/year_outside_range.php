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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package performelement_date_picker
 */
namespace performelement_date_picker;

use mod_perform\models\response\element_validation_error;

class year_outside_range extends element_validation_error {

    public const YEAR_OUTSIDE_RANGE = 'YEAR_OUTSIDE_RANGE';

    public function __construct(int $min_year, int $max_year) {
        $error_code = self::YEAR_OUTSIDE_RANGE;
        $error_message = get_string(
            'error_year_outside_range',
            'performelement_date_picker',
            ['min_year' => $min_year, 'max_year' => $max_year]
        );

        parent::__construct($error_code, $error_message);
    }

}