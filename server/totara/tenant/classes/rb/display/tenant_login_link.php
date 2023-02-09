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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_tenant
 */

namespace totara_tenant\rb\display;

use \totara_reportbuilder\rb\display\base;
use \core\output\flex_icon;

/**
 * A link to the tenant specific login page
 */
final class tenant_login_link extends base {

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
        global $CFG;

        $loginurl = new \moodle_url($CFG->wwwroot . '/login/index.php?tenanttheme=' . $value);

        if ($format !== 'html') {
            return $loginurl->out();
        }

        return \html_writer::link($loginurl, $loginurl->out());
    }
}
