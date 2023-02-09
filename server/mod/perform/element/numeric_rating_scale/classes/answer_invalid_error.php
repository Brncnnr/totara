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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package performelement_numeric_rating_scale
 */

namespace performelement_numeric_rating_scale;

use mod_perform\models\response\element_validation_error;

class answer_invalid_error extends element_validation_error {

    /** @var string */
    public const ANSWER_INVALID = 'ANSWER_INVALID';

    /**
     * answer_invalid_error constructor.
     */
    public function __construct() {
        $error_code = self::ANSWER_INVALID;
        $error_message = get_string('error:answer_invalid', 'performelement_numeric_rating_scale');

        parent::__construct($error_code, $error_message);
    }

}