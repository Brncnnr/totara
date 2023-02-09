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

namespace mod_perform\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\formatter\activity\element_plugin as element_plugin_formatter;
use mod_perform\models\activity\element_plugin as element_plugin_model;

class element_plugin extends type_resolver {

    /**
     * @param string $field
     * @param element_plugin_model $element_plugin
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $element_plugin, array $args, execution_context $ec) {
        if (!is_subclass_of($element_plugin, element_plugin_model::class)) {
            throw new \coding_exception('Expected element plugin model subclass');
        }

        $format = $args['format'] ?? format::FORMAT_HTML;
        $context = $ec->has_relevant_context() ? $ec->get_relevant_context() : \context_system::instance();
        $formatter = new element_plugin_formatter($element_plugin, $context);
        return $formatter->format($field, $format);
    }
}