<?php
/**
 *
 * This file is part of Totara LMS
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package core_course
 */

namespace core_course\formatter;

use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter;

class course_formatter extends formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'idnumber' => null,
            'fullname' => string_field_formatter::class,
            'shortname' => string_field_formatter::class,
            'summary' => function ($value, text_field_formatter $formatter) {
                $component = 'course';
                $filearea = 'summary';
                $itemid = $this->object->id;
                $context = \context_course::instance($itemid);

                return $formatter
                    ->set_text_format($this->object->summaryformat)
                    ->set_pluginfile_url_options($context, $component, $filearea)
                    ->format($value);
            },
            'summaryformat' => null,
            'timecreated' => date_field_formatter::class,
            'timemodified' => date_field_formatter::class,
            'category' => null, // Default: core_category_formatter::class
            'categoryid' => null,
            'startdate' => date_field_formatter::class,
            'enddate' => date_field_formatter::class,
            'theme' => null,
            'lang' => null,
            'format' => null,
            'coursetype' => null,
            'icon' => null,
            'image' => null,
            'mobile_image' => null,
            'sections' => null,
            'showgrades' => null, // Default - boolean.
            'completionenabled' => null, // Default - boolean.
            'completion' => null,
            'criteriaaggregation' => string_field_formatter::class,
            'criteria' => null, // Default - course_criteria_formatter
        ];
    }
}
