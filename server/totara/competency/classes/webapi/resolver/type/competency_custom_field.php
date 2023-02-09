<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use core\webapi\formatter\field\string_field_formatter;

class competency_custom_field extends type_resolver {

    public static function resolve(string $field, $competency_field, array $args, execution_context $ec) {
        switch ($field) {
            case 'title':
                return (new string_field_formatter($args['format'] ?? format::FORMAT_PLAIN, \context_system::instance()))
                    ->format($competency_field->title);
            case 'value':
                // Formatting of the value is done by the 'display_item_data' function of each custom field type
                return $competency_field->value;
            case 'type':
                return $competency_field->type;
            default:
                throw new \coding_exception('Unknown field', $field);
        }
    }

}
