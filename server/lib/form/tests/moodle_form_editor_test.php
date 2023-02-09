<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package core_form
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * Class core_form_moodle_form_editor_testcase
 */
class core_form_moodle_form_editor_testcase extends advanced_testcase {

    /**
     * Note: Not including weka here. Weka-specific tests for this form are in /lib/editor/weka/.
     *
     * @return string[][]
     */
    public function editor_type_data_provider(): array {
        return [
            ['atto'],
            ['textarea'],
        ];
    }

    /**
     * Check validation doesn't throw any errors.
     * There isn't any proper validation for atto/textarea, but introduction of weka-specific code has caused errors before.
     * Make sure problems aren't re-introduced.
     *
     * @dataProvider editor_type_data_provider
     */
    public function test_validate(string $editor_type): void {
        set_user_preference('htmleditor', $editor_type);

        $form = new core_form_moodle_form_editor_testcase_mock_form();
        $moodle_quick_form = $form->get_form();
        $this->add_editor_element($moodle_quick_form);

        // Empty data test.
        $moodle_quick_form->updateSubmission(['test_editor' => ''], []);
        $form->get_data();
        $error = $moodle_quick_form->getElementError('test_editor');
        self::assertEmpty($error);
        $this->assertDebuggingNotCalled();

        // Reset validated flag.
        $form->reset_validated();

        // Valid data test.
        $moodle_quick_form->updateSubmission(['test_editor' => 'Just a string, not an array like weka'], []);
        $form->get_data();
        $error = $moodle_quick_form->getElementError('test_editor');
        self::assertEmpty($error);
        $this->assertDebuggingNotCalled();

        // Reset validated flag.
        $form->reset_validated();
    }

    /**
     * @param MoodleQuickForm $moodle_quick_form
     */
    private function add_editor_element(MoodleQuickForm $moodle_quick_form): void {
        // Set editor options.
        $editor_options = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => 1024,
            'trusttext' => false,
            'noclean' => false
        ];

        /** @var MoodleQuickForm_editor $element */
        $element = $moodle_quick_form->addElement('editor', 'test_editor', 'test', null, $editor_options);
        self::assertInstanceOf(MoodleQuickForm_editor::class, $element);
    }
}


/**
 * Form object to be used in test case.
 */
class core_form_moodle_form_editor_testcase_mock_form extends moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        // No definition required.
    }

    /**
     * Returns form reference
     * @return MoodleQuickForm
     */
    public function get_form(): MoodleQuickForm {
        $moodle_quick_form = $this->_form;
        // Set submitted flag, to simulate submission.
        $moodle_quick_form->_flagSubmitted = true;
        return $moodle_quick_form;
    }

    /**
     * Reset the validation flag so we can update submitted values
     * without re-initialising the form and elements.
     */
    public function reset_validated(): void {
        $this->_validated = null;
    }
}
