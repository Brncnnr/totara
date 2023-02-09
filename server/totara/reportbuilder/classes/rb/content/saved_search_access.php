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
 * Restrict content by saved search public flag
 */
class saved_search_access extends base {

    const TYPE = 'saved_search_access_content';

    /**
     * Generate the SQL to apply this content restriction
     *
     * @param string $field SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {
        global $USER;

        $userid = (int)$USER->id;

        $params = [];
        $sql = "{$field} IN (SELECT id FROM {report_builder_saved} WHERE ispublic = 1 OR userid = $userid)";

        $restriction = [$sql, $params];
        return $restriction;
    }

    /**
     * Generate a human-readable text string describing the restriction
     *
     * @param string $title Name of the field being restricted
     * @param integer $reportid ID of the report
     *
     * @return string Human readable description of the restriction
     */
    public function text_restriction($title, $reportid) {
        return get_string('savedsearchaccessenforced', 'totara_reportbuilder');
    }

    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @param object &$mform Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     * @param string $title Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title) {
        $type = substr(get_class($this), 3);
        $enable = \reportbuilder::get_setting($reportid, $type, 'enable');

        $mform->addElement('header', 'saved_search_access', get_string('showbyx', 'totara_reportbuilder', get_string('savedsearchaccess', 'totara_reportbuilder')));
        $mform->setExpanded('saved_search_access');
        $mform->addElement('checkbox', 'saved_search_access_enable', '',
            get_string('showbasedonx', 'totara_reportbuilder', get_string('savedsearchaccess', 'totara_reportbuilder')));
        $mform->setDefault('saved_search_access_enable', $enable);
        $mform->disabledIf('saved_search_access_enable', 'contentenabled', 'eq', 0);
    }

    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param integer $reportid ID of the report to process
     * @param object $fromform Moodle form data received via form submission
     *
     * @return boolean True if form was successfully processed
     */
    public function form_process($reportid, $fromform) {
        $status = true;
        $enable = (isset($fromform->saved_search_access_enable) && $fromform->saved_search_access_enable) ? 1 : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE, 'enable', $enable);

        return $status;
    }
}
