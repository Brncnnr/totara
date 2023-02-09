<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

debugging('This file has been deprecated since 13.0 and should not be included, all classes in this file have been moved to the \totara_reportbuilder\rb\content namespace', DEBUG_DEVELOPER);

/*
 * Restrict content by availability
 *
 * Pass in a column that contains a pipe '|' separated list of official tag ids
 *
 * @deprecated Since Totara 12.0
 */
class rb_prog_availability_content extends \totara_reportbuilder\rb\content\base {
    /**
     * Generate the SQL to apply this content restriction
     *
     * @deprecated Since Totara 12.0
     * @param string $field SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {
        debugging('rb_prog_availability_content::sql_restriction has been deprecated since Totara 12.0', DEBUG_DEVELOPER);

        // The restriction snippet based on the available fields was moved to totara_visibility_where.
        // So no restriction for programs or certifications.
        $restriction = " 1=1 ";

        return array($restriction, array());
    }

    /**
     * Generate a human-readable text string describing the restriction
     *
     * @deprecated Since Totara 12.0
     * @param string $title Name of the field being restricted
     * @param integer $reportid ID of the report
     *
     * @return string Human readable description of the restriction
     */
    public function text_restriction($title, $reportid) {
        debugging('rb_prog_availability_content::text_restriction has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return get_string('contentavailability', 'totara_program');
    }


    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @deprecated Since Totara 12.0
     * @param object &$mform Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     * @param string $title Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title) {
        debugging('rb_prog_availability_content::form_template has been deprecated since Totara 12.0', DEBUG_DEVELOPER);

        global $DB;

        // Get current settings and
        // remove rb_ from start of classname.
        $type = substr(get_class($this), 3);
        $enable = reportbuilder::get_setting($reportid, $type, 'enable');

        $mform->addElement('header', 'prog_availability_header',
            get_string('showbyx', 'totara_reportbuilder', lcfirst($title)));
        $mform->setExpanded('prog_availability_header');
        $mform->addElement('checkbox', 'prog_availability_enable', '',
            get_string('contentavailability', 'totara_program'));
        $mform->setDefault('prog_availability_enable', $enable);
        $mform->disabledIf('prog_availability_enable', 'contentenabled', 'eq', 0);
        $mform->addHelpButton('prog_availability_header', 'contentavailability', 'totara_program');

    }


    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @deprecated Since Totara 12.0
     * @param integer $reportid ID of the report to process
     * @param object $fromform Moodle form data received via form submission
     *
     * @return boolean True if form was successfully processed
     */
    public function form_process($reportid, $fromform) {
        debugging('rb_prog_availability_content::form_process has been deprecated since Totara 12.0', DEBUG_DEVELOPER);

        global $DB;

        $status = true;
        // Remove rb_ from start of classname.
        $type = substr(get_class($this), 3);

        // Enable checkbox option.
        $enable = (isset($fromform->prog_availability_enable) &&
            $fromform->prog_availability_enable) ? 1 : 0;
        $status = $status && reportbuilder::update_setting($reportid, $type,
            'enable', $enable);

        return $status;

    }
}

