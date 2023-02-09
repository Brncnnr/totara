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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\content;

/*
 * Restrict content by a particular date
 *
 * Pass in an integer that contains a unix timestamp
 */
class date extends base {

    const TYPE = 'date_content';

    /**
     * Generate the SQL to apply this content restriction
     *
     * @param string $field SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {
        global $DB;
        $now = time();
        $financialyear = get_config('reportbuilder', 'financialyear');
        $month = substr($financialyear, 2, 2);
        $day = substr($financialyear, 0, 2);

        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);

        // option to include empty date fields
        $includenulls = (isset($settings['incnulls']) &&
            $settings['incnulls']) ?
            " OR {$field} IS NULL OR {$field} = 0 " : " AND {$field} != 0 ";

        switch ($settings['when']) {
            case 'past':
                return array("({$field} < {$now} {$includenulls})", array());
            case 'future':
                return array("({$field} > {$now} {$includenulls})", array());
            case 'last30days':
                $sql = "( ({$field} < {$now}  AND {$field}  >
                    ({$now} - 60*60*24*30)) {$includenulls})";
                return array($sql, array());
            case 'next30days':
                $sql = "( ({$field} > {$now} AND {$field} <
                    ({$now} + 60*60*24*30)) {$includenulls})";
                return array($sql, array());
            case 'currentfinancial':
                $required_year = date('Y', $now);
                $year_before = $required_year - 1;
                $year_after = $required_year + 1;
                if (date('z', $now) >= date('z', mktime(0, 0, 0, $month, $day, $required_year))) {
                    $start = mktime(0, 0, 0, $month, $day, $required_year);
                    $end = mktime(0, 0, 0, $month, $day, $year_after);
                } else {
                    $start = mktime(0, 0, 0, $month, $day, $year_before);
                    $end = mktime(0, 0, 0, $month, $day, $required_year);
                }
                $sql = "( ({$field} >= {$start} AND {$field} <
                    {$end}) {$includenulls})";
                return array($sql, array());
            case 'lastfinancial':
                $required_year = date('Y', $now) - 1;
                $year_before = $required_year - 1;
                $year_after = $required_year + 1;
                if (date('z', $now) >= date('z', mktime(0, 0, 0, $month, $day, $required_year))) {
                    $start = mktime(0, 0, 0, $month, $day, $required_year);
                    $end = mktime(0, 0, 0, $month, $day, $year_after);
                } else {
                    $start = mktime(0, 0, 0, $month, $day, $year_before);
                    $end = mktime(0, 0, 0, $month, $day, $required_year);
                }
                $sql = "( ({$field} >= {$start} AND {$field} <
                    {$end}) {$includenulls})";
                return array($sql, array());
            default:
                // no match
                // using 1=0 instead of FALSE for MSSQL support
                return array("(1=0 {$includenulls})", array());
        }
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

        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);

        // option to include empty date fields
        $includenulls = (isset($settings['incnulls']) &&
                         $settings['incnulls']) ? " (or $title is empty)" : '';

        switch ($settings['when']) {
            case 'past':
                return $title . ' ' . get_string('occurredbefore', 'totara_reportbuilder') . ' ' .
                    userdate(time(), '%c'). $includenulls;
            case 'future':
                return $title . ' ' . get_string('occurredafter', 'totara_reportbuilder') . ' ' .
                    userdate(time(), '%c'). $includenulls;
            case 'last30days':
                return $title . ' ' . get_string('occurredafter', 'totara_reportbuilder') . ' ' .
                    userdate(time() - 60 * 60 * 24 * 30, '%c') . get_string('and', 'totara_reportbuilder') .
                    get_string('occurredbefore', 'totara_reportbuilder') . userdate(time(), '%c') .
                    $includenulls;

            case 'next30days':
                return $title . ' ' . get_string('occurredafter', 'totara_reportbuilder') . ' ' .
                    userdate(time(), '%c') . get_string('and', 'totara_reportbuilder') .
                    get_string('occurredbefore', 'totara_reportbuilder') .
                    userdate(time() + 60 * 60 * 24 * 30, '%c') . $includenulls;
            case 'currentfinancial':
                return $title . ' ' . get_string('occurredthisfinancialyear', 'totara_reportbuilder') .
                    $includenulls;
            case 'lastfinancial':
                return $title . ' ' . get_string('occurredprevfinancialyear', 'totara_reportbuilder') .
                    $includenulls;
            default:
                return 'Error with date content restriction';
        }
    }


    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @param object &$mform Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     * @param string $title Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title) {
        // get current settings
        $enable = \reportbuilder::get_setting($reportid, self::TYPE, 'enable');
        $when = \reportbuilder::get_setting($reportid, self::TYPE, 'when');
        $incnulls = \reportbuilder::get_setting($reportid, self::TYPE, 'incnulls');

        $mform->addElement('header', 'date_header', get_string('showbyx',
            'totara_reportbuilder', lcfirst($title)));
        $mform->setExpanded('date_header');
        $mform->addElement('checkbox', 'date_enable', '',
            get_string('showbasedonx', 'totara_reportbuilder',
            lcfirst($title)));
        $mform->setDefault('date_enable', $enable);
        $mform->disabledIf('date_enable', 'contentenabled', 'eq', 0);
        $radiogroup = array();
        $radiogroup[] =& $mform->createElement('radio', 'date_when', '',
            get_string('thepast', 'totara_reportbuilder'), 'past');
        $radiogroup[] =& $mform->createElement('radio', 'date_when', '',
            get_string('thefuture', 'totara_reportbuilder'), 'future');
        $radiogroup[] =& $mform->createElement('radio', 'date_when', '',
            get_string('last30days', 'totara_reportbuilder'), 'last30days');
        $radiogroup[] =& $mform->createElement('radio', 'date_when', '',
            get_string('next30days', 'totara_reportbuilder'), 'next30days');
        $radiogroup[] =& $mform->createElement('radio', 'date_when', '',
            get_string('currentfinancial', 'totara_reportbuilder'), 'currentfinancial');
        $radiogroup[] =& $mform->createElement('radio', 'date_when', '',
            get_string('lastfinancial', 'totara_reportbuilder'), 'lastfinancial');
        $mform->addGroup($radiogroup, 'date_when_group',
            get_string('includerecordsfrom', 'totara_reportbuilder'), \html_writer::empty_tag('br'), false);
        $mform->setDefault('date_when', $when);
        $mform->disabledIf('date_when_group', 'contentenabled', 'eq', 0);
        $mform->disabledIf('date_when_group', 'date_enable', 'notchecked');
        $mform->addHelpButton('date_header', 'reportbuilderdate', 'totara_reportbuilder');

        $mform->addElement('checkbox', 'date_incnulls',
            get_string('includeemptydates', 'totara_reportbuilder'));
        $mform->setDefault('date_incnulls', $incnulls);
        $mform->disabledIf('date_incnulls', 'date_enable', 'notchecked');
        $mform->disabledIf('date_incnulls', 'contentenabled', 'eq', 0);
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

        // enable checkbox option
        $enable = (isset($fromform->date_enable) &&
            $fromform->date_enable) ? 1 : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE, 'enable', $enable);

        // when radio option
        $when = isset($fromform->date_when) ?
            $fromform->date_when : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE, 'when', $when);

        // include nulls checkbox option
        $incnulls = (isset($fromform->date_incnulls) &&
            $fromform->date_incnulls) ? 1 : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE, 'incnulls', $incnulls);

        return $status;
    }
}
