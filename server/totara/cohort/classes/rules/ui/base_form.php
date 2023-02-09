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

/**
 * For cohorts that use the form handler as their UI
 */
abstract class base_form extends base {
    /**
     * @var string
     */
    public $handlertype = 'form';

    /**
     * @var string
     */
    public $formclass = 'totara_cohort\rules\ui\form_empty';

    /**
     * @var null|string
     */
    protected $rule = null;

    /**
     *
     * @var emptyruleuiform|null
     */
    public $form = null;

    /** @var string heading description */
    public $description = '';

    /**
     * @return bool
     */
    public function validateResponse() {
        $form = $this->constructForm();
        if (!$form->is_validated()){
            return false;
        }
        return true;
    }

    /**
     * @return emptyruleuiform
     */
    public function constructForm(){
        global $CFG;
        if ($this->form == null) {
            $this->form = new $this->formclass($CFG->wwwroot.'/totara/cohort/rules/ruledetail.php');

            /* @var $mform MoodleQuickForm */
            $mform = $this->form->_form;

            // Add hidden variables
            $mform->addElement('hidden', 'update', 1);
            $mform->setType('update', PARAM_INT);

            $this->form->set_data($this->addFormData());
            $this->addFormFields($mform);
        }
        return $this->form;
    }

    /**
     *
     * @param array $hidden An array of values to be passed into the form as hidden variables
     */
    public function printDialogContent($hidden=array(), $ruleinstanceid=false) {
        global $OUTPUT, $PAGE;

        if (isset($hidden['rule'])) {
            $this->rule = $hidden['rule'];
        }
        echo $OUTPUT->heading(get_string('ruledialogdesc', 'totara_cohort', $this->description), '3', 'cohort-rule-dialog-heading');
        echo $OUTPUT->box_start('cohort-rule-dialog-setting');

        $form = $this->constructForm();
        foreach ($hidden as $name=>$value) {
            $form->_form->addElement('hidden', $name, $value);
        }
        $form->display();

        echo $OUTPUT->box_end();
        echo $PAGE->requires->get_end_code(false);
    }

    /**
     * Get items to add to the form's formdata
     * @return array The data to add to the form
     */
    protected function addFormData() {
        return $this->paramvalues;
    }

    /**
     * Add form fields to this form's dialog. (This should usually be over-ridden by subclasses.)
     * @param MoodleQuickForm $mform
     */
    protected function addFormFields(&$mform) {
        $mform->addElement('static', 'noconfig', '', get_string('ruleneedsnoconfiguration', 'totara_cohort'));
    }
}
