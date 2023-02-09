<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\placeholder;

use stdClass;
/** @var \core_config $CFG */
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

/**
 * Class for providing placeholder options from custom fields.
 */
class customfield_options {

    private static $customfield_cache;

    /**
     * Create options for all defined custom fields and return the list
     *
     * @param string $cf_table_prefix Custom field table prefix. This must be the same as used in the forms
     * @param string $placeholder_key_prefix Optional. Prefix to use in the placeholder option key to distinguish between fields when you need to include customfields from multiple groups
     * @param string $placeholder_label_prefix Optional. Prefix to use in the placeholder option label to distinguish between fields when you need to include customfields from multiple groups
     * @return option[]
     */
    public static function get_options(string $cf_table_prefix, string $placeholder_key_prefix = '', string $placeholder_label_prefix = ''): array {
        $customfields = static::get_field_definitions($cf_table_prefix);

        $cf_options = [];
        foreach ($customfields as $detail) {
            $label = static::format_label($detail, $placeholder_label_prefix, );
            $key = static::format_key($detail, $placeholder_key_prefix);
            $cf_options[] = option::create($key, $label);
        }

        return $cf_options;
    }

    /**
     * Return a list of [placeholder_key => cf_field] mappings. cf_field can be passed to @get_field_value to get the applicable value
     *
     * @param string $cf_table_prefix Custom field table prefix. This must be the same as used in the forms
     * @param string $placeholder_key_prefix Optional. Prefix to use in the placeholder option key. Must be the same as used int @get_options
     * @return array
     */
    public static function get_key_field_map(string $cf_table_prefix, string $placeholder_key_prefix = ''): array {
        $mappings = [];
        $customfields = static::get_field_definitions($cf_table_prefix);
        foreach ($customfields as $detail) {
            $key = static::format_key($detail, $placeholder_key_prefix);
            $mappings[$key] = $detail->shortname;
        }

        return $mappings;
    }

    /**
     * Retrieve and return all customfield values associated with the specific item
     *
     * @param int $item_id
     * @param string $cf_table_prefix Custom field table prefix. This must be the same as used in the forms
     * @param string $cf_prefix Custom field prefix. This must be the same as used in the forms
     * @param string $placeholder_key_prefix Optional. Prefix to use in the placeholder option key. Must be the same as used int @get_options
     */
    public static function get_all_values(int $item_id, string $cf_table_prefix, string $cf_prefix, string $placeholder_key_prefix = '') {
        $customfields = static::get_field_definitions($cf_table_prefix);
        $customfield_data = customfield_get_data((object)['id' => $item_id], $cf_table_prefix, $cf_prefix, false);

        // Return empty values for valid fields without data
        $values = [];
        foreach ($customfields as $detail) {
            $key = static::format_key($detail, $placeholder_key_prefix);

            $cftitle = $detail->shortname;
            $values[$key] = $customfield_data[$cftitle] ?? '';
        }

        return $values;
    }

    /**
     * Retrieve and return a specific customfield value for the item
     *
     * @param int $item_id
     * @param string $cf_table_prefix Custom field table prefix. This must be the same as used in the forms
     * @param string $cf_prefix Custom field prefix. This must be the same as used in the forms
     * @return mixed $value
     */
    public static function get_field_value(int $item_id, string $cf_field, string $cf_table_prefix, string $cf_prefix) {
        global $DB;

        $customfield_data = customfield_get_data((object)['id' => $item_id], $cf_table_prefix, $cf_prefix, false, [], $cf_field);
        return $customfield_data[$cf_field] ?? '';
    }

    /**
     * Get the customfield definitions for the specific table prefix
     *
     * @param string $cf_table_prefix
     * @return array
     */
    private static function get_field_definitions(string $cf_table_prefix): array {
        if (PHPUNIT_TEST || !isset(static::$customfield_cache[$cf_table_prefix])) {
            $customfields = customfield_get_fields_definition($cf_table_prefix);
            static::$customfield_cache[$cf_table_prefix] = $customfields;
        }

        return static::$customfield_cache[$cf_table_prefix];
    }

    /**
     * @param stdClass $detail
     * @param string $placeholder_label_prefix
     * @return string
     */
    private static function format_label(stdClass $detail, string $placeholder_label_prefix = '' ): string {
        $prefix = !empty($placeholder_label_prefix) ? $placeholder_label_prefix . ' ' : '';
        return $prefix . $detail->fullname ?? $detail->shortname;
    }

    /**
     * @param string $placeholder_key_prefix
     * @param stdClass $detail
     * @return string
     */
    private static function format_key(stdClass $detail, string $placeholder_key_prefix = ''): string {
        $prefix = !empty($placeholder_key_prefix) ? $placeholder_key_prefix . '_' : '';
        return $key = 'cf_' . $prefix . $detail->shortname;
    }

}