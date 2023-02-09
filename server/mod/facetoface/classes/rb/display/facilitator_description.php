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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\rb\display;

defined('MOODLE_INTERNAL') || die();

use \totara_reportbuilder\rb\display\base;
use \mod_facetoface\customfield_area\facetofacefacilitator as facilitatorcustomfield;

class facilitator_description extends base {
    /**
     * Display facilitator description
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report): string {

        $isexport = ($format !== 'html');
        $extra = self::get_extrafields_row($row, $column);

        $context = facilitatorcustomfield::get_context();
        $component = facilitatorcustomfield::get_component();
        $filearea = facilitatorcustomfield::get_area_name();

        $description = file_rewrite_pluginfile_urls(
            $value,
            'pluginfile.php',
            $context->id,
            $component,
            $filearea,
            $extra->id
        );
        $descriptionhtml = format_text($description, FORMAT_HTML);

        if ($isexport) {
            $displaytext = html_to_text($descriptionhtml, 0, false);
            $displaytext = \core_text::entities_to_utf8($displaytext);
            return $displaytext;
        }

        return $descriptionhtml;
    }

    /**
     * Is this column graphable?
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report): bool {
        return false;
    }
}