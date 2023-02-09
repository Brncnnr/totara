<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_survey
 */
namespace engage_survey\totara_engage\resource\input;

use totara_engage\resource\input\input_validator;

final class question_validator implements input_validator {
    /**
     * @param array $questions
     * @return bool
     */
    public function is_valid($questions): bool {
        if (!is_array($questions)) {
            debugging("Expecting the parameter \$questions to be an array", DEBUG_DEVELOPER);
            return false;
        }

        if (empty($questions)) {
            return false;
        }

        $keys = ['value', 'answertype'];

        // Run second level check on the questions.
        foreach ($questions as $item) {
            if (!is_array($item)) {
                debugging("Expecting an item of array questions to be another array", DEBUG_DEVELOPER);
                return false;
            }

            foreach ($keys as $key) {
                if (!array_key_exists($key, $item)) {
                    return false;
                }
            }
        }

        return true;
    }
}