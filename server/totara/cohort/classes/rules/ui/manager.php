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

class manager extends base {
    /**
     * @var string
     */
    public $handlertype = 'treeview';

    /**
     * @var array
     */
    public $params = array(
        'isdirectreport' => 0,
        'managerid' => 1
    );

    /**
     * @param array $hidden
     * @param bool $ruleinstanceid
     */
    public function printDialogContent($hidden = array(), $ruleinstanceid = false) {
        global $CFG, $DB;

        // Parent id
        $parentid = optional_param('parentid', 0, PARAM_INT);

        // Only return generated tree html
        $treeonly = optional_param('treeonly', false, PARAM_BOOL);

        $dialog = new \totara_cohort\rules\dialog\manager_cohort();

        // Toggle treeview only display
        $dialog->show_treeview_only = $treeonly;

        // Load items to display
        $dialog->load_items($parentid);

        // Set selected items
        $alreadyselected = array();
        if ($ruleinstanceid) {
            $sql = "SELECT u.id, " . get_all_user_name_fields(true, 'u') . "
                FROM {user} u
                INNER JOIN {cohort_rule_params} crp
                    ON u.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE crp.ruleid = ? AND crp.name='managerid'
                ORDER BY u.firstname, u.lastname
                ";
            $alreadyselected = $DB->get_records_sql($sql, array($ruleinstanceid));
            foreach ($alreadyselected as $k => $v) {
                $alreadyselected[$k]->fullname = fullname($v);
            }
        }
        $dialog->selected_items = $alreadyselected;
        $dialog->isdirectreport = isset($this->isdirectreport) ? $this->isdirectreport : '';

        $dialog->urlparams = $hidden;

        // Display page
        // Display
        $markup = $dialog->generate_markup();
        // Hack to get around the hack that prevents deleting items via dialogs
        $markup = str_replace('<td class="selected" ', '<td class="selected selected-shown" ', $markup);
        echo $markup;
    }

    /**
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
        $isdirectreport = required_param('isdirectreport', PARAM_BOOL);
        $managerid = required_param('selected', PARAM_SEQUENCE);
        $managerid = explode(',', $managerid);
        $this->isdirectreport = $sqlhandler->isdirectreport = (int) $isdirectreport;
        $this->managerid = $sqlhandler->managerid = $managerid;
        $sqlhandler->write();
    }

    /**
     * Get the description of the rule, to be printed on the cohort's rules list page
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB;

        if (!isset($this->isdirectreport) || !isset($this->managerid)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new \stdClass();
        if ($this->isdirectreport) {
            $strvar->desc = get_string('userreportsdirectlyto', 'totara_cohort');
        } else {
            $strvar->desc = get_string('userreportsto', 'totara_cohort');
        }

        $usernamefields = get_all_user_name_fields(true, 'u');
        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->managerid);
        $sqlparams[] = $ruleid;
        $sql = "SELECT u.id, {$usernamefields}, crp.id AS paramid
            FROM {user} u
            INNER JOIN {cohort_rule_params} crp ON u.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE u.id {$sqlin}
            AND crp.name = 'managerid' AND crp.ruleid = ?";
        $userlist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($userlist as $i => $u) {
            $value = '"' . fullname($u) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($u->paramid);
            }
            $userlist[$i] = \html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };
        // Sort by fullname
        sort($userlist);

        $paramseparator = \html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $userlist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }
}
