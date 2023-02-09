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

namespace mod_perform\models\activity\helpers;

/**
 * Holds the configuration used for child element responses.
 *
 * @package mod_perform\models\activity
 * @property-read bool $supports_child_elements
 * @property-read bool $supports_repeating_child_elements
 * @property-read string|null $repeating_item_identifier
 * @property-read string|null $child_element_responses_identifier
 */
class child_element_config {

    /**
     * If element supports child elements.
     *
     * @return bool
     */
    public function get_supports_child_elements(): bool {
        return false;
    }

    /**
     * If the element supports repeating child elements.
     *
     * @return bool
     */
    public function get_supports_repeating_child_elements(): bool {
        return false;
    }

    /**
     * String identifier used to group repeating child element responses.
     *
     * @return string|null
     */
    public function get_repeating_item_identifier(): ?string {
        if (!$this->supports_repeating_child_elements) {
            return null;
        }

        return 'repeaterId';
    }

    /**
     * String identifier used to group child element responses.
     *
     * @return string|null
     */
    public function get_child_element_responses_identifier(): ?string {
        if (!$this->supports_child_elements) {
            return null;
        }

        return 'childElementResponses';
    }

    /**
     * Magic attribute getter
     *
     * @param string $field
     * @return mixed|null
     */
    public function __get(string $field) {
        $get_method = 'get_' . $field;

        return method_exists($this, $get_method)
            ? $this->$get_method()
            : null;
    }
}
