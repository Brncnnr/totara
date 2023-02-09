<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara_cohort
 */

namespace totara_cohort\rules\ui;

defined('MOODLE_INTERNAL') || die();

use totara_cohort\rules\ui\base as base;

class cohort_member extends base {
    /**
     * @var string
     */
    public $handlertype = 'treeview';

    /**
     * @var array
     */
    public $params = array(
        'cohortids' => 1,
        'incohort' => 0
    );

    /**
     * @param array $hidden
     * @param bool $ruleinstanceid
     */
    public function printDialogContent($hidden = array(), $ruleinstanceid = false) {
        global $CFG, $DB;

        $type = !empty($hidden['type']) ? $hidden['type'] : '';
        $id = !empty($hidden['id']) ? $hidden['id'] : 0;
        $rule = !empty($hidden['rule']) ? $hidden['rule'] : '';
        // Get sql to exclude current cohort
        switch ($type) {
            case 'rule':
                $sql = "SELECT DISTINCT crc.cohortid
                    FROM {cohort_rules} cr
                    INNER JOIN {cohort_rulesets} crs ON crs.id = cr.rulesetid
                    INNER JOIN {cohort_rule_collections} crc ON crc.id = crs.rulecollectionid
                    WHERE cr.id = ? ";
                $currentcohortid = $DB->get_field_sql($sql, array($id), IGNORE_MULTIPLE);
                break;
            case 'ruleset':
                $sql = "SELECT DISTINCT crc.cohortid
                    FROM {cohort_rulesets} crs
                    INNER JOIN {cohort_rule_collections} crc ON crc.id = crs.rulecollectionid
                    WHERE crs.id = ? ";
                $currentcohortid = $DB->get_field_sql($sql, array($id), IGNORE_MULTIPLE);
                break;
            case 'cohort':
                $currentcohortid = $id;
                break;
            default:
                $currentcohortid =  0;
                break;
        }

        // Get cohorts
        $sql = "SELECT c.id,
                CASE WHEN c.idnumber IS NULL OR c.idnumber = '' OR c.idnumber = '0'
                    THEN c.name
                    ELSE " . $DB->sql_concat("c.name", "' ('", "c.idnumber", "')'") .
            "END AS fullname
            FROM {cohort} c";
        if (!empty($currentcohortid)) {
            $sql .= ' WHERE c.id != ? ';
        }
        $sql .= ' ORDER BY c.name, c.idnumber';
        $items = $DB->get_records_sql($sql, array($currentcohortid));

        // Set up dialog
        $dialog = new \totara_cohort\rules\dialog\manager_cohortmember();
        $dialog->type = \totara_dialog_content::TYPE_CHOICE_MULTI;
        $dialog->items = $items;
        $dialog->selected_title = 'itemstoadd';
        $dialog->searchtype = 'cohort';
        $dialog->urlparams = array('id' => $id, 'type' => $type, 'rule' => $rule);
        if (!empty($currentcohortid)) {
            $dialog->disabled_items = array($currentcohortid);
            $dialog->customdata['current_cohort_id'] = $currentcohortid;
        }

        // Set selected items
        if ($ruleinstanceid) {
            $sql = "SELECT c.id,
                CASE WHEN c.idnumber IS NULL OR c.idnumber = '' OR c.idnumber = '0'
                    THEN c.name
                    ELSE " . $DB->sql_concat("c.name", "' ('", "c.idnumber", "')'") .
                "END AS fullname
                FROM {cohort} c
                INNER JOIN {cohort_rule_params} crp
                    ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE crp.ruleid = ? AND crp.name = 'cohortids'
                ORDER BY c.name, c.idnumber
                ";
            $alreadyselected = $DB->get_records_sql($sql, array($ruleinstanceid));
        } else {
            $alreadyselected = array();
        }
        $dialog->selected_items = $alreadyselected;
        $dialog->unremovable_items = $alreadyselected;
        $dialog->incohort = isset($this->incohort) ? $this->incohort : '';

        // Display
        $markup = $dialog->generate_markup();
        echo $markup;
    }

    /**
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
        $cohortids = required_param('selected', PARAM_SEQUENCE);
        $cohortids = explode(',', $cohortids);
        $this->cohortids = $sqlhandler->cohortids = $cohortids;

        $incohort = required_param('incohort', PARAM_BOOL);
        $this->incohort = $sqlhandler->incohort = $incohort;

        $sqlhandler->write();
    }

    /**
     * @param int   $ruleid
     * @param bool  $static
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB;

        $strvar = new \stdClass();
        if ($this->incohort) {
            $strvar->desc = get_string('useriscohortmember', 'totara_cohort');
        } else {
            $strvar->desc = get_string('userisnotcohortmember', 'totara_cohort');
        }

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->cohortids);
        $sqlparams[] = $ruleid;
        $sql = "SELECT c.id,
                CASE WHEN c.idnumber IS NULL OR c.idnumber = '' OR c.idnumber = '0'
                    THEN c.name
                    ELSE " . $DB->sql_concat("c.name", "' ('", "c.idnumber", "')'") .
            "END AS fullname, crp.id AS paramid
            FROM {cohort} c
            INNER JOIN {cohort_rule_params} crp ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE c.id {$sqlin}
            AND crp.name = 'cohortids' AND crp.ruleid = ?
            ORDER BY c.name, c.idnumber";
        $cohortlist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($cohortlist as $i => $c) {
            $value = '"' . $c->fullname . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($c->paramid);
            }
            $cohortlist[$i] = \html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($cohortlist, $ruleid, $static);
        $paramseparator = \html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $cohortlist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array $cohortlist
     * @param int   $ruleinstanceid
     * @param bool  $static
     * @return void
     */
    protected function add_missing_rule_params(array &$cohortlist, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($cohortlist) < count($this->cohortids)) {
            // Detected that there is a missing cohort
            $fullparams = $DB->get_records('cohort_rule_params', array(
                'ruleid' => $ruleinstanceid,
                'name' => 'cohortids'
            ), "", "value AS cohortid, id AS paramid");
        }

        foreach ($this->cohortids as $cohortid) {
            if (!isset($cohortlist[$cohortid])) {
                // So, the missing $cohortid that does not existing in $cohortlist array_keys. Which
                // we have to notify the users that it is missing.
                $item = isset($fullparams[$cohortid]) ? $fullparams[$cohortid] : null;
                if (!$item) {
                    debugging("Missing the rule parameter for cohort id {$cohortid}");
                    continue;
                }

                $a = (object) array('id' => $cohortid);
                $value = "\"" . get_string("deleteditem", "totara_cohort", $a) . "\"";
                if (!$static) {
                    $value .= $this->param_delete_action_icon($item->paramid);
                }

                $cohortlist[$cohortid] = \html_writer::tag('span', $value, array(
                    'class' => 'ruleparamcontainer cohortdeletedparam'
                ));
            }
        }
    }
}
