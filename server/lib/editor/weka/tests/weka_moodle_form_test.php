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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package editor_weka
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');

use core\json_editor\node\paragraph;

class core_form_weka_editor_testcase extends advanced_testcase {

    /** @var temp_editor_form */
    private $form;

    /** @var MoodleQuickForm Keeps reference of dummy form object */
    private $mform;

    protected function tearDown(): void {
        $this->form = null;
        $this->mform = null;
        parent::tearDown();
    }

    /**
     * Initalize test wide variable, it is called in start of the testcase
     */
    protected function setUp(): void {
        parent::setUp();

        // Set weka as the default editor.
        set_user_preference('htmleditor', 'weka');

        // Get form data.
        $this->form = new temp_editor_form();
        $this->mform = $this->form->getform();
    }

    /**
     * Confirm that setting user preference to weka works as expected.
     */
    public function test_default_editor() {
        // Should be weka as set in setup.
        $preferred = editors_get_preferred_editor();
        $this->assertInstanceOf(weka_texteditor::class, $preferred);

        // Change to plain text.
        set_user_preference('htmleditor', 'textarea');
        $preferred = editors_get_preferred_editor();
        $this->assertInstanceOf(textarea_texteditor::class, $preferred);
    }

    /**
     * Confirm exportvalue works as expected.
     */
    public function test_exportvalue() {
        // Empty data test.
        $empty_values = $this->get_empty_submit_values();
        $el = $this->add_editor_element($empty_values);

        // Add the content key.
        $text = json_decode($empty_values['test_editor']['text'], true);
        $text['content'][0]['content'] = [];
        $empty_values['test_editor']['text'] = json_encode($text);

        $this->assertSame($empty_values, $el->exportValue($empty_values, true));
        $this->assertDebuggingNotCalled();

        // Valid data test.
        $submit_values = $this->get_submit_values();
        $el = $this->add_editor_element($submit_values);
        $this->assertSame($submit_values, $el->exportValue($submit_values, true));
        $this->assertDebuggingNotCalled();

        // Invalid data test.
        $invalid_submit_values = $this->get_invalid_submit_values();
        $el->exportValue($invalid_submit_values, true);
        $this->assertDebuggingCalled();
    }

    /**
     * Confirm json validation works as expected.
     */
    public function test_validate() {
        $this->add_editor_element([]);

        // Empty data test.
        $submit_values = $this->get_empty_submit_values();
        $this->mform->updateSubmission($submit_values, []);
        $this->form->get_data();
        $error = $this->mform->getElementError('test_editor');
        $this->assertEmpty($error);
        $this->assertDebuggingNotCalled();

        // Reset validated flag.
        $this->form->reset_validated();

        // Valid data test.
        $submit_values = $this->get_submit_values();
        $this->mform->updateSubmission($submit_values, []);
        $this->form->get_data();
        $error = $this->mform->getElementError('test_editor');
        $this->assertEmpty($error);
        $this->assertDebuggingNotCalled();

        // Reset validated flag.
        $this->form->reset_validated();

        // Invalid data test.
        $submit_values = $this->get_invalid_submit_values();
        $this->mform->updateSubmission($submit_values, []);
        $this->form->get_data();
        $error = $this->mform->getElementError('test_editor');
        $this->assertNotEmpty($error);
        $this->assertDebuggingCalled();
        $this->assertEquals(get_string('err_json_editor', 'form'), $error);
    }

    /**
     * Testcase to check onQuickformEvent
     */
    public function test_onquickformevent() {
        $submit_values = $this->get_submit_values();
        $el = $this->add_editor_element($submit_values);
        $el->onQuickFormEvent('updateValue', null, $this->mform);
        $this->assertSame($submit_values['test_editor'], $el->getValue());
    }

    /**
     * @return array
     */
    private function get_empty_submit_values(): array {
        return [
            'test_editor' => [
                'text' => json_encode([
                    'type' => 'doc',
                    'content' => [
                        [
                            'type' => paragraph::get_type(),
                            // no content key here - mimicking an enter being pressed in the editor.
                        ],
                    ]
                ]),
                'format' => FORMAT_JSON_EDITOR,
                'itemid' => null
            ]
        ];
    }

    /**
     * @return array
     */
    private function get_submit_values(): array {
        return [
            'test_editor' => [
                'text' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('This is a test')]
                ]),
                'format' => FORMAT_JSON_EDITOR,
                'itemid' => null
            ]
        ];
    }

    /**
     * @return array
     */
    private function get_invalid_submit_values(): array {
        return [
            'test_editor' => [
                'text' => json_encode([
                    // 'type' is missing here
                    'content' => [paragraph::create_json_node_from_text('This is a test')]
                ]),
                'format' => FORMAT_JSON_EDITOR,
                'itemid' => null
            ]
        ];
    }

    /**
     * @param array $submit_values
     *
     * @return MoodleQuickForm_editor
     */
    private function add_editor_element(array $submit_values): MoodleQuickForm_editor {
        // Set editor options.
        $editoroptions = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => 1024,
            'noclean' => false
        ];

        /** @var MoodleQuickForm_editor $el */
        $el = $this->mform->addElement('editor', 'test_editor', 'test', null, $editoroptions);
        $this->assertTrue($el instanceof MoodleQuickForm_editor);
        $this->mform->setDefaults($submit_values);

        return $el;
    }
}

/**
 * Form object to be used in test case.
 */
class temp_editor_form extends moodleform {

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
    public function getform() {
        $mform = $this->_form;
        // set submitted flag, to simulate submission
        $mform->_flagSubmitted = true;
        return $mform;
    }

    /**
     * Reset the validation flag so we can update submitted values
     * without re-initialising the form and elements.
     */
    public function reset_validated(): void {
        $this->_validated = null;
    }
}
