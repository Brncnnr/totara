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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\webapi\resolver\type;

use coding_exception;
use core\format;
use core\webapi\execution_context;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\type_resolver;
use invalid_parameter_exception;
use performelement_linked_review\content_type as type;

class content_type extends type_resolver {

    /**
     * @param string $field
     * @param string $type
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $type, array $args, execution_context $ec) {
        if (!is_subclass_of($type, type::class)) {
            throw new coding_exception($type . ' must implement ' . type::class);
        }

        switch ($field) {
            case 'identifier':
                return $type::get_identifier();
            case 'display_name':
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                $string_formatter = new string_field_formatter($format, $ec->get_relevant_context());
                return $string_formatter->format($type::get_display_name());
            case 'is_enabled':
                return $type::is_enabled();
            case 'admin_settings_component':
                return $type::get_admin_settings_component();
            case 'admin_view_component':
                return $type::get_admin_view_component();
            case 'content_picker_component':
                return $type::get_content_picker_component();
            case 'participant_content_component':
                return $type::get_participant_content_component();
            case 'participant_content_footer_component':
                return $type::get_participant_content_footer_component();
            case 'admin_content_footer_component':
                return $type::get_admin_content_footer_component();
            case 'available_settings':
                return json_encode($type::get_available_settings());
            default:
                throw new invalid_parameter_exception("Invalid field '$field' passed to " . self::class);
        }
    }

}
