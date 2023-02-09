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
 * Restrict content by a position ID
 *
 * Pass in an integer that represents the position ID
 */
class current_pos extends base {

    const TYPE = 'current_pos_content';

    // Define some constants for the selector options.
    const CONTENT_POS_EQUAL = 0;
    const CONTENT_POS_EQUALANDBELOW = 1;
    const CONTENT_POS_BELOW = 2;

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
        $posids = array();
        foreach ($jobs as $job) {
            if ($job->positionid) {
                $posids[] = $job->positionid;
            }
        }

        if (empty($posids)) {
            // There will be no match, no need to run the big query, empty result will do.
            return array("$field = NULL", array());
        }

        list($possql, $params) = $DB->get_in_or_equal($posids, SQL_PARAMS_NAMED, 'posid');
        $viewpospath = $DB->sql_concat('viewerpos.path', "'/%'");

        if ($restriction == self::CONTENT_POS_EQUAL) {
            $wheresql = "$field IN (
            SELECT ja.userid
              FROM {job_assignment} ja
              JOIN {pos} viewerpos ON viewerpos.id = ja.positionid
             WHERE viewerpos.id $possql)";

            return array($wheresql, $params);
        }

        if ($restriction == self::CONTENT_POS_BELOW) {
            $wheresql = "$field IN (
            SELECT ja.userid
              FROM {job_assignment} ja
              JOIN {pos} pos ON pos.id = ja.positionid
              JOIN {pos} viewerpos ON pos.path LIKE $viewpospath
             WHERE viewerpos.id $possql)";

            return array($wheresql, $params);
        }

        if ($restriction == self::CONTENT_POS_EQUALANDBELOW) {
            $wheresql = "$field IN (
            SELECT ja.userid
              FROM {job_assignment} ja
              JOIN {pos} pos ON pos.id = ja.positionid
              JOIN {pos} viewerpos ON pos.path LIKE $viewpospath OR viewerpos.id = pos.id
             WHERE viewerpos.id $possql)";

            return array($wheresql, $params);
        }

        // Invalid restriction, empty result will do.
        debugging('Invalid restriction type detected', DEBUG_DEVELOPER);
        return array("$field = NULL", array());
    }

    /**
     * Return hierarchy prefix to which this restriction applies
     *
     * @return string Hierarchy prefix
     */
    public function sql_hierarchy_restriction_prefix() {
        return 'pos';
    }

    /**
     * Generate the SQL to apply this content restriction to position queries
     * in position dialogs used in reports.
     *
     * NOTE: always return parent categories even if user is not allowed to see data from them,
     *       this is necessary for trees in dialogs.
     *
     * @param string $field position id SQL field to apply the restriction against
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
        $posids = array();
        foreach ($jobs as $job) {
            if ($job->positionid) {
                $posids[] = $job->positionid;
            }
        }

        if (empty($posids)) {
            // There will be no match, NULL is not equal to anything, not even NULL.
            return array("{$field} = NULL", array());
        }

        list($possql, $params) = $DB->get_in_or_equal($posids, SQL_PARAMS_NAMED, 'posid');
        $viewpospath = $DB->sql_concat('viewerpos.path', "'/%'");
        $parentpospath = $DB->sql_concat('pos.path', "'/%'");

        $sql = "SELECT pos.id
                  FROM {pos} pos
                  JOIN {pos} viewerpos ON viewerpos.path LIKE $parentpospath
                 WHERE viewerpos.id $possql";
        $parents = $DB->get_records_sql($sql, $params);
        $parentids = array_keys($parents);

        if ($restriction == self::CONTENT_POS_EQUAL) {
            $itemids = $posids;
        } else if ($restriction == self::CONTENT_POS_BELOW || $restriction == self::CONTENT_POS_EQUALANDBELOW) {
            // Hierarchy has to include full tree from parent to the current restriction,
            // otherwise we won't be able to build a selector dialog.
            $sql = "SELECT pos.id
                      FROM {pos} pos
                      JOIN {pos} viewerpos ON pos.path LIKE $viewpospath OR viewerpos.id = pos.id
                     WHERE viewerpos.id $possql";
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

        list($idsql, $params) = $DB->get_in_or_equal(array_merge($itemids, $parentids), SQL_PARAMS_NAMED, 'posid');
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

        // remove rb_ from start of classname
        $settings = \reportbuilder::get_all_settings($reportid, self::TYPE);

        $posnames = $DB->get_fieldset_sql('SELECT p.fullname FROM {pos} p WHERE EXISTS (SELECT ja.positionid FROM {job_assignment} ja WHERE ja.userid = ? AND p.id = ja.positionid)',
            array($userid));

        $delim = get_string('contentdesc_delim', 'totara_reportbuilder');
        switch ($settings['recursive']) {
            case self::CONTENT_POS_EQUAL:
                return get_string('contentdesc_posequal', 'totara_reportbuilder', format_string(implode($delim, $posnames)));
            case self::CONTENT_POS_EQUALANDBELOW:
                return get_string('contentdesc_posboth', 'totara_reportbuilder', format_string(implode($delim, $posnames)));
            case self::CONTENT_POS_BELOW:
                return get_string('contentdesc_posbelow', 'totara_reportbuilder', format_string(implode($delim, $posnames)));
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

        $mform->addElement('header', 'current_pos_header',
            get_string('showbyx', 'totara_reportbuilder', lcfirst($title)));
        $mform->setExpanded('current_pos_header');
        $mform->addElement('checkbox', 'current_pos_enable', '',
            get_string('currentposenable', 'totara_reportbuilder'));
        $mform->setDefault('current_pos_enable', $enable);
        $mform->disabledIf('current_pos_enable', 'contentenabled', 'eq', 0);
        $radiogroup = array();
        $radiogroup[] =& $mform->createElement('radio', 'current_pos_recursive',
            '', get_string('showrecordsinposandbelow', 'totara_reportbuilder'), self::CONTENT_POS_EQUALANDBELOW);
        $radiogroup[] =& $mform->createElement('radio', 'current_pos_recursive',
            '', get_string('showrecordsinpos', 'totara_reportbuilder'), self::CONTENT_POS_EQUAL);
        $radiogroup[] =& $mform->createElement('radio', 'current_pos_recursive',
            '', get_string('showrecordsbelowposonly', 'totara_reportbuilder'), self::CONTENT_POS_BELOW);
        $mform->addGroup($radiogroup, 'current_pos_recursive_group',
            get_string('includechildpos', 'totara_reportbuilder'), \html_writer::empty_tag('br'), false);
        $mform->setDefault('current_pos_recursive', $recursive);
        $mform->disabledIf('current_pos_recursive_group', 'contentenabled', 'eq', 0);
        $mform->disabledIf('current_pos_recursive_group', 'current_pos_enable', 'notchecked');
        $mform->addHelpButton('current_pos_header', 'reportbuildercurrentpos', 'totara_reportbuilder');
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
        $enable = (isset($fromform->current_pos_enable) &&
            $fromform->current_pos_enable) ? 1 : 0;
        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE, 'enable', $enable);

        // recursive radio option
        $recursive = isset($fromform->current_pos_recursive) ?
            $fromform->current_pos_recursive : 0;

        $status = $status && \reportbuilder::update_setting($reportid, self::TYPE, 'recursive', $recursive);

        return $status;
    }
}
