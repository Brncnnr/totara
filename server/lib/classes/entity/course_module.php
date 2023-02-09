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
use core\orm\entity\relations\belongs_to;

/**
 * @property int           $id
 * @property int           $course
 * @property int           $module
 * @property int|null      $instance
 * @property int|null      $section
 * @property string        $idnumber
 * @property int           $added
 * @property int           $score
 * @property int           $indent
 * @property int           $visible
 * @property int           $visibleoncoursepage
 * @property int           $visibleold
 * @property int           $groupmode
 * @property int           $groupingid
 * @property int           $completion
 * @property int           $completiongradeitemnumber
 * @property int           $completionview
 * @property int           $completionexpected
 * @property int           $showdescription
 * @property string|null   $availability
 * @property int           $deletioninprogress
 *
 * @property-read course $course_entity
 */
final class course_module extends entity {
    /**
     * @var string
     */
    public const TABLE = 'course_modules';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'added';

    /**
     * Format the value being input into either zero or one.
     *
     * @param int $value
     * @return void
     */
    protected function set_showdescription_attribute(int $value): void {
        if (!in_array($value, [1, 0])) {
            debugging("Value for property 'showdescription' is invalid", DEBUG_DEVELOPER);
            $value = 0;
        }

        $this->set_attribute_raw('showdescription', $value);
    }

    /**
     * @param int $value
     * @return void
     */
    protected function set_deletioninprogress_attribute(int $value): void {
        if (!in_array($value, [1, 0])) {
            debugging("Value for property 'deletioninprogress' is invalid", DEBUG_DEVELOPER);
            $value = 0;
        }

        $this->set_attribute_raw('deletioninprogress',$value);
    }

    /**
     * @return belongs_to
     */
    public function course_entity(): belongs_to {
        return $this->belongs_to(course::class, 'course');
    }

}