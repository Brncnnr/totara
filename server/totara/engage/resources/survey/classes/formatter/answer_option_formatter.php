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
use totara_engage\entity\answer_option;

/**
 * Class answer_option_formatter
 * @package engage_survey\formatter
 */
final class answer_option_formatter extends formatter {
    /**
     * answer_option_formatter constructor.
     * @param answer_option $answer_option
     * @param \context      $context
     */
    public function __construct(answer_option $answer_option, \context $context) {
        $record = new \stdClass();

        $record->id = $answer_option->id;
        $record->value = $answer_option->value;
        $record->questionid = $answer_option->questionid;

        parent::__construct($record, $context);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'value' => string_field_formatter::class,
            'questionid' => null
        ];
    }
}
