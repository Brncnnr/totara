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

namespace core\webapi\formatter\field;

use coding_exception;
use context;

/**
 * Extend this class to create a field formatter for a specific purpose.
 * The minimum you need to override is the get_default_format() function.
 *
 * Alternatively you can implement a format_[formatname]() function for each of
 * you supported formats, i.e. format_html() or format_raw() which is automatically
 * called.
 *
 * If any other valid format is passed which does not it's own format function it
 * falls back to the get_default_format() function.
 *
 * You should also override the validate_format() function which makes sure
 * only valid formats can be used.
 */
abstract class base implements field_formatter_interface {

    /**
     * @var string|null
     */
    protected $format;

    /**
     * @var context
     */
    protected $context;

    /**
     * @param string $format
     * @param context $context
     */
    public function __construct(?string $format, context $context) {
        $this->format = clean_string($format);
        $this->context = $context;
    }

    /**
     * Run the given value through the formatter, if there's no function for the given format
     * in the form ->format_[format]() then get_default_format is called.
     * The format will be validated by validate_format(), make sure you override this
     * in the child classes
     *
     * @param mixed|null $value null values will be returned as null
     * @return mixed|null
     */
    public function format($value) {
        if (!$this->validate_format()) {
            throw new coding_exception('Invalid format given');
        }

        if (is_null($value)) {
            return null;
        }

        if (!is_null($this->format)) {
            $format_function = 'format_' . strtolower($this->format);
            if (method_exists($this, $format_function)) {
                // Allow for array of values to be formatted and returned.
                if (is_array($value)) {
                    $formatted = [];
                    foreach ($value as $record) {
                        // Note: This can be changed to is_scalar to allow other types of arrays, but should never
                        // accept objects or nested arrays. Limited to strings for now due to test coverage.
                        if (is_string($record)) {
                            $formatted[] = $this->$format_function($record);
                        } else {
                            throw new coding_exception('Invalid array values, only scalar values can be formatted');
                        }
                    }
                    return $formatted;
                } else {
                    return $this->$format_function($value);
                }
            }
        }

        // If format is null or no specific format function is provided
        return $this->get_default_format($value);
    }

    /**
     * Returns true if the format passed in the constructor is valid
     * Override this to make sure only valid formats for your field formatter can be used.
     *
     * @return bool
     */
    protected function validate_format(): bool {
        return true;
    }

    /**
     * The default format to apply.
     * Override this if you have a default format. This function is also called for any valid format
     * which does not have it's own format function.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function get_default_format($value) {
        return $value;
    }

}
