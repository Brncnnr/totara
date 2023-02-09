<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\forms;

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

use moodleform;
use totara_evidence\customfield_area;
use totara_evidence\entity\evidence_item;
use totara_mvc\viewable;

class edit_evidence extends moodleform implements viewable {

    protected function definition(): void {
        $form = $this->_form;
        $item = $this->_customdata['item'];

        $form->addElement('hidden', 'id', $item->id);
        $form->addElement('hidden', 'typeid', $item->typeid);
        $form->addElement('hidden', 'user_id', $item->user_id);
        $form->addElement('hidden', 'submit_url', $item->submit_url);
        $form->addElement('hidden', 'cancel_url', $item->cancel_url);
        $form->setType('id', PARAM_INT);
        $form->setType('typeid', PARAM_INT);
        $form->setType('user_id', PARAM_INT);
        $form->setType('submit_url', PARAM_URL);
        $form->setType('cancel_url', PARAM_URL);

        $form->addElement('text', 'name', get_string('evidence_name', 'totara_evidence'), 'maxlength="1024" size="30"');
        $form->setType('name', PARAM_TEXT);

        customfield_definition(
            $form,
            $item,
            customfield_area\evidence::get_prefix(),
            $item->typeid,
            customfield_area\evidence::get_base_table(),
            true,
            false,
            false
        );

        $this->add_action_buttons(
            true,
            $item->id ?
                get_string('save_changes', 'totara_evidence') :
                get_string('save_evidence_item', 'totara_evidence')
        );
    }

    /**
     * If there are errors return array ("fieldname"=>"error message"),
     * otherwise true if ok.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $errors += customfield_validation(
            (object) $data,
            customfield_area\evidence::get_prefix(),
            customfield_area\evidence::get_base_table()
        );
        return $errors;
    }

    /**
     * This method is called after definition(), data submission and set_data().
     * All form setup that is dependent on form values should go in here.
     */
    public function definition_after_data(): void {
        $item = new evidence_item($this->_customdata['id'] > 0 ? $this->_customdata['id'] : null);
        if ($item->exists()) {
            customfield_definition_after_data(
                $this->_form,
                (object) $item->to_array(),
                customfield_area\evidence::get_prefix(),
                $item->typeid,
                customfield_area\evidence::get_base_table()
            );
        }
    }

}
