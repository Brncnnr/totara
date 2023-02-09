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
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Display tenant name with link to Tenant participants page.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */
class tenant_name extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if ($value === null) {
            return '';
        }
        $value = format_string($value, true, array('context' => \context_system::instance()));
        if ($format !== 'html') {
            return \core_text::entities_to_utf8($value);
        }

        $extra = self::get_extrafields_row($row, $column);
        $tenantcontext = \context_tenant::instance($extra->tenantid, IGNORE_MISSING);
        $categorycontext = \context_coursecat::instance($extra->categoryid, IGNORE_MISSING);
        if ($tenantcontext and $categorycontext) {
            if (has_capability('moodle/user:viewalldetails', $tenantcontext) or has_capability('moodle/user:viewhiddendetails', $categorycontext)) {
                $url = new \moodle_url('/totara/tenant/participants.php', ['id' => $extra->tenantid]);
                $value = \html_writer::link($url, $value);
            }
        }

        return $value;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
