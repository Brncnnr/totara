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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package core_form
 */

namespace core_form\hook;

/**
 * Class editor_formats_available
 *
 * A hook to allow plugins to manipulate the editor formats available.
 *
 */
class editor_formats_available extends \totara_core\hook\base {
    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $values;

    /**
     * @var array
     */
    private $formats;

    /**
     * Constructor for editor_formats_available
     *
     * @param array $options Editor options
     * @param array $values Editor values
     * @param array $formats Available formats
     */
    public function __construct(array $options, array $values, array $formats) {
        $this->options = $options;
        $this->values = $values;
        $this->formats = $formats;
    }

    /**
     * Get the array of editor options
     *
     * @return array
     */
    public function get_options(): array {
        return $this->options;
    }

    /**
     * Get the array of editor values
     *
     * @return array
     */
    public function get_values(): array {
        return $this->values;
    }

    /**
     * Get the array of available formats
     *
     * @return array
     */
    public function get_formats(): array {
        return $this->formats;
    }

    /**
     * Set an available format; can be used to overwrite the name of an existing available format.
     *
     * @param int $format
     * @param string $format_name
     */
    public function set_format(int $format, string $format_name): void {
        $this->formats[$format] = $format_name;
    }

    /**
     * Remove an available format
     *
     * @param int $format
     */
    public function remove_format(int $format): void {
        if (isset($this->formats[$format])) {
            unset($this->formats[$format]);
        }
    }
}