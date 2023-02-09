<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

use totara_form\form\element\editor,
    totara_form\model,
    totara_form\test\test_definition,
    totara_form\test\test_form,
    totara_form\file_area;
use core\json_editor\node\paragraph;

/**
 * Test for \totara_form\form\element\editor class.
 */
class totara_form_element_editor_testcase extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        require_once(__DIR__  . '/fixtures/test_form.php');
        test_form::phpunit_reset();

        // Clear the file status cache between tests to reduce interference
        clearstatcache();
    }

    protected function tearDown(): void {
        test_form::phpunit_reset();
        parent::tearDown();
    }

    public function test_no_post() {
        $this->setAdminUser();
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var editor $editor1 */
                $editor1 = $model->add(new editor('someeditor1', 'Some editor 1'));
            }
        );
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        $currentdata = array('someeditor1' => '', 'someeditor1format' => null);
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertNull($data);
        $this->assertNull($files);
    }

    public function test_empty_json_submission() {
        $this->setAdminUser();
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var editor $editor1 */
                $editor = $model->add(new editor('test_editor', 'Some test editor'));
            }
        );
        test_form::phpunit_set_definition($definition);

        $valid_data = $this->get_empty_json_submission();
        $postdata = [
            'test_editor' => $valid_data
        ];
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'test_editor' => 'hehe',
            'test_editorformat' => null
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertInstanceOf('stdClass', $data);

        // Add the content key.
        $text = json_decode($valid_data['text'], true);
        $text['content'][0]['content'] = [];
        $text = json_encode($text);

        $expected = [
            'test_editor' => $text,
            'test_editorformat' => (string)FORMAT_JSON_EDITOR,
        ];
        $this->assertSame($expected, (array)$data);
    }

    public function test_valid_json_submission() {
        $this->setAdminUser();
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var editor $editor1 */
                $editor = $model->add(new editor('test_editor', 'Some test editor'));
            }
        );
        test_form::phpunit_set_definition($definition);

        $valid_data = $this->get_valid_json_submission();
        $postdata = [
            'test_editor' => $valid_data
        ];
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'test_editor' => 'hehe',
            'test_editorformat' => null
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertInstanceOf('stdClass', $data);
        $expected = [
            'test_editor' => $valid_data['text'],
            'test_editorformat' => (string)FORMAT_JSON_EDITOR,
        ];
        $this->assertSame($expected, (array)$data);
    }

    public function test_invalid_json_submission() {
        global $OUTPUT;

        $this->setAdminUser();
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var editor $editor1 */
                $editor = $model->add(new editor('test_editor', 'Some test editor'));
            }
        );
        test_form::phpunit_set_definition($definition);

        $invalid_data = $this->get_invalid_json_submission();
        $postdata = [
            'test_editor' => $invalid_data
        ];
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'test_editor' => 'hehe',
            'test_editorformat' => null
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertDebuggingCalled([
            'JSON document is invalid',
            'JSON document is invalid',
        ]);
        $this->assertNotEmpty($data);
        $this->assertEquals("", $data->test_editor);

        $data = $definition->model->export_for_template($OUTPUT);
        $this->assertSame('test_editor', $data['items'][0]['name']);
    }

    public function test_html_submission() {
        $this->setAdminUser();
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var editor $editor1 */
                $editor1 = $model->add(new editor('someeditor1', 'Some editor 1'));
            }
        );
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someeditor1' => array('text' => 'lala', 'format' => FORMAT_HTML),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someeditor1' => 'hehe',
            'someeditor1format' => null
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertInstanceOf('stdClass', $files);
        $expected = array(
            'someeditor1' => 'lala',
            'someeditor1format' => (string)FORMAT_HTML,
        );
        $this->assertSame($expected, (array)$data);
        $this->assertSame(array(), (array)$files);
    }

    public function test_required() {
        $this->setAdminUser();
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var editor $editor1 */
                $editor1 = $model->add(new editor('someeditor1', 'Some editor 1'));
                $editor1->set_attribute('required', true);
            }
        );
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someeditor1' => array('text' => 'lala', 'format' => FORMAT_HTML),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someeditor1' => 'hehe',
            'someeditor1format' => null
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertInstanceOf('stdClass', $files);
        $expected = array(
            'someeditor1' => 'lala',
            'someeditor1format' => (string)FORMAT_HTML,
        );
        $this->assertSame($expected, (array)$data);
        $this->assertSame(array(), (array)$files);

        $postdata = array(
            'someeditor1' => array('text' => '', 'format' => FORMAT_HTML),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someeditor1' => 'hehe',
            'someeditor1format' => null
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertNull($data);
        $this->assertNull($files);
    }

    public function test_submission_files() {
        global $OUTPUT, $PAGE;

        // Clear the file status cache to ensure previous tests are not interfering
        clearstatcache();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        $syscontext = context_system::instance();
        $fs = get_file_storage();

        $PAGE->set_url('/totara/form/tests/element_editor_test.php');
        $PAGE->set_context($syscontext);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var editor $editor1 */
                $editor1 = $model->add(new editor('someeditor1', 'Some editor 1'));
            }
        );
        test_form::phpunit_set_definition($definition);

        $currentdata = array(
            'someeditor1' => '',
            'someeditor1format' => null,
            'someeditor1filearea' => new file_area($syscontext, 'totara_core', 'testarea', null),
        );
        $form = new test_form($currentdata);
        $data = $definition->model->export_for_template($OUTPUT);
        $this->assertSame('someeditor1', $data['items'][0]['name']);
        $this->assertSame('', $data['items'][0]['text']);
        $draftitemid = $data['items'][0]['itemid'];
        $this->assertIsNumeric($draftitemid);
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        $file = $fs->create_file_from_string(['contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftitemid, 'filepath' => '/', 'filename' => 'test.jpg'], 'abc');
        $url = moodle_url::make_draftfile_url($draftitemid, '/', 'test.jpg');
        $postdata = array(
            'someeditor1' => array('text' => 'some image <img src="' . $url . '" alt="test.jpg" />', 'format' => FORMAT_HTML, 'itemid' => $draftitemid),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someeditor1' => '',
            'someeditor1format' => null,
            'someeditor1filearea' => new file_area($syscontext, 'totara_core', 'testarea', null),
        );
        $form = new test_form($currentdata);
        $data = $definition->model->export_for_template($OUTPUT);
        $this->assertSame('someeditor1', $data['items'][0]['name']);
        $this->assertSame($postdata['someeditor1']['text'], $data['items'][0]['text']);
        $this->assertSame($draftitemid, $data['items'][0]['itemid']);
        $this->assertIsNumeric($draftitemid);

        $data = (array)$form->get_data();
        $expected = [
            'someeditor1' => 'some image <img src="@@PLUGINFILE@@/test.jpg" alt="test.jpg" />',
            'someeditor1format' => '1',
        ];
        $this->assertSame($expected, $data);

        $files = $form->get_files();
        $this->assertCount(2, $files->someeditor1);
        /** @var stored_file $draftfile */
        $draftfile = $files->someeditor1[1];
        $this->assertSame($usercontext->id, (int)$draftfile->get_contextid());
        $this->assertSame($draftitemid, $draftfile->get_itemid());
        $this->assertSame('/', $draftfile->get_filepath());
        $this->assertSame('test.jpg', $draftfile->get_filename());

        $form->update_file_area('someeditor1', $syscontext, 3);
        $this->assertTrue($fs->file_exists($syscontext->id, 'totara_core', 'testarea', 3, '/', '.'));
        $this->assertTrue($fs->file_exists($syscontext->id, 'totara_core', 'testarea', 3, '/', 'test.jpg'));

        // Now add another file.
        test_form::phpunit_set_post_data(null);
        $currentdata = $expected;
        $currentdata['someeditor1filearea'] = new file_area($syscontext, 'totara_core', 'testarea', 3);
        $form = new test_form($currentdata);
        $data = $definition->model->export_for_template($OUTPUT);
        $this->assertSame('someeditor1', $data['items'][0]['name']);
        $draftitemid = $data['items'][0]['itemid'];
        $this->assertSame('some image <img src="https://www.example.com/moodle/draftfile.php/' . $usercontext->id . '/user/draft/' . $draftitemid . '/test.jpg" alt="test.jpg" />', $data['items'][0]['text']);
        $this->assertIsNumeric($draftitemid);
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        $file = $fs->create_file_from_string(['contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftitemid, 'filepath' => '/', 'filename' => 'test2.jpg'], 'xyz');
        $url = moodle_url::make_draftfile_url($draftitemid, '/', 'test.jpg');
        $url2 = moodle_url::make_draftfile_url($draftitemid, '/', 'test2.jpg');
        $postdata = array(
            'someeditor1' => array('text' => 'some image <img src="' . $url . '" alt="test.jpg" /><img src="' . $url2 . '" alt="test2.jpg" />', 'format' => FORMAT_HTML, 'itemid' => $draftitemid),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someeditor1' => 'some image <img src="@@PLUGINFILE@@/test.jpg" alt="test.jpg" />',
            'someeditor1format' => '1',
            'someeditor1filearea' => new file_area($syscontext, 'totara_core', 'testarea', 3),
        );
        $form = new test_form($currentdata);
        $data = $definition->model->export_for_template($OUTPUT);
        $this->assertSame('someeditor1', $data['items'][0]['name']);
        $this->assertSame($postdata['someeditor1']['text'], $data['items'][0]['text']);
        $this->assertSame($draftitemid, $data['items'][0]['itemid']);
        $this->assertIsNumeric($draftitemid);

        $data = (array)$form->get_data();
        $expected = [
            'someeditor1' => 'some image <img src="@@PLUGINFILE@@/test.jpg" alt="test.jpg" /><img src="@@PLUGINFILE@@/test2.jpg" alt="test2.jpg" />',
            'someeditor1format' => '1',
        ];
        $this->assertSame($expected, $data);

        $files = $form->get_files();
        $this->assertCount(3, $files->someeditor1);
        $form->update_file_area('someeditor1', $syscontext, 3);
        $this->assertTrue($fs->file_exists($syscontext->id, 'totara_core', 'testarea', 3, '/', '.'));
        $this->assertTrue($fs->file_exists($syscontext->id, 'totara_core', 'testarea', 3, '/', 'test.jpg'));
        $this->assertTrue($fs->file_exists($syscontext->id, 'totara_core', 'testarea', 3, '/', 'test2.jpg'));
    }

    public function test_frozen() {
        // TODO TL-9422: test frozen files.
    }

    /**
     * @return array
     */
    private function get_empty_json_submission(): array {
        return [
            'text' => json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => paragraph::get_type(),
                        // no content key here - mimicking an enter being pressed in the editor.
                    ],
                ],
            ]),
            'format' => FORMAT_JSON_EDITOR,
            'itemid' => null
        ];
    }

    /**
     * @return array
     */
    private function get_valid_json_submission(): array {
        return [
            'text' => json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('This is a test')],
            ]),
            'format' => FORMAT_JSON_EDITOR,
            'itemid' => null
        ];
    }

    /**
     * @return array
     */
    private function get_invalid_json_submission(): array {
        return [
            'text' => json_encode([
                'type' => 'doc',
                // Content is not an array.
                'content' => paragraph::create_json_node_from_text('This is a test'),
            ]),
            'format' => FORMAT_JSON_EDITOR,
            'itemid' => null
        ];
    }
}
