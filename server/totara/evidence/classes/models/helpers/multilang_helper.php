<?php
/**
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
 * @package totara_evidence
 */

namespace totara_evidence\models\helpers;

class multilang_helper {

    /**
     * Get the multi-lang string value from a specified potential string identifier.
     *
     * @param string      $string String to parse to get the language string identifier
     * @param string|null $prefix Optional prefix to add to the string identifier
     * @param bool|null   $raw    Return the string without formatting.
     *                            Must call format_string() or format_text() after calling this if outputting.
     *
     * @return string The specified multi-lang string, or just the original specified string if no language string was found
     */
    public static function parse_string(string $string, string $prefix = '', bool $raw = false): string {
        if (preg_match('/^multilang:/', $string)) {
            $lang_string = $prefix . preg_replace('/^multilang:/', '', $string);

            if (get_string_manager()->string_exists($lang_string, 'totara_evidence')) {
                return get_string($lang_string, 'totara_evidence');
            }
        }

        return $raw ? $string : format_string($string);
    }

    /**
     * Return a language string if the specified evidence type name contains one, or otherwise just return the name.
     *
     * @param string $type_name
     * @param bool $raw Return the string without formatting. (true = without formatting, false = with formatting)
     *
     * @return string
     */
    public static function parse_type_name_string(string $type_name, bool $raw = false): string {
        return self::parse_string($type_name, 'system_type_name:', $raw);
    }

    /**
     * Return a language string if the specified evidence type description contains one, or otherwise just return the description.
     *
     * @param string $type_description
     * @return string
     */
    public static function parse_type_description_string(string $type_description): string {
        return self::parse_string($type_description, 'system_type_desc:', true);
    }

    /**
     * Return a language string if the specified evidence custom field name contains one, or otherwise just return the name.
     *
     * @param string $field_name
     * @return string
     */
    public static function parse_field_name_string(string $field_name): string {
        return self::parse_string($field_name, 'system_field_name:');
    }

}
