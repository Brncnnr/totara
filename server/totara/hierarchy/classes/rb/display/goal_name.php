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
 * @package totara_hierarchy
 */

namespace totara_hierarchy\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for goal name.
 *
 * The goal name is only displayed if the viewer has the 'totara/hierarchy:viewallgoals' capability or if the goal is
 * their own and they have the 'totara/hierarchy:viewownpersonalgoal' capability.
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_hierarchy
 */
class goal_name extends base {

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
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG, $USER;

        if (empty($value)) {
            return '';
        }

        $extrafields = self::get_extrafields_row($row, $column);

        static $viewallgoalscap             = null;
        static $viewowncompanygoalcap       = null;
        static $viewownpersonalgoalcap      = null;
        static $viewstaffpersonalgoalcap    = null;
        static $viewstaffcompanygoalcap     = null;
        static $ismanager                   = [];

        if ($viewallgoalscap === null || PHPUNIT_TEST) {
            $viewallgoalscap = has_capability('totara/hierarchy:viewallgoals', \context_system::instance());
        }

        if ($viewallgoalscap) {
            return \totara_reportbuilder\rb\display\format_string::display($value, $format, $row, $column, $report);
        }

        if (empty($ismanager[$extrafields->userid]) || PHPUNIT_TEST) {
            $ismanager[$extrafields->userid] = \totara_job\job_assignment::is_managing($USER->id, $extrafields->userid, null, false);
        }

        include_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

        // Personal goal.
        if ($extrafields->scope == 'personal' || $extrafields->scope == \goal::SCOPE_PERSONAL) {
            // Own personal goals.
            if ($viewownpersonalgoalcap === null || PHPUNIT_TEST) {
                $viewownpersonalgoalcap = has_capability('totara/hierarchy:viewownpersonalgoal', \context_user::instance($extrafields->userid));
            }

            if ($viewownpersonalgoalcap && $USER->id == $extrafields->userid) {
                return \totara_reportbuilder\rb\display\format_string::display($value, $format, $row, $column, $report);
            }

            // Staff personal goals.
            if ($viewstaffpersonalgoalcap === null || PHPUNIT_TEST) {
                $viewstaffpersonalgoalcap = has_capability('totara/hierarchy:viewstaffpersonalgoal', \context_user::instance($extrafields->userid), $USER->id);
            }

            if ($ismanager[$extrafields->userid] && $viewstaffpersonalgoalcap) {
                return \totara_reportbuilder\rb\display\format_string::display($value, $format, $row, $column, $report);
            }
        }

        // Company goal.
        if ($extrafields->scope == 'company' || $extrafields->scope == \goal::SCOPE_COMPANY) {
            // Own company goals.
            if ($viewowncompanygoalcap === null || PHPUNIT_TEST) {
                $viewowncompanygoalcap = has_capability('totara/hierarchy:viewowncompanygoal', \context_user::instance($extrafields->userid));
            }

            if ($viewowncompanygoalcap && $USER->id == $extrafields->userid) {
                return \totara_reportbuilder\rb\display\format_string::display($value, $format, $row, $column, $report);
            }

            // Staff company goals.
            if ($viewstaffcompanygoalcap === null || PHPUNIT_TEST) {
                $viewstaffcompanygoalcap = has_capability('totara/hierarchy:viewstaffcompanygoal', \context_user::instance($extrafields->userid), $USER->id);
            }

            if ($ismanager[$extrafields->userid] && $viewstaffcompanygoalcap) {
                return \totara_reportbuilder\rb\display\format_string::display($value, $format, $row, $column, $report);
            }
        }

        return get_string('goalnamehidden', 'totara_hierarchy');
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
