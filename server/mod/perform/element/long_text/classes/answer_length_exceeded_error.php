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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package performelement_short_text
 */

namespace performelement_long_text;

use mod_perform\models\response\element_validation_error;

/**
 * @deprecated since Totara 13.3
 */
class answer_length_exceeded_error extends element_validation_error {

    /**
     * @deprecated since Totara 13.3
     */
    public const LENGTH_EXCEEDED = 'LENGTH_EXCEEDED';

    /**
     * @deprecated since Totara 13.3
     */
    public function __construct() {
        debugging(__CLASS__ . ' is deprecated and should no longer be used. There is no alternative.', DEBUG_DEVELOPER);
        $error_code = self::LENGTH_EXCEEDED;
        $error_message = get_string('error_question_length_exceed', 'performelement_short_text');

        parent::__construct($error_code, $error_message);
    }
}