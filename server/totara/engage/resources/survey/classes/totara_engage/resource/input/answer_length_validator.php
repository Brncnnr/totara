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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package engage_survey
 */
namespace engage_survey\totara_engage\resource\input;


use totara_engage\resource\input\name_length_validator;

class answer_length_validator extends name_length_validator {
    /**
     * @param array $questions
     * @return bool
     */
    public function is_valid($questions): bool {
        // As survey only has one question, safely get first item.
        $question = reset($questions);

        if (!is_array($question['options']) || empty($question['options'])) {
            debugging("The value for options is not array or is empty", DEBUG_DEVELOPER);
            return false;
        }

        foreach ($question['options'] as $option) {
            if (\core_text::strlen(trim($option)) > $this->character_length) {
                return false;
            }
        }

        return true;
    }
}