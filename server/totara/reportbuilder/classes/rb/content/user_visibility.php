<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\content;

/**
 * Restrict content by user visibility, that is by deleted and confirmed status
 * and if enabled tenant visibility rules.
 */
final class user_visibility extends base {

    const TYPE = 'user_visibility_content';

    /**
     * Generate the SQL to apply this content restriction.
     *
     * @param string $field SQL field to apply the restriction against
     * @param int $reportid ID of the report
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {
        global $CFG, $DB, $USER;

        $enable = \reportbuilder::get_setting($reportid, self::TYPE, 'enable');
        if (!$enable) {
            return ["1=1", []]; // No restrictions.
        }

        $params = [];
        $guestparam = $DB->get_unique_param('guestid');
        $params[$guestparam] = guest_user()->id;

        if (!$CFG->tenantsenabled) {
            $sql = 'EXISTS (
                SELECT cruv_u.id
                  FROM "ttr_user" cruv_u
                 WHERE cruv_u.deleted = 0 AND cruv_u.confirmed = 1 AND cruv_u.id <> :' . $guestparam . '
                       AND cruv_u.id = ' . $field . '
            )';
            return [$sql, $params];
        }

        if (!isloggedin() || isguestuser()) {
            $sql = 'EXISTS (
                SELECT cruv_u.id
                  FROM "ttr_user" cruv_u
                 WHERE cruv_u.deleted = 0 AND cruv_u.confirmed = 1 AND cruv_u.id <> :' . $guestparam . '
                       AND cruv_u.tenantid IS NULL AND cruv_u.id = ' . $field . ' 
            )';
            return [$sql, $params];
        }

        if (!property_exists($USER, 'tenantid')) {
            debugging('Missing USER->tenantid, cannot show report data due to content restriction', DEBUG_DEVELOPER);
            return ["1=0", $params];
        }

        // One extra query to simplify complex queries.
        $sql = 'SELECT cm.cohortid
                  FROM "ttr_tenant" t
                  JOIN "ttr_cohort_members" cm ON cm.cohortid = t.cohortid
                 WHERE cm.userid = :userid';
        $mytenantcohortids = $DB->get_fieldset_sql($sql, ['userid' => $USER->id]);

        if (!$mytenantcohortids) {
            if ($USER->tenantid) {
                // This should not happen.
                return ["1=0", $params];
            }
            $sql = 'EXISTS (
                SELECT cruv_u.id
                  FROM "ttr_user" cruv_u
                 WHERE cruv_u.deleted = 0 AND cruv_u.confirmed = 1 AND cruv_u.id <> :' . $guestparam . '
                       AND cruv_u.tenantid IS NULL AND cruv_u.id = ' . $field . ' 
            )';
            return [$sql, $params];
        }

        list($cohortequals, $cparams) = $DB->get_in_or_equal($mytenantcohortids, SQL_PARAMS_NAMED, 'cid');
        $params = $params + $cparams;

        if ($USER->tenantid && $CFG->tenantsisolated) {
            $sql = 'EXISTS (
                SELECT cruv_u.id
                  FROM "ttr_user" cruv_u
                  JOIN "ttr_cohort_members" cruv_cm ON cruv_cm.userid = cruv_u.id AND cruv_cm.cohortid ' . $cohortequals . '
                 WHERE cruv_u.deleted = 0 AND cruv_u.confirmed = 1 AND cruv_u.id <> :' . $guestparam . '
                       AND cruv_u.id = ' . $field . ' 
            )';
        } else {
            $sql = 'EXISTS (
                SELECT cruv_u.id
                  FROM "ttr_user" cruv_u
             LEFT JOIN "ttr_cohort_members" cruv_cm ON cruv_cm.userid = cruv_u.id AND cruv_cm.cohortid ' . $cohortequals . '
                 WHERE cruv_u.deleted = 0 AND cruv_u.confirmed = 1 AND cruv_u.id <> :' . $guestparam . '
                       AND cruv_u.id = ' . $field . '
                       AND (cruv_cm.id IS NOT NULL OR cruv_u.tenantid IS NULL) 
            )';
        }

        return [$sql, $params];
    }

    /**
     * Generate a human-readable text string describing the restriction
     *
     * @param string $title Name of the field being restricted
     * @param int $reportid ID of the report
     * @return string Human readable description of the restriction
     */
    public function text_restriction($title, $reportid) {
        return get_string('user_visibility', 'totara_reportbuilder');
    }

    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @param \MoodleQuickForm &$mform form object to modify (passed by reference)
     * @param int $reportid ID of the report being adjusted
     * @param string $title Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title) {
        $mform->addElement('header', 'user_visibility_header', get_string('user_visibility', 'totara_reportbuilder'));
        $mform->addHelpButton('user_visibility_header', 'user_visibility', 'totara_reportbuilder');
        $mform->setExpanded('user_visibility_header');

        $enable = \reportbuilder::get_setting($reportid, self::TYPE, 'enable');
        $mform->addElement('checkbox', 'user_visibility_enable', '', get_string('user_visibility_checkbox', 'totara_reportbuilder'));
        $mform->setDefault('user_visibility_enable', $enable);
        $mform->disabledIf('user_visibility_enable', 'contentenabled', 'eq', 0);
    }

    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param int $reportid ID of the report to process
     * @param \stdClass $fromform form data received via form submission
     * @return bool True if form was successfully processed
     */
    public function form_process($reportid, $fromform) {
        $visibilityenable = $fromform->user_visibility_enable ?? 0;
        return \reportbuilder::update_setting($reportid, self::TYPE, 'enable', $visibilityenable);
    }
}
