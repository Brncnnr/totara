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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\placeholder;

use coding_exception;

class key_helper {
    /**
     * @var string
     */
    public const LOOP_START = 'loop_start';

    /**
     * @var string
     */
    public const LOOP_END = 'loop_end';

    /**
     * helper constructor.
     */
    private function __construct() {
        // Prevent the construction on this class.
    }

    /**
     * Concatinate the group key with the placehoplder key. Which it will result into
     * "group_key:placeholder_key"
     *
     * For example:
     *  get_key('commenter', 'firstname') => commenter:firstname
     *  get_key('author', 'alternative_first_name') => 'commenter:alternative_first_name'
     *
     * Note that this function does not check the legitimate of neither the group key nor placeholder key.
     *
     * @param string $group_key
     * @param string $placeholder_key
     * @return string
     */
    public static function get_group_key(string $group_key, string $placeholder_key): string {
        return "{$group_key}:{$placeholder_key}";
    }

    /**
     * @param string $key
     * @return string
     */
    public static function remove_bracket(string $key): string {
        return str_replace(['[', ']'], "", $key);
    }

    /**
     * From a string "prefix:placeholder_key", this function will separate the string into
     * two part by the delimiter as the colon symbol.
     *
     * @param string $key
     * @return array    => [$prefix, $placeholder_key]
     */
    public static function normalize_grouped_key(string $key): array {
        $cleaned_key = self::remove_bracket($key);

        if (!self::is_valid_grouped_key($cleaned_key)) {
            throw new coding_exception("Invalid key value that does not match the pattern: '{$key}'");
        }

        [$prefix, $placeholder_key] = explode(':', $cleaned_key);
        return [$prefix, $placeholder_key];
    }

    /**
     * Checking the legitimate of a grouped string key.
     *
     * @param string $key
     * @param bool $with_bracket
     *
     * @return bool
     */
    public static function is_valid_grouped_key(string $key, bool $with_bracket = false): bool {
        $regex = self::get_grouped_key_regex($with_bracket, true);
        return preg_match($regex, $key);
    }

    /**
     * @param bool $with_bracket        To include the open and close square bracket into the regex.
     * @param bool $strict_to_string    To add the start with string and end with string simple in regex.
     * @return string
     */
    public static function get_grouped_key_regex(bool $with_bracket = false, bool $strict_to_string = false): string {
        $regex = "[a-zA-Z0-9\_]+:[a-zA-Z0-9\_]+";

        if ($with_bracket) {
            $regex = "\[{$regex}\]";
        }

        if ($strict_to_string) {
            $regex = "^{$regex}$";
        }

        return "/{$regex}/";
    }

    /**
     * @param string $group_key
     * @param bool   $start         If true then it is a start, ortherwise it is an end.
     * @param bool   $with_bracket
     * @return string
     */
    private static function get_collection_key(string $group_key, bool $start, bool $with_bracket = false): string {
        $placeholder_key = $start ? self::LOOP_START : self::LOOP_END;
        $key = self::get_group_key($group_key, $placeholder_key);

        return $with_bracket ? "[{$key}]" : $key;
    }

    /**
     * @param string $group_key
     * @param bool   $with_bracket
     * @return string
     */
    public static function get_start_collection_key(string $group_key, bool $with_bracket = false): string {
        return self::get_collection_key($group_key, true, $with_bracket);
    }

    /**
     * @param string $group_key
     * @param bool   $with_bracket
     *
     * @return string
     */
    public static function get_end_collection_key(string $group_key, bool $with_bracket = false): string {
        return self::get_collection_key($group_key, false, $with_bracket);
    }

    /**
     * @param string $group_key
     * @return string
     */
    public static function strip_invalid_characters_from_key(string $group_key): string {
        $regex = '/[^a-zA-Z0-9:_]/';
        return preg_replace($regex, '', $group_key);
    }
}