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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\webapi\resolver\type;

use coding_exception;
use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\type_resolver;

class catalog_import_learning_objects_result extends type_resolver {

    /**
     * @param string $field
     * @param array $result_data
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $result_data, array $args, execution_context $ec) {
        switch ($field) {
            case 'selected_filters':
                // Special handling for the selected_filters filter labels.
                // We use the system context, as the filter option strings are sourced from LinkedIn Learning and aren't internal.
                $context = context_system::instance();
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                $formatter = new string_field_formatter($format, $context);

                return array_map(static function ($filter_name) use ($formatter) {
                    return $formatter->format($filter_name);
                }, $result_data['selected_filters']);

            case 'items':
            case 'total':
            case 'next_cursor':
                return $result_data[$field];

            default:
                throw new coding_exception("Field '{$field}' doesn't exist on type catalog_import_learning_objects_result");
        }
    }

}
