<?php
/**
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace performelement_linked_review\helper;

use coding_exception;
use core\collection;
use mod_perform\models\activity\element;

/**
 * This class the responsible for validating content child element responses.
 *
 * @package performelement_linked_review\helpers
 */
class content_element_response_validator extends content_element_response_base {

    /**
     * Child elements.
     *
     * @var array
     */
    private $child_elements = [];

    /**
     * Validate responses.
     *
     * @param string|null $encoded_response_data
     * @param element|null $element
     * @param false $is_draft_validation
     *
     * @return collection
     */
    public function validate_responses(
        ?string $encoded_response_data,
        ?element $element,
        $is_draft_validation = false
    ): collection {
        if (is_null($encoded_response_data) || is_null($element)) {
            return new collection();
        }
        $this->set_child_elements($element);
        $child_element_config = $element->element_plugin->get_child_element_config();
        $repeating_item_identifier = $child_element_config->repeating_item_identifier;
        $child_element_responses_identifier = $child_element_config->child_element_responses_identifier;
        $content_responses = $this->decode_content_responses($encoded_response_data, $repeating_item_identifier);

        if (empty($content_responses)) {
            return new collection();
        }
        $validation_errors = [];

        foreach ($content_responses as $content_response) {
            if (empty($content_response[$child_element_responses_identifier])) {
                continue;
            }
            foreach ($content_response[$child_element_responses_identifier] as $child_element_response) {
                $child_validation_errors = $this->validate_child_element_response($child_element_response, $is_draft_validation);

                if (!empty($child_validation_errors)) {
                    array_push($validation_errors, ...$child_validation_errors);
                }
            }
        }

        return new collection($validation_errors);
    }

    /**
     * Set the child elements of the element.
     *
     * @param element $element
     * @return void
     */
    private function set_child_elements(element $element): void {
        $this->child_elements = $element->get_children()->key_by('id')->all(true);
    }

    /**
     * Validates the child element response.
     *
     * @param $child_element_response
     * @param bool $is_draft_validation
     *
     * @return array
     */
    private function validate_child_element_response($child_element_response, bool $is_draft_validation): array {
        $element_id = $child_element_response['child_element_id'];

        if(empty($this->child_elements[$element_id])) {
            throw new coding_exception("Child element does not exist.");
        }

        /** @var element $child_element */
        $child_element = $this->child_elements[$element_id];

        if (!$child_element->element_plugin->get_is_respondable()) {
            return [];
        }

        return $child_element->element_plugin->validate_response(
            $child_element_response['response_data'],
            $child_element,
            $is_draft_validation
        )->all();
    }
}