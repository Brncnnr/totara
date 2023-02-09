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
namespace core_user\profile;

use core\orm\query\builder;
use core_user\profile\field\field_helper;

final class display_setting {
    /**
     * This is existing just so that we do not have to hardcode the magic number.
     * It stands for the total of fields to be display on the card.
     *
     * @var int
     */
    public const MAGIC_NUMBER_OF_DISPLAY_FIELDS = 4;

    /**
     * Default array of display fields for the card.
     *
     * @var array
     */
    private const DEFAULT_FIELDS = ['fullname', 'department'];

    /**
     * @var string
     */
    public const SETTING_FIELD_KEY = 'profile_card_display_fields';

    /**
     * @var string
     */
    public const SETTING_PICTURE_KEY = 'profile_card_display_user_picture';

    /**
     * Support data type from 'user info field;
     *
     * @var array
     */
    public const SUPPORT_DATA_TYPES = ['text', 'menu'];

    /**
     * display_setting constructor.
     * Preventing this class from construction.
     */
    private function __construct() {
    }

    /**
     * Returning a hash map which the keys are set to these values:
     * And the values are the actual field name related to the user.
     *
     * @return array
     */
    public static function get_display_fields(): array {
        $display_fields_text = get_config('core_user', static::SETTING_FIELD_KEY);
        // This is the default, if the display fields are not configured by the
        // site admin yet.
        $display_fields = static::DEFAULT_FIELDS;

        if (!empty($display_fields_text)) {
            $display_fields = explode(',', $display_fields_text);
        }

        $hash_map = [];

        for ($i = 0; $i < static::MAGIC_NUMBER_OF_DISPLAY_FIELDS; $i++) {
            $key = field_helper::format_position_key($i);
            $value = null;

            if (isset($display_fields[$i])) {
                $value = trim($display_fields[$i]);

                if ('' === $value) {
                    $value = null;
                }
            }

            $hash_map[$key] = $value;
        }

        return $hash_map;
    }

    /**
     * Return a list of the fields that need to be used to show the profile image.
     * Note that this function will not taking account of whether the profile picture is enabled or not.
     *
     * @return array
     */
    public static function get_default_display_picture_fields(): array {
        return [
            'profileimageurl',
            'profileimageurlsmall',
            'profileimagealt',
        ];
    }

    /**
     * Return a list of the fields that need to be used to show the profile image.
     * Note that this function will be taking account of whether the profile picture is enabled or not.
     * If profile picture is enabled then list of fields in
     * {@see display_setting::get_default_display_picture_fields()} will be returned.
     *
     * @return array
     */
    public static function get_display_picture_fields(): array {
        if (!static::display_user_picture()) {
            return [];
        }

        return static::get_default_display_picture_fields();
    }

    /**
     * @return bool
     */
    public static function display_user_picture(): bool {
        $value = get_config('core_user', static::SETTING_PICTURE_KEY);
        if (null === $value || false === $value) {
            // Value for config item 'profile_card_display_user_picture' does not exist yet, therefore,
            // fallback to the default value which is 'true'
            return true;
        }

        return (bool) $value;
    }

    /**
     * Returning a hashmap where it is merging between {@see display_setting::get_display_fields()}
     * and the setting from {@see display_setting::display_user_picture()}.
     *
     * For displaying user picture, the key associate with the value will be 'user_picture'
     *
     * @return array
     */
    public static function get_setting_data(): array {
        $map = self::get_display_fields();
        $map['user_picture'] = self::display_user_picture();

        return $map;
    }

    /**
     * Saving the list of displaying fields to config table.
     * The order of element within fields will determine the position of each element.
     *
     * @param array $fields
     * @return void
     */
    public static function save_display_fields(array $fields): void {
        global $CFG;
        $values = array_values($fields);

        // Make sure that it does not go beyond our magic number.
        if (static::MAGIC_NUMBER_OF_DISPLAY_FIELDS < count($values)) {
            throw new \coding_exception("The number of fields exceeds the limit of acceptable fields");
        }

        // No empty values check.
        $not_emptied_values = array_filter(
            $values,
            function (string $value): bool {
                return !empty($value);
            }
        );

        if (empty($not_emptied_values)) {
            throw new \coding_exception("There must be at least a field to be not empty");
        }

        // Duplication check, but we have to check if the values are empty.
        $non_empty_values = array_filter(
            $values,
            function (string $value): bool {
                return !empty($value);
            }
        );
        $unique_values = array_unique($non_empty_values);
        if (count($unique_values) !== count($non_empty_values)) {
            // There are duplication, but we do not know which.
            // This is the last resource of checking on duplication, the duplication should be done in the
            // form prior to go to this function.
            throw new \coding_exception("Display fields cannot be duplicated");
        }

        // Normalise the data on save.
        $normalise = [];
        for ($i = 0; $i < static::MAGIC_NUMBER_OF_DISPLAY_FIELDS; $i++) {
            if (!isset($values[$i])) {
                // Empty string for now.
                $normalise[$i] = '';
                continue;
            }

            $normalise[$i] = $values[$i];
        }

        // We need to make sure that none of the fields that are going to be saved are appearing in the list
        // of $CFG->hiddenuserfields.
        if (!empty($CFG->hiddenuserfields)) {
            $hidden_user_fields = explode(',', $CFG->hiddenuserfields);
            $hidden_user_fields = array_map('trim', $hidden_user_fields);

            foreach ($normalise as $value) {
                if (in_array($value, $hidden_user_fields)) {
                    throw new \coding_exception(
                        "Cannot save field '{$value}' as it is appearing in the list of hidden user fields"
                    );
                }
            }
        }

        $values_string = implode(',', $normalise);
        set_config('profile_card_display_fields', $values_string, 'core_user');
    }

    /**
     * @param bool $value
     * @return void
     */
    public static function save_display_user_profile(bool $value): void {
        $int_value = $value ? 1 : 0;
        set_config(static::SETTING_PICTURE_KEY, $int_value, 'core_user');
    }

    /**
     * @param int $id
     * @return void
     */
    public static function remove_display_field_by_id(int $id): void {
        $db = builder::get_db();

        $record = $db->get_record('user_info_field',  ['id' => $id], 'datatype,shortname', MUST_EXIST);
        if (!in_array($record->datatype, self::SUPPORT_DATA_TYPES)) {
            return;
        }

        $format_name = field_helper::format_custom_field_short_name($record->shortname);
        if (!in_array($format_name, array_values(self::get_display_fields()))) {
            return;
        }

        $display_fields_text = get_config('core_user', static::SETTING_FIELD_KEY);

        if (empty($display_fields_text)) {
            return;
        }

        $display_fields = array_diff(explode(',', $display_fields_text), [$format_name]);
        $display_fields = implode(',', $display_fields);
        set_config('profile_card_display_fields', $display_fields, 'core_user');
    }
}