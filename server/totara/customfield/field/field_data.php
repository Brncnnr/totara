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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_customfield
 */

namespace totara_customfield\field;

/**
 * Class field_data
 *
 * This class defines a standard way of representing a custom field's data.
 *
 * @package totara_customfield\field
 */
class field_data {

    /** @var string */
    private $label;

    /** @var string */
    private $type;

    /** @var array */
    private $extra;

    /**
     * field_data constructor.
     *
     * @param string $label
     * @param string $type
     * @param array $extra
     */
    public function __construct(string $label, string $type, array $extra) {
        $this->label = $label;
        $this->type = $type;
        $this->extra = $extra;
    }

    /**
     * @param string $label
     */
    public function set_label(string $label): void {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function get_label(): string {
        return $this->label;
    }

    /**
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function set_type(string $type): void {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function get_extra(): array {
        return $this->extra;
    }

    /**
     * @param array $extra
     */
    public function add_extra(array $extra): void {
        $this->extra = array_merge($this->extra, $extra);
    }

    /**
     * @param array $extra
     */
    public function set_extra(array $extra): void {
        $this->extra = $extra;
    }

    /**
     * @return string
     */
    public function extra_to_json(): string {
        return json_encode($this->extra);
    }

}