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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
namespace totara_topic\rb\display;

use totara_reportbuilder\rb\display\base;

final class component extends base {
    /**
     * @param string         $component
     * @param string         $format
     * @param \stdClass      $row
     * @param \rb_column     $column
     * @param \reportbuilder $report
     *
     * @return string
     */
    public static function display($component, $format, \stdClass $row, \rb_column $column,
                                   \reportbuilder $report): string {
        return get_string('pluginname', $component);
    }
}