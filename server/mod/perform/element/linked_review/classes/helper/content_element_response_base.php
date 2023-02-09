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

/**
 * Base class for content element responses.
 *
 * @package performelement_linked_review\helpers
 */
class content_element_response_base {

    /**
     * Decode content responses.
     *
     * @param string|null $encoded_response_data
     * @param string $repeating_item_identifier
     *
     * @return array|null
     */
    protected function decode_content_responses(?string $encoded_response_data, string $repeating_item_identifier): ?array {
        $decoded_response = json_decode($encoded_response_data, true);

        if (empty($decoded_response['response'][$repeating_item_identifier])) {
            return null;
        }

        return $decoded_response['response'][$repeating_item_identifier];
    }
}