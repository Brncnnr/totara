<?php
/*
 * This file is part of Totara Perform
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
 * @author Angela Kuznetsova <angela.Kuznetsova@totaralearning.com>
 * @package performelement_custom_rating_scale
 */

namespace performelement_custom_rating_scale;

use core\collection;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\element_weka_helper;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\models\activity\single_select_element_plugin_trait;

class custom_rating_scale extends respondable_element_plugin {
    use single_select_element_plugin_trait;

    /**
     * @inheritDoc
     */
    public function process_data(element_entity $element): ?string {
        return element_weka_helper::add_weka_html_to_data_iterable(
            $element,
            'options',
            'descriptionWekaDoc',
            'descriptionHtml'
        );
    }

    /**
     * @inheritDoc
     */
    public function validate_response(
        ?string $encoded_response_data,
        ?element $element,
        $is_draft_validation = false
    ): collection {
        $element_data = $element->data ?? null;
        $answer_option = $this->decode_response($encoded_response_data, $element_data);

        $errors = new collection();

        if ($this->fails_required_validation(empty($answer_option), $element, $is_draft_validation)) {
            $errors->append(new answer_required_error());
        }

        return $errors;
    }

    /**
     * @inheritDoc
     */
    public function format_response_lines(?string $encoded_response_data, ?string $encoded_element_data): array {
        $decoded_response = $this->decode_response($encoded_response_data, $encoded_element_data);

        if ($decoded_response === null) {
            return [];
        }

        $response_string = get_string(
            'answer_output',
            'performelement_custom_rating_scale',
            [
                'label' => $decoded_response['text'],
                'count' => $decoded_response['score']
            ]
        );

        return [$response_string];
    }

    /**
     * @inheritDoc
     */
    public function get_participant_print_component(): string {
        return $this->get_participant_form_component();
    }

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 60;
    }
    
    /**
     * @inheritDoc
     */
    public function get_is_aggregatable(): bool {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function get_aggregatable_value(?string $encoded_response_data, ?string $encoded_element_data): ?float {
        $decoded_response = $this->decode_response($encoded_response_data, $encoded_element_data);

        if ($decoded_response === null) {
            return null;
        }

        return $decoded_response['score'];
    }

}