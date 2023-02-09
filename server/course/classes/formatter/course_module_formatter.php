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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core_course
 */

namespace core_course\formatter;

use core\webapi\formatter\formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;

class course_module_formatter extends formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'idnumber' => null,
            'instanceid' => null,
            'modtype' => string_field_formatter::class,
            'name' => string_field_formatter::class,
            'viewurl' => string_field_formatter::class,
            'completion' => null,
            'completionstatus' => null,
            'available' => null,
            'availablereason' => string_field_formatter::class,
            'showdescription' => null,
            'description' => function ($value, text_field_formatter $formatter) {
                $component = "mod_{$this->object->modname}";
                $filearea = 'intro';

                return $formatter
                    ->set_pluginfile_url_options($this->context, $component, $filearea)
                    ->format($value);
            },
            'gradefinal' => null,
            'grademax' => null,
            'gradepercentage' => null,
        ];
    }
}
