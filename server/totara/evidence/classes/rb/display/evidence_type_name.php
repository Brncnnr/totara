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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\rb\display;

use rb_column;
use reportbuilder;
use stdClass;
use totara_evidence\models\helpers\multilang_helper;
use totara_evidence\output\type_name_link;
use totara_mvc\view;
use totara_reportbuilder\rb\display\base;
use totara_reportbuilder\rb\display\format_string;

class evidence_type_name extends base {

    /**
     * Display the type name of evidence with a link to edit it
     *
     * @param string $type_name
     * @param string $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     * @return string
     */
    public static function display($type_name, $format, stdClass $row, rb_column $column, reportbuilder $report): string {
        $type_name = format_string::display(
            multilang_helper::parse_type_name_string($type_name),
            $format, $row, $column, $report
        );

        if ($format === 'html') {
            return view::core_renderer()->render(type_name_link::create_from_report(
                self::get_extrafields_row($row, $column)->typeid,
                $type_name,
                $column->extracontext['type_can_manage']
            ));
        }

        return $type_name;
    }

}
