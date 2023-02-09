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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_user
 */
namespace core_user\profile\field;

use core_user\profile\display_setting;

/**
 * This class is used for providing the list of fields that are available.
 */
final class summary_field_provider {
    /**
     * Cached array of custom fields, a hash map to store field short name as the key
     * and field full name as the value.
     *
     * @var metadata[]
     */
    private $custom_fields;

    /**
     * field_provider constructor.
     */
    public function __construct() {
        $this->custom_fields = [];
    }

    /**
     * This function is a metadata function, where it will return all the available fields within the system,
     * developers may add more fields to this.
     *
     * NOTE: this function will not respect the configuration such as 'hiddenuserfields'.
     * Please use {@see summary_field_provider::get_all_provide_fields()} to get all the fields that ara available.
     *
     * @return metadata[]
     */
    public static function get_system_fields(): array {
        return [
            new metadata(
                'fullname',
                get_string('fullname'),
                'profileurl'
            ),

            new metadata(
                'username',
                get_string('username')
            ),

            new metadata(
                'city',
                get_string('city')
            ),

            new metadata(
                'country',
                get_string('country')
            ),

            new metadata(
                'email',
                get_string('email'),
                'mailtourl'
            ),

            new metadata(
                'timezone',
                get_string('timezone')
            ),

            new metadata(
                'url',
                get_string('url')
            ),

            new metadata(
                'skype',
                get_string('skypeid')
            ),

            new metadata(
                'idnumber',
                get_string('idnumber')
            ),

            new metadata(
                'institution',
                get_string('institution')
            ),

            new metadata(
                'department',
                get_string('department')
            ),

            new metadata(
                'phone1',
                get_string('phone1')
            ),

            new metadata(
                'phone2',
                get_string('phone2')
            ),

            new metadata(
                'address',
                get_string('address')
            )
        ];
    }

    /**
     * Returning all the fields that are related to user, and also returning the custom fields existing
     * in the system as well.
     *
     * @return metadata[]
     */
    public function get_all_provide_fields(): array {
        global $CFG;

        $system_fields = self::get_system_fields();

        if (!empty($CFG->hiddenuserfields)) {
            $hidden_user_fields = explode(',', $CFG->hiddenuserfields);
            $hidden_user_fields = array_map('trim', $hidden_user_fields);

            $system_fields = array_filter(
                $system_fields,
                function (metadata $field) use ($hidden_user_fields): bool {
                    $field_name = $field->get_key();
                    return !in_array($field_name, $hidden_user_fields);
                }
            );
        }

        $custom_fields = $this->get_available_custom_fields();
        return array_merge($system_fields, $custom_fields);
    }

    /**
     * Returning a hash map where key is the field name and the value is the language string
     * that represent for the field name.
     *
     * @param bool $include_custom_fields
     * @return array
     */
    public function get_provide_fields_with_label(bool $include_custom_fields = true): array {
        $hash_map = [];
        $all_fields = $this->get_all_provide_fields();

        foreach ($all_fields as $field) {
            if ($field->is_custom_field() && !$include_custom_fields) {
                continue;
            }

            $hash_map[$field->get_key()] = $field->get_label();
        }

        return $hash_map;
    }

    /**
     * Returning a hashmap of custom fields. If the custom fields are already loaded, it will return
     * the loaded fields. Otherwise, will start fetching it.
     *
     * If $reload is provided, the custom fields will be reloaded.
     *
     * @param bool $reload
     * @return metadata[]
     */
    public function get_available_custom_fields(bool $reload = false): array {
        global $CFG;

        if (!empty($this->custom_fields) && !$reload) {
            return $this->custom_fields;
        }

        require_once("{$CFG->dirroot}/user/profile/lib.php");
        $this->custom_fields = [];

        $custom_fields = profile_get_custom_fields();
        foreach ($custom_fields as $custom_field) {
            // Note: for now we are only looking for text input and menu field.
            if (!in_array($custom_field->datatype, display_setting::SUPPORT_DATA_TYPES)) {
                continue;
            }

            $field_metadata = new metadata($custom_field->shortname, $custom_field->name);
            $field_metadata->set_custom_field(true);

            $this->custom_fields[] = $field_metadata;
        }

        return $this->custom_fields;
    }

    /**
     * @param string $field_name
     * @return metadata|null
     */
    public function get_field_metadata(string $field_name): ?metadata {
        $fields = $this->get_all_provide_fields();

        foreach ($fields as $field) {
            $inner_name = $field->get_key();
            if ($field_name === $inner_name) {
                return $field;
            }
        }

        return null;
    }
}