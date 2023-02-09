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
 * Approval workflows form_schema parser field base class
 *
 * @package mod_approval\form_schema
 */
class form_schema_field {
    /**
     * @var string
     */
    private $index;

    /**
     * @var string|null
     */
    public $line;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $instruction;

    /**
     * @var string
     */
    public $help;

    /**
     * Help text in html.
     *
     * @var string
     */
    public $help_html;

    /**
     * @var string
     */
    public $type;

    /**
     * @var bool
     */
    public $required = true;

    /**
     * @var bool
     */
    public $disabled = false;

    /**
     * @var bool
     */
    public $hidden = false;

    /**
     * @var string
     */
    public $default;

    /**
     * @var array
     */

    public $meta;

    /**
     * @var array
     */
    public $validations;

    /**
     * @var stdClass
     */
    public $attrs;

    /**
     * @var stdClass
     */
    public $conditional;

    /**
     * @var array
     */
    public $rules;

    /**
     * form_schema_field constructor.
     *
     * @param string $index
     * @param stdClass $field
     */
    public function __construct(string $index, stdClass $field) {
        $this->index = $index;
        foreach (get_object_vars($field) as $key => $value) {
            switch ($key) {
                case 'key':
                    break;
                case 'line':
                case 'label':
                case 'instruction':
                case 'help':
                case 'help_html':
                case 'type':
                case 'default':
                case 'meta':
                case 'char_length':
                case 'validations':
                case 'attrs':
                case 'conditional':
                case 'rules':
                    $this->{$key} = $value;
                    break;
                case 'required':
                case 'disabled':
                case 'hidden':
                    $this->{$key} = (bool) $value;
                    break;
                default:
                    debugging("unknown schema field key $key", DEBUG_DEVELOPER);
                    break;
            }
        }
    }

    /**
     * Returns the field index, which is a section_index/field_key path
     *
     * @return string
     */
    public function get_index(): string {
        return $this->index;
    }

    /**
     * Get the section_index part of the field index.
     *
     * @return string
     */
    public function get_section_index(): string {
        list($section_index, $field_key) = explode('/', $this->index);
        return $section_index;
    }

    /**
     * Get the field_key of the field.
     *
     * @return string
     */
    public function get_field_key(): string {
        list($section_key, $field_key) = explode('/', $this->index);
        return $field_key;
    }

    /**
     * Convert this field to a stdClass object with all of its properties intact.
     *
     * @return stdClass
     */
    public function to_stdClass(): stdClass {
        $field = new stdClass();
        $field->key = $this->get_field_key();
        foreach (get_object_vars($this) as $key => $value) {
            switch ($key) {
                case 'index':
                    break;
                default:
                    $field->{$key} = $value;
            }
        }
        return $field;
    }
}
