<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\rb\display;

use rb_column;
use reportbuilder;
use stdClass;
use totara_reportbuilder\rb\display\base;

class notification_event_log_event_name extends base {

    /**
     * @param $string_key
     * @param $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     * @return string
     */
    public static function display($string_key, $format, stdClass $row, rb_column $column, reportbuilder $report) {
        // Retrieve the extra row data.
        $extra = self::get_extrafields_row($row, $column);

        $resolver_class_name = $extra->resolver_class_name;
        $string_params = json_decode($extra->string_params, true);

        return $resolver_class_name::format_event_log_display_string($string_key, $string_params);
    }
}