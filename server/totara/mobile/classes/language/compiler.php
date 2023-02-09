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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\language;

/**
 * Class compiler serialises all language strings in component into JSON format
 *
 * String keys containing ":" will be split into multi-level structure.
 * E.g. $string['level1:level2'] = 'Value'; will be converted into:
 * {"level1: {"level2": "Value"}}
 *
 *
 * @package totara_mobile
 */
class compiler {
    /**
     * @var source Strings source
     */
    private $source;

    public function __construct(source $source) {
        $this->source = $source;
    }

    public static function instance(source $source): compiler {
        return new self($source);
    }

    /**
     * Add string to results array
     * @param string $key
     * @param string $value
     * @param array $result
     */
    private static function add_string(string $key, string $value, array &$result) {
        $levels = explode(':', $key);
        $elem = &$result;
        foreach ($levels as $level) {
            if (!isset($elem[$level])) {
                $elem[$level] = null;
            }
            $elem = &$elem[$level];
        }
        $elem = $value;
    }

    /**
     * Get json formatted results
     * @return string
     */
    public function get_json(): string {
        return json_encode($this->get_array()) ?? '';
    }

    /**
     * Get results as array
     * @return array
     */
    public function get_array(): array {
        $strings = $this->source->get_strings();
        $results = [];
        foreach ($strings as $key => $value) {
            self::add_string($key, $value, $results);
        }
        return $results;
    }
}