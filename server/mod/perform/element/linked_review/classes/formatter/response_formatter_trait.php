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

namespace performelement_linked_review\formatter;

use coding_exception;
use core\webapi\formatter\field\base;
use mod_perform\formatter\response\response_formatter_trait as perform_response_formatter_trait;
use mod_perform\models\activity\element;

/**
 * This trait is used to aggregate repeating child element for linked_review responses and formatted_response_lines.
 *
 * @package performelement_linked_review\formatter
 */
trait response_formatter_trait {

    /**
     * Aggregates formatted responses.
     *
     * @param string|null $value
     * @return string|null
     */
    protected function format_value(?string $value): ?string {
        if ($value === null) {
            return null;
        }
        $content_responses = json_decode($value, true);
        $child_element_responses_identifier = $this
            ->get_element()
            ->element_plugin
            ->get_child_element_config()
            ->get_child_element_responses_identifier();
        $repeating_item_identifier = $this
            ->get_element()
            ->element_plugin
            ->get_child_element_config()
            ->repeating_item_identifier;

        if (empty($content_responses[$repeating_item_identifier])) {
            return null;
        }

        foreach ($content_responses[$repeating_item_identifier] as $content_id => &$content_response) {
            if (empty($content_response[$child_element_responses_identifier])) {
                continue;
            }

            foreach ($content_response[$child_element_responses_identifier] as $response_index => &$child_element_response) {
                /** @var element $element*/
                $element = $this->get_element()
                    ->children
                    ->find('id', $child_element_response['child_element_id']);
                $response = $child_element_response['response_data'];

                /** @var perform_response_formatter_trait|base $child_element_formatter*/
                $child_element_formatter = $this->get_child_element_formatter($element);
                $child_element_formatter->set_response_id($this->get_response_id());
                $child_element_formatter->set_element($this->get_element());
                $child_element_response['response_data'] = $child_element_formatter->format($response);
            }
        }

        return json_encode($content_responses);
    }

    /**
     * Get element formatter for child element.
     *
     * @param element $element
     *
     * @return base
     * @throws coding_exception
     */
    protected function get_child_element_formatter(element $element): base {
        throw new coding_exception('You have to implement this function in the class using this trait.');
    }
}