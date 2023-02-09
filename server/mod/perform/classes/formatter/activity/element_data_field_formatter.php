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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\formatter\activity;

use core\webapi\formatter\field\base;
use mod_perform\models\activity\element;
use mod_perform\models\activity\element_plugin;

/**
 * Generic data field formatter for elements.
 * If this formatter is used direcly it won't format anything but just return the original value.
 *
 * Use the static method to get the correct formatter if it exists.
 *
 * @see \mod_perform\formatter\activity\element for an example on how to use it
 */
class element_data_field_formatter extends base {

    /**
     * Returns the classname of the data_field_formatter for the element if it has any otherwise return generic one
     *
     * @param element $element_model
     * @return string
     */
    public static function for_model(element $element_model) {
        return self::for_plugin($element_model->get_element_plugin());
    }

    /**
     * Returns the classname of the data_field_formatter for the element if it has any otherwise return generic one
     *
     * @param element_plugin $element_plugin
     * @return string
     */
    public static function for_plugin(element_plugin $element_plugin) {
        $plugin_name = $element_plugin->get_plugin_name();
        $formatter_class = "performelement_{$plugin_name}\\formatter\\data_field_formatter";

        // If the plugin does not implement it's own formatter use a blank one
        if (!class_exists($formatter_class)) {
            $formatter_class = __CLASS__;
        } else if (!is_subclass_of($formatter_class, base::class)) {
            throw new \coding_exception('The data_field_formatter must extend the base field formatter class');
        }

        return $formatter_class;
    }

}