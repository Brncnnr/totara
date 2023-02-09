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

use coding_exception;
use mod_perform\entity\activity\element as element_entity;

/**
 * This class is used to specify where a class can be used. Either as child element or as top level element.
 * This can be overridden in the individual element plugin class.
 *
 * @property-read bool $can_be_child_element Element can be used as child element.
 * @property-read bool $can_be_top_level_element Element can be used as top level element.
 *
 * @package mod_perform\models\activity\helpers
 */
class element_usage {

    /**
     * If the element can be used as a child element.
     *
     * @return bool
     */
    public function get_can_be_child_element(): bool {
        return true;
    }

    /**
     * If the element can be used as a top level element.
     *
     * @return bool
     */
    public function get_can_be_top_level_element(): bool {
        return true;
    }

    /**
     * Checks if element plugin is a compatible child element for the provided plugin_name and data.
     *
     * @param string $plugin_name
     * @param string|null $data
     *
     * @return bool
     */
    public function is_compatible_child_element(string $plugin_name, ?string $data): bool {
        return $this->can_be_child_element;
    }

    /**
     * Validates the element usage as top level or child element.
     *
     * @param element_entity $element
     */
    public function validate_element_usage(element_entity $element) {
        if (!empty($element->parent) && !$this->can_be_child_element) {
            throw new coding_exception("$element->plugin_name can not be used as a child element.");
        }
        if (empty($element->parent) && !$this->can_be_top_level_element) {
            throw new coding_exception("$element->plugin_name can not be used as a top level element.");
        }
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
