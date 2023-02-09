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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\rb\display;

use html_writer;
use totara_reportbuilder\rb\display\base;
use totara_reportbuilder\rb\display\format_string;

/**
 * Display class intended for reported workspacelink
 */
final class engagedusers_workspacelink extends base {
    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report): string {
        if ($format !== 'html') {
            return $value ?? '';
        }

        $extra = self::get_extrafields_row($row, $column);
        if(isset($value) && isset($extra->ids)){
            $ids = explode(',', $extra->ids);
            $workspaces = explode(',', $value);
            
            $list = [];
            foreach ($workspaces as $i => $workspace) {
                $url = new \moodle_url("/container/type/workspace/workspace.php", ['id' => $ids[$i]]);
                $list[] = html_writer::link($url->out(true), $workspace);
            }
            return implode(' , ', $list);
        }
        return '';
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}