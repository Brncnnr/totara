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

abstract class base_selector_program extends base {
    /**
     * @var string
     */
    public $handlertype = 'treeview';

    /**
     * @var mixed
     */
    protected $pickertype;

    /**
     * @param string $description Brief description of this rule
     */
    public function __construct($description, $pickertype) {
        $this->description = $description;
        $this->pickertype = $pickertype;
    }

    /**
     * @param array $hidden
     * @param bool $ruleinstanceid
     */
    public function printDialogContent($hidden = array(), $ruleinstanceid = false) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_programs.class.php');

        ///
        /// Setup / loading data
        ///

        // Category id
        $categoryid = optional_param('parentid', 'cat0', PARAM_ALPHANUM);

        // Strip cat from begining of categoryid
        $categoryid = (int) substr($categoryid, 3);

        ///
        /// Setup dialog
        ///

        // Load dialog content generator.
        $dialog = new \totara_cohort\rules\dialog\cohort_rules_programs($categoryid);

        // Set type to multiple.
        $dialog->type = \totara_dialog_content::TYPE_CHOICE_MULTI;
        $dialog->selected_title = '';

        $dialog->urlparams = $hidden;

        // Add data.
        $dialog->load_programs();

        // Set selected items.
        if ($ruleinstanceid) {
            $sql = "SELECT program.id, program.fullname
                    FROM {prog} program
                    INNER JOIN {cohort_rule_params} crp
                        ON program.id=" . $DB->sql_cast_char2int('crp.value') . "
                    WHERE crp.ruleid = ? and crp.name='listofids'
                    ORDER BY program.fullname
                    ";
            $alreadyselected = $DB->get_records_sql($sql, array($ruleinstanceid));
            if (!$alreadyselected) {
                $alreadyselected = array();
            }
        } else {
            $alreadyselected = array();
        }
        $dialog->selected_items = $alreadyselected;

        // Set unremovable items.
        $dialog->unremovable_items = array();

        // Semi-hack to allow for callback to this ui class to generate some elements of the treeview.
        $dialog->cohort_rule_ui = $this;

        // Display.
        $markup = $dialog->generate_markup();

        echo $markup;
    }

    /**
     * Provide extra elements to insert into the top of the "selected items" pane of the treeview
     */
    abstract public function getExtraSelectedItemsPaneWidgets();
}
