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

global $CFG;
require_once($CFG->dirroot. '/totara/hierarchy/lib.php');

use totara_cohort\rules\ui\base as base;

class base_selector_hierarchy extends base {
    /**
     * @var array
     */
    public $params = array(
        'equal'=>0,
        'includechildren'=>0,
        'listofvalues'=>1,
    );

    /**
     * @var string
     */
    public $handlertype = 'treeview';

    /**
     * @var mixed
     */
    public $prefix;

    /**
     * @var string
     */
    public $shortprefix;

    /**
     * @param string $description Brief description of this rule
     */
    public function __construct($description, $prefix) {
        $this->description = $description;
        $this->prefix = $prefix;
        $this->shortprefix = \hierarchy::get_short_prefix($prefix);
    }

    /**
     * @param array $hidden
     * @param bool $ruleinstanceid
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function printDialogContent($hidden=array(), $ruleinstanceid=false) {
        global $CFG, $DB;
        require_once($CFG->libdir.'/adminlib.php');

        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
        require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');


        ///
        /// Setup / loading data
        ///

        // Competency id
//        $compid = required_param('id', PARAM_INT);

        // Parent id
        $parentid = optional_param('parentid', 0, PARAM_INT);

        // Framework id
        $frameworkid = optional_param('frameworkid', 0, PARAM_INT);

        // Only return generated tree html
        $treeonly = optional_param('treeonly', false, PARAM_BOOL);

        // should we show hidden frameworks?
        $showhidden = optional_param('showhidden', false, PARAM_BOOL);

        // check they have permissions on hidden frameworks in case parameter is changed manually
        $context = \context_system::instance();
        if ($showhidden && !has_capability('totara/hierarchy:updatecompetencyframeworks', $context)) {
            print_error('nopermviewhiddenframeworks', 'hierarchy');
        }

        // show search tab instead of browse
        $search = optional_param('search', false, PARAM_BOOL);

        // Setup page
        $alreadyrelated = array();
        $hierarchy = $this->shortprefix;
        if ($ruleinstanceid) {
            $sql = "SELECT hier.id, hier.fullname
                FROM {{$hierarchy}} hier
                INNER JOIN {cohort_rule_params} crp
                    ON hier.id=" . $DB->sql_cast_char2int('crp.value') . "
                INNER JOIN {{$hierarchy}_framework} fw
                    ON hier.frameworkid = fw.id
                WHERE crp.ruleid = {$ruleinstanceid} AND crp.name='listofvalues'
                ORDER BY fw.sortorder, hier.sortthread
                ";
            $alreadyselected = $DB->get_records_sql($sql);
            if (!$alreadyselected) {
                $alreadyselected = array();
            }
        } else {
            $alreadyselected = array();
        }

        ///
        /// Display page
        ///
        // Load dialog content generator
        $dialog = new \totara_cohort\rules\dialog\hierarchy_multi_cohortrule($this->prefix, $frameworkid, $showhidden);

        // Toggle treeview only display
        $dialog->show_treeview_only = $treeonly;

        // Load items to display
        $dialog->load_items($parentid);

        if (!empty($hidden)) {
            $dialog->urlparams = $hidden;
        }

        // Set disabled/selected items
        $dialog->disabled_items = $alreadyrelated;
        $dialog->selected_items = $alreadyselected;
        if (isset($this->equal)) {
            $dialog->equal = $this->equal;
        }
        if (isset($this->includechildren)) {
            $dialog->includechildren = $this->includechildren;
        }

        // Set title
        $dialog->select_title = '';
        $dialog->selected_title = '';

        // Display
        $markup = $dialog->generate_markup();
        // Hack to get around the hack that prevents deleting items via dialogs
        $markup = str_replace('<td class="selected" ', '<td class="selected selected-shown" ', $markup);
        echo $markup;
    }

    /**
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler){
        $equal = required_param('equal', PARAM_BOOL);
        $includechildren = required_param('includechildren', PARAM_BOOL);
        $listofvalues = required_param('selected', PARAM_SEQUENCE);
        $listofvalues = explode(',',$listofvalues);
        $this->includechildren = $sqlhandler->includechildren = (int) $includechildren;
        $this->equal = $sqlhandler->equal = (int) $equal;
        $this->listofvalues = $sqlhandler->listofvalues = $listofvalues;
        $sqlhandler->write();
    }

    /**
     * Get the description of the rule, to be printed on the cohort's rules list page
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $COHORT_RULES_OP_IN, $DB;

        if (
            !isset($this->equal)
            || !isset($this->listofvalues)
            || !is_array($this->listofvalues)
            || !count($this->listofvalues)
        ) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new \stdClass();
        $strvar->desc = $this->description;
        $strvar->join = get_string("is{$COHORT_RULES_OP_IN[$this->equal]}to", 'totara_cohort');
        if ($this->includechildren) {
            $strvar->ext = get_string('orachildof', 'totara_cohort');
        }

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofvalues);
        $sqlparams[] = $ruleid;
        $hierarchy = $this->shortprefix;
        $sql = "SELECT h.id, h.frameworkid, h.fullname, h.sortthread, hfw.fullname AS frameworkname, hfw.sortorder, crp.id AS paramid
            FROM {{$hierarchy}} h
            INNER JOIN {{$hierarchy}_framework} hfw ON h.frameworkid = hfw.id
            INNER JOIN {cohort_rule_params} crp ON h.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE h.id {$sqlin}
            AND crp.name = 'listofvalues' AND crp.ruleid = ?
            ORDER BY hfw.sortorder, h.sortthread";
        $items = $DB->get_records_sql($sql, $sqlparams);
        if (!$items) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $paramseparator = \html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $frameworkid = current($items)->frameworkid;
        $frameworkname = current($items)->frameworkname;
        reset($items);
        $hierarchylist = array();
        $get_rule_markup = function($hierarchylist, $frameworkid, $frameworkname) use($paramseparator) {
            $a = new \stdClass();
            $a->hierarchy = implode($paramseparator, $hierarchylist);
            $a->framework = $frameworkname;
            $frameworkstr = get_string('ruleformat-framework', 'totara_cohort', $a);
            $frameworkspan = \html_writer::tag('span', $frameworkstr,
                array('class' => 'ruleparamcontainer', 'data-ruleparam-framework-id' => $frameworkid));
            return get_string('ruleformat-vars', 'totara_cohort', $a) . $frameworkspan;
        };
        $itemlist = array();
        foreach ($items as $i => $h) {
            $value = '"' . $h->fullname . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($h->paramid);
            }
            if ($frameworkid != $h->frameworkid) {
                $itemlist[] = $get_rule_markup($hierarchylist, $frameworkid, $frameworkname);
                $hierarchylist = array();
                $frameworkid = $h->frameworkid;
            }
            $hierarchylist[$i] = \html_writer::tag('span', $value,
                array('class' => 'ruleparamcontainer', 'data-ruleparam-frameworkid' => $frameworkid));
            $frameworkname = $h->frameworkname;
        };
        // Processing the missing position/organisation here
        $this->add_missing_rule_params($hierarchylist, $ruleid, $static);

        // Process last item.
        $itemlist[] = $get_rule_markup($hierarchylist, $frameworkid, $frameworkname);

        $strvar->vars = implode($paramseparator, $itemlist);
        if (!empty($strvar->ext)) {
            return get_string('ruleformat-descjoinextvars', 'totara_cohort', $strvar);
        } else {
            return get_string('ruleformat-descjoinvars', 'totara_cohort', $strvar);
        }
    }

    /**
     * @param array $hierarchylist
     * @param int $ruleinstanceid
     * @param bool $static
     */
    protected function add_missing_rule_params(array &$hierarchylist, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($hierarchylist) < $this->listofvalues) {
            $fullparams = $DB->get_records('cohort_rule_params', array(
                'ruleid' => $ruleinstanceid,
                'name'   => 'listofvalues',
            ), "", 'value as instanceid, id as paramid');

            // Need full hierarchy list as the contextualised hierarchy list may be incomplete when multiple frameworks are in play.
            $fullhierarchylist = array_flip($DB->get_fieldset_sql("SELECT id FROM {{$this->shortprefix}}"));

            foreach ($this->listofvalues as $instanceid) {
                if (!isset($fullhierarchylist[$instanceid])) {
                    // Detected one of the missing hierachy instance here
                    $item = isset($fullparams[$instanceid]) ? $fullparams[$instanceid] : null;
                    if (!$item) {
                        debugging("Missing the rule param for {$this->prefix} {$instanceid}");
                        continue;
                    }
                    $a = (object) array('id' => $instanceid);
                    $value = "\"" . get_string('deleteditem', 'totara_cohort', $a) . "\"";

                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $hierarchylist[$instanceid] =
                        \html_writer::tag('span', $value, array('class' =>  'ruleparamcontainer cohortdeletedparam'));
                }
            }
        }
    }
}
