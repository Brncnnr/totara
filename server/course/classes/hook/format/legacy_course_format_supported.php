<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_course
 */
namespace core_course\hook\format;

use totara_core\hook\base;

/**
 * A hook to remove the course's format that are not meant for the legacy course within the list
 * of available course formats.
 */
final class legacy_course_format_supported extends base {
    /**
     * Array<String, String>
     * @var string[]
     */
    private $formats;

    /**
     * legacy_course_format_supported constructor.
     *
     * @param array $formats
     */
    public function __construct(array $formats) {
        $this->formats = [];

        foreach ($formats as $format) {
            if (!is_string($format)) {
                debugging("Expecting an item of array \$formats to be a string", DEBUG_DEVELOPER);
                continue;
            }

            $this->formats[$format] = $format;
        }
    }

    /**
     * @param string $format
     * @return void
     */
    public function remove_format(string $format): void {
        if (!isset($this->formats[$format])) {
            debugging("The format '{$format}' is not existing in the list of format", DEBUG_DEVELOPER);
            return;
        }

        unset($this->formats[$format]);
    }

    /**
     * Returning the original data value of the array when it first injected via construction.
     *
     * @return string[]
     */
    public function get_formats(): array {
        return array_keys($this->formats);
    }
}