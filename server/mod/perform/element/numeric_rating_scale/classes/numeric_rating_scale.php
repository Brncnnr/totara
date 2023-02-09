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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package performelement_numeric_rating_scale
 */

namespace performelement_numeric_rating_scale;

use coding_exception;
use core\collection;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\element_weka_helper;
use mod_perform\models\activity\respondable_element_plugin;

class numeric_rating_scale extends respondable_element_plugin {

    /**
     * @inheritDoc
     */
    public function validate_response(
        ?string $encoded_response_data,
        ?element $element,
        $is_draft_validation = false
    ): collection {
        $element_data = $element->data ?? null;
        $answer_value = $this->decode_response($encoded_response_data, $element_data);

        if ($element === null) {
            throw new coding_exception('Invalid element data format, expected "options" field');
        }
        $errors = new collection();
        if ($this->fails_required_validation($answer_value === '' || is_null($answer_value), $element, $is_draft_validation)) {
            $errors->append(new answer_required_error());
        }

        if (!is_null($answer_value) && $answer_value !== '') {
            $this->validate_value((int) $answer_value, $element, $errors);
        }

        return $errors;
    }

    /**
     * Get the answer value from encoded json data.
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return string|string[]
     */
    public function decode_response(?string $encoded_response_data, ?string $encoded_element_data) {
        return json_decode($encoded_response_data ?? '', true);
    }

    /**
     * @param int $answer_value
     * @param element $element
     * @param collection $errors
     */
    protected function validate_value(?int $answer_value, element $element, collection $errors): void {
        $data = json_decode($element->data ?? '', true);

        // If element does not have any data then we have a problem.
        if (empty($data) || !isset($data['lowValue']) || !isset($data['highValue'])) {
            throw new coding_exception('Invalid element data');
        }

        $low = $data['lowValue'];
        $high = $data['highValue'];

        // Confirm that the response value is in valid range.
        if (!is_null($answer_value) && ($answer_value < $low || $answer_value > $high)) {
            $errors->append(new answer_invalid_error());
        }
    }

    /**
     * @inheridDoc
     */
    public function process_data(element_entity $element): ?string {
        return element_weka_helper::add_weka_html_to_data(
            $element,
            'descriptionWekaDoc',
            'descriptionHtml'
        );
    }

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 50;
    }

    /**
     * @inheritDoc
     */
    public function is_response_required_enabled(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get_is_aggregatable(): bool {
        return true;
    }
}
