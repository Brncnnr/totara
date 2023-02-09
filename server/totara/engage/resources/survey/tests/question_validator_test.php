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
defined('MOODLE_INTERNAL') || die();

use engage_survey\totara_engage\resource\input\question_validator;
use totara_engage\answer\answer_type;

class engage_survey_question_validator_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_valid_input(): void {
        $validator = new question_validator();

        $questions = [
            [
                'value' => 'Hello world ??',
                'answertype' => answer_type::SINGLE_CHOICE,
                'options' => ['yes', 'no']
            ]
        ];

        $this->assertTrue($validator->is_valid($questions));
    }

    /**
     * @return void
     */
    public function test_invalid_input(): void {
        $validator = new question_validator();

        $this->assertFalse($validator->is_valid([]));
        $this->assertFalse($validator->is_valid(false));

        $this->assertDebuggingCalled();
    }
}