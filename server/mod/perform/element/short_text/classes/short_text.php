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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace performelement_short_text;

use core\collection;
use mod_perform\models\activity\element;
use core_text;
use mod_perform\models\activity\respondable_element_plugin;

class short_text extends respondable_element_plugin {

    public const MAX_ANSWER_LENGTH = 1024;

    /**
     * @inheritDoc
     */
    public function validate_response(
        ?string $encoded_response_data,
        ?element $element,
        $is_draft_validation = false
    ): collection {
        $element_data = $element->data ?? null;
        $answer_text = $this->decode_response($encoded_response_data, $element_data);

        $errors = new collection();
        $is_empty_answer = is_null($answer_text) || trim($answer_text) === '';
        if ($this->fails_required_validation($is_empty_answer, $element, $is_draft_validation)) {
            $errors->append(new answer_required_error());
        }

        if (core_text::strlen($answer_text) > self::MAX_ANSWER_LENGTH) {
            $errors->append(new answer_length_exceeded_error());
        }
        return $errors;
    }

    /**
     * Pull the answer text string out of the encoded json data.
     *
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return string|null
     */
    public function decode_response(?string $encoded_response_data, ?string $encoded_element_data): ?string {
        return $this->decode_simple_string_response($encoded_response_data);
    }

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 20;
    }
}
