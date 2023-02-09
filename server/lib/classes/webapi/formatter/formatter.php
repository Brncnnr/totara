<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\webapi\formatter;

use Closure;
use coding_exception;
use context;
use core\webapi\formatter\field\base;
use stdClass;

/**
 * Extend this class to define a formatter for your object (can also be passed as array).
 * The formatter needs to define a map (returned by get_map()) which contains a map between field names
 * and format functions for those field. Calling formatter->format('fieldname', 'format') will get the value
 * for the field from the given object, will run it through the specific format function and return the formatted value.
 *
 * Make sure you pass the correct context you want the formatting to happen in.
 * As we use context aware functions like format_string() or format_text() it is important to pass the right one.
 *
 * Example:
 *
 * $formatter = new competency_formatter($competency, $context);
 * $value = $formatter->format('fullname', \core\format::FORMAT_HTML);
 *
 * If you want ot make your formatter work with an custom object which is not a stdClass then you
 * need to override the functions get_field() and has_field() which should then map to the respective functionality of your object.
 */
abstract class formatter {

    /**
     * @var context
     */
    protected $context;

    /**
     * @var stdClass
     */
    protected $object;

    /**
     * @var array
     */
    protected $format_map = [];

    /**
     * @param array|object $object
     * @param context $context
     */
    public function __construct($object, context $context) {
        if (is_array($object)) {
            $object = (object)$object;
        }
        $this->object = $object;
        $this->context = $context;
    }

    /**
     * Returns an array where the keys are the name of the fields
     * and the values can be:
     * - a field formatter class name, must be a child of \core\webapi\formatter\field\base
     * - a function name of the current formatter, the function takes value and format as arguments
     * - a simple Closure with the value as first and the format as second parameter
     * - a Closure with the value as first and a typehinted field formatter as second argument
     *
     * @return array
     */
    abstract protected function get_map(): array;

    /**
     * Format the given field using the given format
     *
     * @param string $field
     * @param string|null $format the format for the field
     * @return array|mixed|null
     */
    public function format(string $field, ?string $format = null) {
        if ($field === '') {
            throw new coding_exception('Field name cannot be empty.');
        }

        // Field does not exist on the object
        if (!$this->has_field($field)) {
            throw new coding_exception('Unknown field '.$field);
        }

        return $this->apply_format($field, $this->get_field($field), $format);
    }

    /**
     * Get the value of a field,
     * can be overridden if formatter does not use stdClasses or entities
     *
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        return $this->object->$field;
    }

    /**
     * Does the object has the given field,
     * can be overridden if formatter does not use stdClasses or entities
     *
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        return property_exists($this->object, $field);
    }

    /**
     * Detect and apply the format function of the given field
     *
     * @param string $field
     * @param mixed $value
     * @param string|null $format
     * @return mixed
     */
    private function apply_format(string $field, $value, ?string $format = null) {
        $map = $this->get_map();

        // The field must be defined in the map otherwise it will fail
        if (!array_key_exists($field, $map)) {
            throw new coding_exception('Field was not found in the format map.');
        }

        $format_function = $map[$field];

        // If null is given then we don't do anything with the value
        if (is_null($format_function)) {
            return $value;
        }

        // A formatter class name is given
        if (is_subclass_of($format_function, base::class)) {
            if (is_string($format_function)) {
                $format_function = new $format_function($format, $this->context);
            }
            return $format_function->format($value);
        }

        // A closure is given
        if (is_object($format_function) && ($format_function instanceof Closure)) {
            $reflection_function = new \ReflectionFunction($format_function);
            $params = $reflection_function->getParameters();

            // If the second parameter is a formatter class instantiate it
            if (count($params) > 1) {
                $type = $params[1]->getType();
                if ($type) {
                    $name = $type->getName();
                    if (is_subclass_of($name, base::class)) {
                        return $format_function($value, new $name($format, $this->context));
                    }
                }
                return $format_function($value, $format);
            }

            // A simple Closure was given, just pass the value on
            return $format_function($value);
        }

        // A function name in the existing class was given
        if (method_exists($this, $format_function)) {
            return $this->$format_function($value, $format);
        }

        throw new coding_exception('Format method not found!');
    }

}
