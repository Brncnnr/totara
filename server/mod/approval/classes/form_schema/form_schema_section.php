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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\form_schema;

use stdClass;

/**
 * Approval workflows form_schema parser section base class
 *
 * @package mod_approval\form_schema
 */
class form_schema_section {
    /**
     * @var string
     */
    private $key;

    /**
     * @var string|null
     */
    public $line;

    /**
     * @var string
     */
    public $label;

    /**
     * form_schema_section constructor.
     *
     * @param string $key
     * @param string $label
     * @param string|null $line
     */
    public function __construct(string $key, string $label, ?string $line = null) {
        $this->key = $key;
        $this->line = $line;
        $this->label = $label;
    }

    /**
     * Get section's key
     *
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * Convert this instance to a stdClass object with all properties intact.
     *
     * @return stdClass
     */
    public function to_stdClass(): stdClass {
        $section = new stdClass();
        foreach (get_object_vars($this) as $key => $value) {
            $section->{$key} = $value;
        }
        return $section;
    }
}