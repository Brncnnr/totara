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

/**
 * Base class for a cohort ui. This handles all the content that goes inside the dialog for the rule,
 * also processing the input from the dialog, and printing a description of the rule
 */
abstract class base {
    /**
     * These variables will match one of the group & names in the rule definition list
     * @var string
     */
    public $group, $name;

    /**
     * @var int
     */
    public $ruleinstanceid;

    /**
     * A list of the parameters this rule passes on to its sqlhandler. (The sqlhandler's $param
     * variable should match exactly.)
     * @var array
     */
    public $params = array(
        'operator' => 0,
        'lov' => 1
    );

    /**
     * The actual values to the parameters (if we're printing a dialog to edit an existing rule instance)
     * @var unknown_type
     */
    public $paramvalues = array();

    /**
     * Which dialog handler type should be used. The dialog handler types are defined in cohort/rules/ruledialog.js.php
     * @var string
     */
    public $handlertype = '';

    /**
     * @param string    $group
     * @param string    $name
     */
    public function setGroupAndName($group, $name) {
        $this->group = $group;
        $this->name = $name;
    }

    /**
     * @param array $paramvalues
     */
    public function setParamValues($paramvalues) {
        $this->paramvalues = $paramvalues;
        foreach ($paramvalues as $k=>$v) {
            $this->{$k} = $v;
        }
    }

    /**
     *
     * @param array $hidden hidden variables to add to forms in the dialog (if needed)
     * @param int $ruleinstanceid The instance of the rule, or false if for a new rule
     */
    abstract public function printDialogContent($hidden=array(), $ruleinstanceid=false);

    /**
     *
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    abstract public function handleDialogUpdate($sqlhandler);

    /**
     * Get the description of the rule, to be printed on the cohort's rules list page
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    abstract public function getRuleDescription($ruleid, $static=true);

    /**
     * Print the user params (used in logging)
     */
    public function printParams() {
        $ret = '';
        foreach ($this->params as $k=>$v) {
            $ret .= $k.':'.print_r($this->{$k}, true)."\n";
        }
        return $ret;
    }

    /**
     * Validate the response
     */
    public function validateResponse() {
        return true;
    }
    /**
     * @global core_renderer $OUTPUT
     * @param int $paramid
     * @return string
     */
    public function param_delete_action_icon($paramid) {
        global $OUTPUT;

        $icon = new \core\output\flex_icon('delete', array(
            'alt' => get_string('deleteruleparam', 'totara_cohort'),
            'classes' => 'ruleparam-delete'
        ));
        return $OUTPUT->action_icon('#', $icon, null, array('data-ruleparam-id' => $paramid));
    }

    /**
     * A method of adding missing rule params within all the rule's instances that are going to be added into the rule
     * description. Before returning as a complete string, within method getRuleDescription, this method should be called
     * to detect any parameters within rule are actually invalid ones.
     *
     * @param array $ruledescriptions   => passed by references, as it needed to be updated
     * @param int   $ruleinstanceid     => The rule's id that is going to be checked against
     * @param bool  $static             => Whether the renderer is about displaying readonly text or read with action text
     * @return void
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static=true) {
        // Implementation at the children level
        return;
    }
}
