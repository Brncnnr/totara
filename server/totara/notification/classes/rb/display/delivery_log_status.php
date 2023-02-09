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
use totara_mvc\view;
use totara_reportbuilder\rb\display\base;

class delivery_log_status extends base {

    /**
     * @param int $has_error
     * @param string $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     * @return string
     */
    public static function display($has_error, $format, stdClass $row, rb_column $column, reportbuilder $report): string {

        $error_text = get_string('error_text', 'rb_source_notification_delivery_log');

        if ($has_error == 1) {
            return view::core_renderer()->help_icon('error_text','rb_source_notification_delivery_log', $error_text);
        } else {
            return get_string('success_text', 'rb_source_notification_delivery_log');
        }
    }

}