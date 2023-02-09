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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\http;

/**
 * A class represents form data.
 */
final class formdata {
    /**
     * @var array
     */
    private $data = [];

    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = []) {
        foreach ($data as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Set a parameter.
     *
     * @param string $name
     * @param string|array $value
     * @return self
     */
    public function set(string $name, $value): self {
        if (is_array($value)) {
            foreach ($value as $i => $val) {
                $value[$i] = (string)$val;
            }
        } else {
            $value = (string)$value;
        }
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * Delete a parameter.
     *
     * @param string $name
     * @return self
     */
    public function delete(string $name): self {
        unset($this->data[$name]);
        return $this;
    }

    /**
     * Get concatenated parameters in the application/x-www-form-urlencoded format.
     *
     * @return string
     */
    public function as_string(): string {
        $array = [];
        foreach ($this->data as $name => $value) {
            $name = urlencode($name);
            if (is_array($value)) {
                foreach ($value as $val) {
                    $array[] = $name . '[]=' . urlencode($val);
                }
            } else {
                $array[] = $name . '=' . urlencode($value);
            }
        }
        return implode('&', $array);
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return $this->as_string();
    }
}
