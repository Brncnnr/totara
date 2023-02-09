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

/**
 * Restrict content by an organisation ID
 *
 * Pass in an integer that represents the organisation ID
 */
class current_org extends base {

    const TYPE = 'current_org_content';

    // Define some constants for the selector options.
    const CONTENT_ORG_EQUAL = 0;
    const CONTENT_ORG_EQUALANDBELOW = 1;
    const CONTENT_ORG_BELOW = 2;

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

        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);
        $restriction = $settings['recursive'];
        $userid = $this->reportfor;

        $jobs = \totara_job\job_assignment::get_all($userid);
        $orgids = array();
        foreach ($jobs as $job) {
            if ($job->organisationid) {
                $orgids[] = $job->organisationid;
            }
        }

        if (empty($orgids)) {
            // There will be no match, no need to run the big query, empty result will do.
            return array("{$field} = NULL", array());
        }

        list($orgsql, $params) = $DB->get_in_or_equal($orgids, SQL_PARAMS_NAMED, 'orgid');
        $vieworgpath = $DB->sql_concat('viewerorg.path', "'/%'");

        if ($restriction == self::CONTENT_ORG_EQUAL) {
            $wheresql = "$field IN (
            SELECT ja.userid
              FROM {job_assignment} ja
              JOIN {org} viewerorg ON viewerorg.id = ja.organisationid
             WHERE viewerorg.id $orgsql)";

            return array($wheresql, $params);
        }

        if ($restriction == self::CONTENT_ORG_BELOW) {
            $wheresql = "$field IN (
            SELECT ja.userid
              FROM {job_assignment} ja
              JOIN {org} org ON org.id = ja.organisationid
              JOIN {org} viewerorg ON org.path LIKE $vieworgpath
             WHERE viewerorg.id $orgsql)";

            return array($wheresql, $params);
        }

        if ($restriction == self::CONTENT_ORG_EQUALANDBELOW) {
            $wheresql = "$field IN (
            SELECT ja.userid
              FROM {job_assignment} ja
              JOIN {org} org ON org.id = ja.organisationid
              JOIN {org} viewerorg ON org.path LIKE $vieworgpath OR viewerorg.id = org.id
             WHERE viewerorg.id $orgsql)";

            return array($wheresql, $params);
        }

        // Invalid restriction, empty result will do.
        debugging('Invalid restriction type detected', DEBUG_DEVELOPER);
        return array("{$field} = NULL", array());
    }

    /**
     * Return hierarchy prefix to which this restriction applies
     *
     * @return string Hierarchy prefix
     */
    public function sql_hierarchy_restriction_prefix() {
        return 'org';
    }

    /**
     * Generate the SQL to apply this content restriction to organisation queries
     * in organisation dialogs used in reports.
     *
     * NOTE: always return parent categories even if user is not allowed to see data from them,
     *       this is necessary for trees in dialogs.
     *
     * @param string $field organisation id SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_hierarchy_restriction($field, $reportid) {
        global $DB;

        // remove rb_ from start of classname
        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);
        $restriction = $settings['recursive'];
        $userid = $this->reportfor;

        $jobs = \totara_job\job_assignment::get_all($userid);
        $orgids = array();
        foreach ($jobs as $job) {
            if ($job->organisationid) {
                $orgids[] = $job->organisationid;
            }
        }

        if (empty($orgids)) {
            // There will be no match, NULL is not equal to anything, not even NULL.
            return array("{$field} = NULL", array());
        }

        list($orgsql, $params) = $DB->get_in_or_equal($orgids, SQL_PARAMS_NAMED, 'orgid');
        $vieworgpath = $DB->sql_concat('viewerorg.path', "'/%'");
        $parentorgpath = $DB->sql_concat('org.path', "'/%'");

        $sql = "SELECT org.id
                  FROM {org} org
                  JOIN {org} viewerorg ON viewerorg.path LIKE $parentorgpath
                 WHERE viewerorg.id $orgsql";
        $parents = $DB->get_records_sql($sql, $params);
        $parentids = array_keys($parents);

        if ($restriction == self::CONTENT_ORG_EQUAL) {
            $itemids = $orgids;
        } else if ($restriction == self::CONTENT_ORG_BELOW || $restriction == self::CONTENT_ORG_EQUALANDBELOW) {
            // Hierarchy has to include full tree from parent to the current restriction,
            // otherwise we won't be able to build a selector dialog.
            $sql = "SELECT org.id
                      FROM {org} org
                      JOIN {org} viewerorg ON org.path LIKE $vieworgpath OR viewerorg.id = org.id
                     WHERE viewerorg.id $orgsql";
            $items = $DB->get_records_sql($sql, $params);
            $itemids = array_keys($items);
        } else {
            // Invalid restriction, NULL is not equal to anything, not even NULL.
            debugging('Invalid restriction type detected', DEBUG_DEVELOPER);
            return array("{$field} = NULL", array());
        }

        if (!$itemids and !$parentids) {
            return array("{$field} = NULL", array());
        }

        list($idsql, $params) = $DB->get_in_or_equal(array_merge($itemids, $parentids), SQL_PARAMS_NAMED, 'orgid');
        return array("{$field} $idsql", $params);
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

        $orgnames = $DB->get_fieldset_sql('SELECT p.fullname FROM {org} p WHERE EXISTS (SELECT ja.organisationid FROM {job_assignment} ja WHERE ja.userid = ? AND p.id = ja.organisationid)',
            array($userid));

        $delim = get_string('contentdesc_delim', 'totara_reportbuilder');
        switch ($settings['recursive']) {
            case self::CONTENT_ORG_EQUAL:
                return get_string('contentdesc_orgequal', 'totara_reportbuilder', format_string(implode($delim, $orgnames)));
            case self::CONTENT_ORG_EQUALANDBELOW:
                return get_string('contentdesc_orgboth', 'totara_reportbuilder', format_string(implode($delim, $orgnames)));
            case self::CONTENT_ORG_BELOW:
                return get_string('contentdesc_orgbelow', 'totara_reportbuilder', format_string(implode($delim, $orgnames)));
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

        $mform->addElement('header', 'current_org_header',
            get_string('showbyx', 'totara_reportbuilder', lcfirst($title)));
        $mform->setExpanded('current_org_header');
        $mform->addElement('checkbox', 'current_org_enable', '',
            get_string('currentorgenable', 'totara_reportbuilder'));
        $mform->setDefault('current_org_enable', $enable);
        $mform->disabledIf('current_org_enable', 'contentenabled', 'eq', 0);
        $radiogroup = array();
        $radiogroup[] =& $mform->createElement('radio', 'current_org_recursive',
            '', get_string('showrecordsinorgandbelow', 'totara_reportbuilder'), self::CONTENT_ORG_EQUALANDBELOW);
        $radiogroup[] =& $mform->createElement('radio', 'current_org_recursive',
            '', get_string('showrecordsinorg', 'totara_reportbuilder'), self::CONTENT_ORG_EQUAL);
        $radiogroup[] =& $mform->createElement('radio', 'current_org_recursive',
            '', get_string('showrecordsbeloworgonly', 'totara_reportbuilder'), self::CONTENT_ORG_BELOW);
        $mform->addGroup($radiogroup, 'current_org_recursive_group',
            get_string('includechildorgs', 'totara_reportbuilder'), \html_writer::empty_tag('br'), false);
        $mform->setDefault('current_org_recursive', $recursive);
        $mform->disabledIf('current_org_recursive_group', 'contentenabled',
            'eq', 0);
        $mform->disabledIf('current_org_recursive_group', 'current_org_enable',
            'notchecked');
        $mform->addHelpButton('current_org_header', 'reportbuildercurrentorg', 'totara_reportbuilder');
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
        $enable = (isset($fromform->current_org_enable) &&
            $fromform->current_org_enable) ? 1 : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE,
            'enable', $enable);

        // recursive radio option
        $recursive = isset($fromform->current_org_recursive) ?
            $fromform->current_org_recursive : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE,
            'recursive', $recursive);

        return $status;
    }
}
