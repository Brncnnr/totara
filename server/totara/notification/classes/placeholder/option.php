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

/**
 * Data transfer object for placeholder which to keep the key and label.
 * This class can be used to hold either simple key or grouped key.
 *
 * Example:
 *  + simple key: firstname, lastname
 *  + grouped key: author:firstname, manager:lastname
 */
class option {
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $label;

    /**
     * option constructor.
     * @param string $key
     * @param string $label
     */
    private function __construct(string $key, string $label) {
        $this->key = $key;
        $this->label = $label;
    }

    /**
     * Please note that the keys for the map options will have to only simple
     * alphabet and numeric string with optional underscore/colon symbol. Any other
     * string that are non alphabet/numeric string and/or contains different character
     * other than underscore or colon symbol will fail the processing of converting
     * placeholder into a proper text.
     *
     * @param string $key
     * @param string $label
     * @return option
     */
    public static function create(string $key, string $label): option {
        // Special key that get grouped scenario.
        $grouped_key_regex = key_helper::get_grouped_key_regex();
        if (preg_match($grouped_key_regex, $key) || !preg_match('/[^a-zA-Z0-9_]/', $key)) {
            return new static($key, $label);
        }

        throw new coding_exception("The key '{$key}' contains illegal character(s)");
    }

    /**
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * @return string
     */
    public function get_label(): string {
        return $this->label;
    }
}