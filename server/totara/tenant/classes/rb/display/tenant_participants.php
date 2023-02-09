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

/**
 * Link to tenant category with management UI.
 */
final class tenant_participants extends base {
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
        $extra = self::get_extrafields_row($row, $column);

        $tenant = \core\record\tenant::fetch($extra->id, IGNORE_MISSING);
        if (!$tenant) {
            return '0';
        }

        $count = $value;
        if ($format !== 'html') {
            return $count;
        }

        $tenantcontext = \context_tenant::instance($tenant->id, IGNORE_MISSING);
        $categorycontext = \context_coursecat::instance($tenant->categoryid, IGNORE_MISSING);

        if (!$categorycontext) {
            return $count;
        }

        $canviewparticipants = false;
        if (has_capability('totara/tenant:viewparticipants', $categorycontext)) {
            $canviewparticipants = true;
        } else if (has_capability('totara/tenant:view', $tenantcontext) and has_capability('moodle/user:viewalldetails', $tenantcontext)) {
            $canviewparticipants = true;
        }

        if (!$canviewparticipants) {
            return $count;
        }

        $url = new \moodle_url('/totara/tenant/participants.php', ['id' => $tenant->id]);
        return \html_writer::link($url, $count);
    }

    /**
     * Is the result of this display method usable for graph series?
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return true;
    }
}
