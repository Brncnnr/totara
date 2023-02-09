<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_contentmarketplace
 */

use mod_contentmarketplace\completion\condition;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/course/moodleform_mod.php");


class mod_contentmarketplace_mod_form extends moodleform_mod {
    /**
     * @return void
     */
    protected function definition() {
        $moodle_form = $this->_form;
        $moodle_form->addElement('header', 'general', get_string('general', 'form'));

        $current_data = $this->get_current();

        // Default to maximum length.
        $name_length = 200;
        if (null !== $current_data && $current_data->name) {
            $name_length = strlen($current_data->name);
        }

        $moodle_form->addElement('text', 'name', get_string('name'), ['size' => $name_length]);
        $moodle_form->setType('name', PARAM_TEXT);
        $moodle_form->addRule('name', null, 'required', null, 'client');
        $moodle_form->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * @return string[]
     */
    public function add_completion_rules() {
        $moodle_form = $this->_form;

        // Define an additional completion condition for content marketplace activity.
        $element_group[] = $moodle_form->createElement(
            'advcheckbox',
            'completion_condition',
            null, // Label comes from the group below.
            condition::get_content_marketplace_conditions_string('contentmarketplace_linkedin'),
            null, // No additional attributes
            [0, condition::CONTENT_MARKETPLACE]
        );

        $moodle_form->addGroup(
            $element_group,
            'completion_condition_group',
            get_string('completion_condition', 'mod_contentmarketplace'),
            ['<br/>'],
            false
        );

        return ['completion_condition_group'];
    }

    /**
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        if ($data['completion'] == COMPLETION_TRACKING_AUTOMATIC) {
            // Validate condition rules if the completion tracking automatic is enabled.
            return !empty($data['completion_condition']) && condition::is_valid($data['completion_condition']);
        }

        return true;
    }
}
