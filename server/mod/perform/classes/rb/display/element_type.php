<?php
/*
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\display;

use coding_exception;
use mod_perform\models\activity\element_plugin;
use performelement_linked_review\content_type_factory;
use totara_reportbuilder\rb\display\base;

class element_type extends base {

    /**
     * Handles the display
     *
     * @param string $plugin_name
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($plugin_name, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if (empty($plugin_name)) {
            return '';
        }

        $type = element_plugin::load_by_plugin($plugin_name)->get_name();
        if ($plugin_name !== 'linked_review') {
            return $type;
        }

        // Linked review elements have a slightly different way of naming types.
        $extra_fields = self::get_extrafields_row($row, $column);
        $data = json_decode($extra_fields->data, true);
        $content_type = $data['content_type'] ?? null;

        if (is_null($content_type)) {
            throw new coding_exception('Cannot find content type for perform linked review type question');
        }

        $content_class = content_type_factory::get_class_name_from_identifier($content_type);
        return $type . ': ' . $content_class::get_display_name();
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
