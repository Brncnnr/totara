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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_survey
 */

namespace engage_survey\formatter;

use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;
use totara_engage\question\question;

/**
 * Formatter for the question
 */
final class question_formatter extends formatter {
    /**
     * question_formatter constructor.
     * @param question $question
     * @param \context $context
     */
    public function __construct(question $question, \context $context) {
        $record = new \stdClass();

        $record->id = $question->get_id();
        $record->value = $question->get_value();
        $record->answertype = $question->get_answer_type();

        parent::__construct($record, $context);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'value' => string_field_formatter::class,
            'answertype' => null
        ];
    }
}