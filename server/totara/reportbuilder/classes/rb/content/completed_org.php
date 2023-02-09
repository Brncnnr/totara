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
 * Restrict content by an organisation at time of completion
 *
 * Pass in an integer that represents an organisation ID
 */
class completed_org extends base {
    const CONTENT_ORGCOMP_EQUAL = 0;
    const CONTENT_ORGCOMP_EQUALANDBELOW = 1;
    const CONTENT_ORGCOMP_BELOW = 2;

    const TYPE = 'completed_org_content';

    /**
     * Generate the SQL to apply this content restriction
     *
     * @param string $field SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');

        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);
        $restriction = $settings['recursive'];
        $userid = $this->reportfor;

        // get the user's primary organisation path
        $orgpaths = $DB->get_fieldset_sql(
            "SELECT o.path
               FROM {job_assignment} ja
               JOIN {org} o ON ja.organisationid = o.id
              WHERE ja.userid = ?",
              array($userid));

        // we need the user to have a valid organisation path
        if (empty($orgpaths)) {
            // using 1=0 instead of FALSE for MSSQL support
            return array('1=0', array());
        }

        $constraints = array();
        $params = array();
        switch ($restriction) {
            case self::CONTENT_ORGCOMP_EQUAL:
                foreach ($orgpaths as $orgpath) {
                    $paramname = rb_unique_param('ccor');
                    $constraints[] = "$field = :$paramname";
                    $params[$paramname] = $orgpath;
                }
                break;
            case self::CONTENT_ORGCOMP_BELOW:
                foreach ($orgpaths as $orgpath) {
                    $paramname = rb_unique_param('ccor');
                    $constraints[] = $DB->sql_like($field, ":{$paramname}");
                    $params[$paramname] = $DB->sql_like_escape($orgpath) . '/%';
                }
                break;
            case self::CONTENT_ORGCOMP_EQUALANDBELOW:
                foreach ($orgpaths as $orgpath) {
                    $paramname = rb_unique_param('ccor1');
                    $constraints[] = "$field = :{$paramname}";
                    $params[$paramname] = $orgpath;

                    $paramname = rb_unique_param('ccors');
                    $constraints[] = $DB->sql_like($field, ":$paramname");
                    $params[$paramname] = $DB->sql_like_escape($orgpath) . '/%';
                }
                break;
        }
        $sql = implode(' OR ', $constraints);

        return array("({$sql})", $params);
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

        $userid = $this->reportfor;

        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);

        $orgid = $DB->get_field('job_assignment', 'organisationid', array('userid' => $userid, 'sortorder' => 1));
        if (empty($orgid)) {
            return $title . ' ' . get_string('is', 'totara_reportbuilder') . ' "UNASSIGNED"';
        }
        $orgname = $DB->get_field('org', 'fullname', array('id' => $orgid));

        switch ($settings['recursive']) {
            case self::CONTENT_ORGCOMP_EQUAL:
                return $title . ' ' . get_string('is', 'totara_reportbuilder') .
                    ': "' . $orgname . '"';
            case self::CONTENT_ORGCOMP_EQUALANDBELOW:
                return $title . ' ' . get_string('is', 'totara_reportbuilder') .
                    ': "' . $orgname . '" ' . get_string('orsuborg', 'totara_reportbuilder');
            case self::CONTENT_ORGCOMP_BELOW:
                return $title . ' ' . get_string('isbelow', 'totara_reportbuilder') .
                    ': "' . $orgname . '"';
            default:
                return '';
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
        $recursive = \reportbuilder::get_setting($reportid, self::TYPE, 'recursive');

        $mform->addElement('header', 'completed_org_header',
            get_string('showbyx', 'totara_reportbuilder', lcfirst($title)));
        $mform->setExpanded('completed_org_header');
        $mform->addElement('checkbox', 'completed_org_enable', '',
            get_string('completedorgenable', 'totara_reportbuilder'));
        $mform->setDefault('completed_org_enable', $enable);
        $mform->disabledIf('completed_org_enable', 'contentenabled', 'eq', 0);
        $radiogroup = array();
        $radiogroup[] =& $mform->createElement('radio', 'completed_org_recursive',
            '', get_string('showrecordsinorgandbelow', 'totara_reportbuilder'), self::CONTENT_ORGCOMP_EQUALANDBELOW);
        $radiogroup[] =& $mform->createElement('radio', 'completed_org_recursive',
            '', get_string('showrecordsinorg', 'totara_reportbuilder'), self::CONTENT_ORGCOMP_EQUAL);
        $radiogroup[] =& $mform->createElement('radio', 'completed_org_recursive',
            '', get_string('showrecordsbeloworgonly', 'totara_reportbuilder'), self::CONTENT_ORGCOMP_BELOW);
        $mform->addGroup($radiogroup, 'completed_org_recursive_group',
            get_string('includechildorgs', 'totara_reportbuilder'), \html_writer::empty_tag('br'), false);
        $mform->setDefault('completed_org_recursive', $recursive);
        $mform->disabledIf('completed_org_recursive_group', 'contentenabled',
            'eq', 0);
        $mform->disabledIf('completed_org_recursive_group',
            'completed_org_enable', 'notchecked');
        $mform->addHelpButton('completed_org_header', 'reportbuildercompletedorg', 'totara_reportbuilder');
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
        $enable = (isset($fromform->completed_org_enable) &&
            $fromform->completed_org_enable) ? 1 : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE,
            'enable', $enable);

        // recursive radio option
        $recursive = isset($fromform->completed_org_recursive) ?
            $fromform->completed_org_recursive : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE,
            'recursive', $recursive);

        return $status;
    }
}
