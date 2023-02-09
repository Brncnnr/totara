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

use HTML_QuickForm_element;
use moodleform;
use MoodleQuickForm_editor;
use totara_evidence\customfield_area;
use totara_mvc\viewable;

class view_field extends moodleform implements viewable {

    public function definition(): void {
        $field = $this->_customdata['field'];
        $field_definition = customfield_area\field_helper::get_field_definition($field->datatype);
        $form = $this->_form;

        // These are required for the form to render and submit but are otherwise unused
        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);
        $form->addElement('hidden', 'datatype', $field->datatype);
        $form->setType('datatype', PARAM_ALPHA);

        // Load the custom fields form
        $field_definition->define_form($form);

        // Load the data for the specific settings
        $field_definition->define_load_preprocess($field);
        $form->setDefaults((array) $field);

        // There aren't any required fields since we aren't editing the form
        $form->_required = [];

        // Freeze all the elements so they can't be edited
        foreach ($form->_elements as $element) {
            /** @var HTML_QuickForm_element $element */
            if (!$element instanceof MoodleQuickForm_editor) {
                // Text editors can't be frozen (MDL-29421)
                $element->freeze();
            }
        }

        // No submit button, just a cancel button
        $form->addElement('cancel', 'cancel', get_string('go_back', 'totara_evidence'));
        $form->closeHeaderBefore('cancel');
    }

}
