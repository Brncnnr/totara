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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

namespace core\hook;

use totara_core\hook\base;

/**
 * Class theme_settings_css_categories
 * Hook to set which theme settings categories contains any CSS variables that needs to be
 * included in the CSS stylesheet.
 *
 * Category array entries must have the following format:
 *  key: Name of category
 *  value options:
 *      '*' - indicates that all properties in category are treated as CSS variables
 *      []  - Array of property names that are CSS variables
 *
 * Example:
 *  [
 *      'colours' => '*',
 *      'your_new_category' => [
 *          'property_name_1' => [],
 *          'property_name_2' => []
 *      ],
 *  ]
 *
 * Note that the property names are keys with array values as some properties can have settings.
 * Example:
 *  [
 *      'category' => [
 *          'property1' => [
 *              'setting_name' => setting_value
 *          ],
 *          'property2' = [],
 *      ]
 *  ]
 *
 * Property settings:
 *   transform - Default is true. Indicates that this property needs to be transformed into a `--name: value;` pair.
 */
class theme_settings_css_categories extends base {

    /**
     * @var array $categories Array of categories.
     */
    private $categories;

    public function __construct(array $categories) {
        $this->categories = $categories;
    }

    /**
     * @param array $categories New set of categories
     */
    public function set_categories(array $categories) {
        $this->categories = $categories;
    }

    /**
     * @param string $key
     * @param string|array $properties
     */
    public function add_category(string $key, $properties) {
        $this->categories[$key] = $properties;
    }

    /**
     * @return array
     */
    public function get_categories(): array {
        return $this->categories;
    }
}
