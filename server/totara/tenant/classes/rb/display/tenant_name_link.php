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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

namespace totara_tenant\rb\display;

use \totara_reportbuilder\rb\display\base;
use \core\output\flex_icon;

/**
 * Tenant name that links to category.
 */
final class tenant_name_link extends base {
    /**
     * Display data.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $name = format_string($value);

        if ($format !== 'html') {
            return \core_text::entities_to_utf8($name);
        }

        $extra = self::get_extrafields_row($row, $column);
        $tenant = \core\record\tenant::fetch($extra->id);

        $category = \coursecat::get($tenant->categoryid, IGNORE_MISSING, true);
        if (!$category or !$category->is_uservisible()) {
            return $name;
        }

        $url = new \moodle_url('/course/index.php', ['categoryid' => $category->id]);
        return \html_writer::link($url, $name);
    }
}
