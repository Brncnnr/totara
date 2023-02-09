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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Display class intended for report builder export options
 *
 * @package totara_reportbuilder
 */
class report_export_options extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if (empty($value)) {
            return '';
        }

        $exportoptions = explode('|', $value);

        // Normalise option names.
        foreach ($exportoptions as $key => $option) {
            $exportoptions[$key] =  \totara_core\tabexport_writer::normalise_format($option);
        }

        $alloptions = \totara_core\tabexport_writer::get_export_options();
        $output = array();
        foreach ($alloptions as $type => $name) {
            if (in_array($type, $exportoptions)) {
                $output[$type] = $name;
            }
        }

        return implode(', ', $output);
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
