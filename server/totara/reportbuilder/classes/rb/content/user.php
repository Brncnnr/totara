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
 * Restrict content by a particular user or group of users
 */
class user extends base {

    const USER_OWN = 1;
    const USER_DIRECT_REPORTS = 2;
    const USER_INDIRECT_REPORTS = 4;
    const USER_TEMP_REPORTS = 8;

    const TYPE = 'user_content';

    /**
     * Generate the SQL to apply this content restriction.
     *
     * @param array $field      SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {
        global $DB;

        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);
        $restriction = isset($settings['who']) ? $settings['who'] : null;
        $userid = $this->reportfor;


        if (empty($restriction)) {
            return array(' (1 = 1) ', array());
        }

        $conditions = array();
        $params = array();

        $viewownrecord = ($restriction & self::USER_OWN) == self::USER_OWN;
        if ($viewownrecord) {
            $conditions[] = "{$field} = :self";
            $params['self'] = $userid;
        }

        if (($restriction & self::USER_DIRECT_REPORTS) == self::USER_DIRECT_REPORTS) {
            $condition = "d1ja.managerjaid = u1ja.id";
        }

        if (($restriction & self::USER_TEMP_REPORTS) == self::USER_TEMP_REPORTS) {
            if (!isset($condition)) {
                $condition = "d1ja.tempmanagerjaid = u1ja.id";
            } else {
                $condition .= " OR d1ja.tempmanagerjaid = u1ja.id";
            }
        }

        if (isset($condition)) {
            $conditions[] = "{$field} IN (SELECT DISTINCT(d1ja.userid)
                                   FROM {user} u1
                             INNER JOIN {job_assignment} u1ja
                                     ON u1ja.userid = u1.id
                             INNER JOIN {job_assignment} d1ja
                                     ON ({$condition})
                                  WHERE u1.id = :viewer1
                                    AND d1ja.userid != u1.id
                                 )";
            $params['viewer1'] = $userid;
        }

        if (($restriction & self::USER_INDIRECT_REPORTS) == self::USER_INDIRECT_REPORTS) {
            $ilikesql = $DB->sql_concat('u2ja.managerjapath', "'/%'");
            $conditions[] = "{$field} IN (SELECT i2ja.userid
                                       FROM {user} u2
                                 INNER JOIN {job_assignment} u2ja
                                         ON u2ja.userid = u2.id
                                 INNER JOIN {job_assignment} i2ja
                                         ON i2ja.managerjapath LIKE {$ilikesql}
                                      WHERE u2.id = :viewer2
                                        AND i2ja.userid != u2.id
                                        AND i2ja.managerjaid != u2ja.id
                                    )";
            $params['viewer2'] = $userid;
        }

        $sql = implode(' OR ', $conditions);

        return array(" ($sql) ", $params);
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
        global $DB;

        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);
        $who = isset($settings['who']) ? $settings['who'] : 0;
        $userid = $this->reportfor;

        $user = $DB->get_record('user', array('id' => $userid));

        $strings = array();
        $strparams = array('field' => $title, 'user' => fullname($user));

        if (($who & self::USER_OWN) == self::USER_OWN) {
            $strings[] = get_string('contentdesc_userown', 'totara_reportbuilder', $strparams);
        }

        if (($who & self::USER_DIRECT_REPORTS) == self::USER_DIRECT_REPORTS) {
            $strings[] = get_string('contentdesc_userdirect', 'totara_reportbuilder', $strparams);
        }

        if (($who & self::USER_INDIRECT_REPORTS) == self::USER_INDIRECT_REPORTS) {
            $strings[] = get_string('contentdesc_userindirect', 'totara_reportbuilder', $strparams);
        }

        if (($who & self::USER_TEMP_REPORTS) == self::USER_TEMP_REPORTS) {
            $strings[] = get_string('contentdesc_usertemp', 'totara_reportbuilder', $strparams);
        }

        if (empty($strings)) {
            return $title . ' ' . get_string('isnotfound', 'totara_reportbuilder');
        }

        return implode(get_string('or', 'totara_reportbuilder'), $strings);
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
        $who = \reportbuilder::get_setting($reportid, self::TYPE, 'who');

        $mform->addElement('header', 'user_header', get_string('showbyx',
            'totara_reportbuilder', lcfirst($title)));
        $mform->setExpanded('user_header');
        $mform->addElement('checkbox', 'user_enable', '',
            get_string('showbasedonx', 'totara_reportbuilder', lcfirst($title)));
        $mform->disabledIf('user_enable', 'contentenabled', 'eq', 0);
        $mform->setDefault('user_enable', $enable);
        $checkgroup = array();
        $checkgroup[] =& $mform->createElement('advcheckbox', 'user_who['.self::USER_OWN.']', '',
            get_string('userownrecords', 'totara_reportbuilder'), null, array(0, 1));
        $mform->setType('user_who['.self::USER_OWN.']', PARAM_INT);
        $checkgroup[] =& $mform->createElement('advcheckbox', 'user_who['.self::USER_DIRECT_REPORTS.']', '',
            get_string('userdirectreports', 'totara_reportbuilder'), null, array(0, 1));
        $mform->setType('user_who['.self::USER_DIRECT_REPORTS.']', PARAM_INT);
        $checkgroup[] =& $mform->createElement('advcheckbox', 'user_who['.self::USER_INDIRECT_REPORTS.']', '',
            get_string('userindirectreports', 'totara_reportbuilder'), null, array(0, 1));
        $mform->setType('user_who['.self::USER_INDIRECT_REPORTS.']', PARAM_INT);
        $checkgroup[] =& $mform->createElement('advcheckbox', 'user_who['.self::USER_TEMP_REPORTS.']', '',
            get_string('usertempreports', 'totara_reportbuilder'), null, array(0, 1));
        $mform->setType('user_who['.self::USER_TEMP_REPORTS.']', PARAM_INT);

        $mform->addGroup($checkgroup, 'user_who_group',
            get_string('includeuserrecords', 'totara_reportbuilder'), \html_writer::empty_tag('br'), false);
        $usergroups = array(self::USER_OWN, self::USER_DIRECT_REPORTS, self::USER_INDIRECT_REPORTS, self::USER_TEMP_REPORTS);
        foreach ($usergroups as $usergroup) {
            // Bitwise comparison.
            if (($who & $usergroup) == $usergroup) {
                $mform->setDefault('user_who['.$usergroup.']', 1);
            }
        }
        $mform->disabledIf('user_who_group', 'contentenabled', 'eq', 0);
        $mform->disabledIf('user_who_group', 'user_enable', 'notchecked');
        $mform->addHelpButton('user_header', 'reportbuilderuser', 'totara_reportbuilder');
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
        $enable = (isset($fromform->user_enable) &&
            $fromform->user_enable) ? 1 : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE,
            'enable', $enable);

        // Who checkbox option.
        // Enabled options are stored as user_who[key] = 1 when enabled.
        // Key is a bitwise value to be summed and stored.
        $whovalue = 0;
        $who = isset($fromform->user_who) ?
            $fromform->user_who : array();
        foreach ($who as $key => $option) {
            if ($option) {
                $whovalue += $key;
            }
        }
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE,
            'who', $whovalue);

        return $status;
    }
}
