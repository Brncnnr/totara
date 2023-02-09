<?php
/**
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
 * @package totara_program
 */

namespace totara_program\formatter;

use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter;

class program_formatter extends formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'fullname' => string_field_formatter::class,
            'shortname' => string_field_formatter::class,
            'idnumber' => null,
            'duedate' => date_field_formatter::class,
            'duedate_state' => null,
            'summary' => function ($value, text_field_formatter $formatter) {
                $component = 'totara_program';
                $filearea = 'summary';
                $itemid = $this->object->id;
                $context = \context_program::instance($itemid);

                return $formatter
                    ->set_pluginfile_url_options($context, $component, $filearea, 0)
                    ->format($value);
            },
            'summaryformat' => null,
            'endnote' => function ($value, text_field_formatter $formatter) {
                $component = 'totara_program';
                $filearea = 'endnote';
                $itemid = $this->object->id;

                return $formatter
                    ->set_pluginfile_url_options($this->context, $component, $filearea, $itemid)
                    ->format($value);
            },
            'availablefrom' => date_field_formatter::class,
            'availableuntil' => date_field_formatter::class,
            'category' => null, // Default - core\category_formatter::class
            'coursesets' => null, // Default - program\courseset_formatter::class
            'completion' => null, // Default - program\completion_formatter::class
        ];
    }
}
