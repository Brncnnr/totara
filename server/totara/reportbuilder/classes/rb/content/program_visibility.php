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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\content;

/**
 * Restrict content by a program visibility settings
 *
 * NOTE: This restriction requires 'program' and 'ctx' joins to be present
 * in the report source.
 */
final class program_visibility extends base {

    const TYPE = 'program_visibility_content';

    /**
     * Generate the SQL to apply this content restriction.
     *
     * @param array   $fields   SQL field to apply the restriction against
     *                          Not used in this restriction type as visibility relies
     *                          on the fields from the 'program' and 'ctx' joins.
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($fields, $reportid) {
        $params = [];
        $norestriction = [" 1=1 ", $params]; // No restrictions.

        $enable = \reportbuilder::get_setting($reportid, self::TYPE, 'enable');
        if (!$enable) {
            return $norestriction;
        }

        // Only include courses the user is allowed to see.
        list($sql, $params) = totara_visibility_where(
            $this->reportfor,
            'program.id',
            'program.visible',
            'program.audiencevisible',
            'program',
            'program',
            false
        );

        return [$sql, $params];
    }

    /**
     * Generate a human-readable text string describing the restriction
     *
     * @param string  $title    Name of the field being restricted
     * @param integer $reportid ID of the report
     *
     * @return string Human readable description of the restriction
     */
    public function text_restriction($title, $reportid) {
        return get_string('program_visibility', 'totara_reportbuilder');
    }

    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @param object &$mform    Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     * @param string  $title    Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title) {
        $mform->addElement('header', 'program_visibility_header', get_string('program_visibility', 'totara_reportbuilder'));
        $mform->addHelpButton('program_visibility_header', 'program_visibility', 'totara_reportbuilder');
        $mform->setExpanded('program_visibility_header');

        $enable = \reportbuilder::get_setting($reportid, self::TYPE, 'enable');
        $mform->addElement('checkbox', 'program_visibility_enable', '', get_string('program_visibility_checkbox', 'totara_reportbuilder'));
        $mform->setDefault('program_visibility_enable', $enable);
        $mform->disabledIf('program_visibility_enable', 'contentenabled', 'eq', 0);
    }

    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param integer $reportid ID of the report to process
     * @param object  $fromform Moodle form data received via form submission
     *
     * @return bool True if form was successfully processed
     */
    public function form_process($reportid, $fromform) {
        $status = true;

        $visibilityenable = $fromform->program_visibility_enable ?? 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE, 'enable', $visibilityenable);

        return $status;
    }
}
