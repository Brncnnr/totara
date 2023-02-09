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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */

namespace core\entity;

use core\orm\entity\entity;

/**
 * A class that represents for a row within table {course_sections}.
 *
 * @property int           $id
 * @property int           $course
 * @property int           $section
 * @property string|null   $name
 * @property string        $summary
 * @property int           $summaryformat
 * @property int           $sequence
 * @property bool          $visible
 * @property string|null   $availability
 * @property int           $timemodified
 */
final class course_section extends entity {
    /**
     * @var string
     */
    public const TABLE = 'course_sections';

    /**
     * @param string|array $value
     * @return void
     */
    protected function set_sequence_attribute($value): void {
        if (is_array($value)) {
            $value = implode(',', $value);
        } else if (null === $value || '' === $value) {
            $value = null;
        } else if (!is_string($value)) {
            throw new \coding_exception("Invalid value parameter for sequence");
        }

        $this->set_attribute_raw('sequence', $value);
    }

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return course_section_repository::class;
    }
}