<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core\entity\user;
use core\orm\query\builder;
use mod_approval\form_schema\field_type\application_editor;
use mod_approval\form_schema\form_schema;
use mod_approval\model\application\application;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\approvalform_base;
use mod_approval\model\form\form;
use mod_approval\model\form\form_data;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_type;
use mod_approval\model\workflow\workflow_version;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\form_schema\field_type\application_editor
 */
class mod_approval_application_editor_testcase extends mod_approval_testcase {

    private $user;

    private $application;

    public function setUp(): void {
        $this->setup_filestorage_lib();
        $this->setAdminUser();
        $type = workflow_type::create('test workflow type');

        // Create workflow.
        $form = form::create('simple', 'test form');
        $workflow = workflow::create(
            $type,
            $form,
            'test workflow',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $workflow->activate();
        $workflow->get_default_assignment()->activate();

        // create form version.
        $json_schema = file_get_contents(__DIR__ . "/fixtures/form/test_form.json");
        $form_version = form_version::create($form, 'test form version', $json_schema);
        $form_version->activate();

        // Create workflow version.
        $workflow_version = workflow_version::create($workflow, $form_version);

        // Create workflow stage.
        $workflow_stage = workflow_stage::create($workflow_version, 'stage 1', form_submission::get_enum());
        workflow_stage_formview::create($workflow_stage, 'kia', true, false, 'KIA');
        workflow_stage_formview::create($workflow_stage, 'ora', false, false, 'ORA');
        workflow_stage_formview::create($workflow_stage, 'detailed_description', false, false, 'ORA');
        $workflow_version->activate();

        $this->user = new user($this->getDataGenerator()->create_user());
        $this->setUser($this->user);
        $this->application = application::create($workflow_version, $workflow->get_default_assignment(), $this->user->id);
    }

    protected function tearDown(): void {
        $this->user = null;
        $this->application = null;
    }

    private function setup_filestorage_lib() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->dirroot . '/repository/lib.php');
    }

    public function test_creating_instance_of_application_editor() {
        // test by constructor.
        $application_interactor = $this->application->get_interactor($this->user->id);
        $application_editor_by_constructor = new application_editor($application_interactor);
        $this->assertInstanceOf(application_editor::class, $application_editor_by_constructor);

        // test creating by application id.
        $application_editor_by_id = application_editor::by_application_id($this->application->id, $this->user);
        $this->assertInstanceOf(application_editor::class, $application_editor_by_id);
    }

    public function test_adjust_editor_fields_in_form_schema() {
        $plugin = approvalform_base::from_plugin_name($this->application->form_version->form->plugin_name);
        $form_schema = form_schema::from_form_version($this->application->form_version);

        foreach ($form_schema->get_fields_of_type(application_editor::FIELD_TYPE) as $editor_field) {
            $this->assertNull($editor_field->meta);
        }
        $application_interactor = $this->application->get_interactor($this->user->id);
        $plugin->adjust_form_schema_for_application($application_interactor, $form_schema);

        // meta values are set after adjusting form schema for application.
        foreach ($form_schema->get_fields_of_type(application_editor::FIELD_TYPE) as $editor_field) {
            $this->assertNotNull($editor_field->meta);
            $expected_meta = [
                'usageIdentifier' => [
                    'instanceId' => $this->application->id,
                    'component' => application_editor::FILE_COMPONENT,
                    'area' => application_editor::FILE_AREA,
                    'context' => $this->application->context->id,
                ],
                'variant' => application_editor::VARIANT,
                'extraExtensions' => ['attachment'],
            ];

            $this->assertEqualsCanonicalizing($expected_meta['usageIdentifier'], $editor_field->meta['usageIdentifier']);
            $this->assertEqualsCanonicalizing($expected_meta['variant'], $editor_field->meta['variant']);
            $this->assertEqualsCanonicalizing($expected_meta['extraExtensions'], $editor_field->meta['extraExtensions']);
            $this->assertArrayHasKey('fileItemId', $editor_field->meta);
        }
    }

    /**
     * @covers ::serve_file
     */
    public function test_serve_file() {
        $this->markTestSkipped('Covered by behat');
    }

    /**
     * @covers ::set_value_formats
     */
    public function test_set_value_formats() {
        $application_interactor = $this->application->get_interactor($this->user->id);
        $application_editor = new application_editor($application_interactor);

        // test null value
        $value_formats = $application_editor->set_value_formats(null);
        $this->assertEquals(
            [
                'html' => null,
                'editor' => null,
                'plain' => null,
            ],
            $value_formats
        );

        // test empty value
        $value_formats = $application_editor->set_value_formats('');
        $this->assertEquals(
            [
                'html' => null,
                'editor' => null,
                'plain' => null,
            ],
            $value_formats
        );

        // test value with @@PLUGINFILE@@
        $field_value = $this->generate_content(FORMAT_HTML);
        $field_value = $application_editor->move_files_to_application_area(json_encode($field_value));
        $this->assertStringContainsString('@@PLUGINFILE@@', $field_value);

        $value_formats = $application_editor->set_value_formats($field_value);

        $formats = array_keys($value_formats);
        $expected_formats = ['html', 'editor', 'plain'];
        $this->assertEqualsCanonicalizing($expected_formats, $formats);

        $this->assertStringNotContainsString('@@PLUGINFILE@@', $value_formats['editor']);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $value_formats['html']);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $value_formats['plain']);
    }

    /**
     * Test files are copied when an application is cloned.
     */
    public function test_copy_files_when_cloning() {
        $response = json_encode([
            'detailed_description' => json_encode($this->generate_content(FORMAT_HTML))
        ]);
        application_submission::create_or_update($this->application, $this->user->id, form_data::from_json($response));

        $cloned_application = $this->application->clone($this->user->id);
        $file_storage = get_file_storage();

        // Get files for application.
        $source_application_files = $file_storage->get_area_files(
            $this->application->context->id,
            application_editor::FILE_COMPONENT,
            application_editor::FILE_AREA,
            $this->application->id
        );
        // Check source application files still exist.
        $this->assertNotEmpty($source_application_files);
        $source_file_names = array_map(function ($file) {
            return $file->get_filename();
        }, $source_application_files);

        $cloned_application_files = $file_storage->get_area_files(
            $cloned_application->context->id,
            application_editor::FILE_COMPONENT,
            application_editor::FILE_AREA,
            $cloned_application->id
        );
        $cloned_file_names = array_map(function ($file) {
            return $file->get_filename();
        }, $cloned_application_files);
        $this->assertNotEmpty($cloned_application_files);
        $this->assertEqualsCanonicalizing($source_file_names, $cloned_file_names);
    }

    /**
     * Test file paths are masked when an application is submitted.
     */
    public function test_file_urls_are_masked_when_submitting() {
        $response = json_encode([
            'detailed_description' => json_encode($this->generate_content(FORMAT_HTML))
        ]);
        $submission = application_submission::create_or_update(
            $this->application,
            $this->user->id,
            form_data::from_json($response)
        );
        $this->assertStringContainsString('@@PLUGINFILE@@', $submission->form_data);
    }

    /**
     * @covers ::move_files_to_application_area
     */
    public function test_move_draft_files_to_file_area() {
        $this->test_move_draft_files_without_draft_id();
        $this->test_move_draft_files_without_content();
        $this->test_files_in_content_is_rewritten();
    }

    /**
     * Test nothing is done if draft id is not provided.
     */
    private function test_move_draft_files_without_draft_id() {
        $application_editor = new application_editor($this->application->get_interactor($this->user->id));
        $value = json_encode([
            'format' => FORMAT_HTML,
            'content' => "<h1>Hello world</h1>"
        ]);
        $result = $application_editor->move_files_to_application_area($value);
        $this->assertEquals($value, $result);
    }

    /**
     * Test exception is thrown if content is not provided.
     */
    private function test_move_draft_files_without_content() {
        $application_editor = new application_editor($this->application->get_interactor($this->user->id));
        $value = json_encode([
            'format' => FORMAT_HTML,
            'draft_id' => 47,
        ]);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('invalid editor content');
        $application_editor->move_files_to_application_area($value);
    }

    /**
     * Test file url in content is rewritten.
     */
    private function test_files_in_content_is_rewritten() {
        $application_editor = new application_editor($this->application->get_interactor($this->user->id));
        $value = $this->generate_content(FORMAT_HTML);
        $moved_content = $application_editor->move_files_to_application_area(json_encode($value));
        $moved_content = json_decode($moved_content, true);

        // tests it removes the draft_id before saving.
        $this->assertArrayNotHasKey('draft_id', $moved_content);
        $this->assertEquals($value['format'], $moved_content['format']);
        $this->assertStringNotContainsString($value['draft_id'], $moved_content['content']);
        $this->assertStringContainsString('@@PLUGINFILE@@', $moved_content['content']);
    }

    /**
     * Generate content to be processed.
     *
     * @param string $format
     * @return array
     */
    private function generate_content(string $format, $text = 'My content'): array {
        $draft_id = $this->generate_draft_id();
        $file = $this->create_file($draft_id);

        // We could extend testing with other format contents. Out of scope for now.
        $content = "<h3>$text</h3><img src='$file' alt='my alt'/>";

        return [
            'format' => $format,
            'draft_id' => $draft_id,
            'content' => $content,
        ];
    }

    /**
     * Creates a file for draft id.
     *
     * @param int $draft_id
     * @return string
     */
    private function create_file(int $draft_id): string {
        $stored_file = get_file_storage()->create_file_from_string([
            'contextid' => context_user::instance($this->user->id)->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draft_id,
            'filepath'  => '/',
            'filename'  => 'hello_world.txt',
        ],'hello world');

        return moodle_url::make_draftfile_url(
            $draft_id,
            $stored_file->get_filepath(),
            $stored_file->get_filename()
        )->out(false);
    }

    /**
     * Generate a draft id.
     *
     * @return int
     */
    private function generate_draft_id(): int {
        $draft_id = 0;

        file_prepare_draft_area(
            $draft_id,
            $this->application->context->id,
            application_editor::FILE_COMPONENT,
            application_editor::FILE_AREA,
            $this->application->id
        );

        return $draft_id;
    }

    /**
     * @covers ::get_editor_meta
     */
    public function test_get_editor_meta_with_upload_capabilities() {
        $application_editor = new application_editor($this->application->get_interactor($this->user->id));
        $editor_field_meta = $application_editor->get_editor_meta();
        $expected_meta = [
            'usageIdentifier' => [
                'instanceId' => $this->application->id,
                'component' => application_editor::FILE_COMPONENT,
                'area' => application_editor::FILE_AREA,
                'context' => $this->application->context->id,
            ],
            'variant' => application_editor::VARIANT,
            'extraExtensions' => ['attachment'],
        ];

        $this->assertEqualsCanonicalizing($expected_meta['usageIdentifier'], $editor_field_meta['usageIdentifier']);
        $this->assertEqualsCanonicalizing($expected_meta['variant'], $editor_field_meta['variant']);
        $this->assertEqualsCanonicalizing($expected_meta['extraExtensions'], $editor_field_meta['extraExtensions']);
        $this->assertArrayHasKey('fileItemId', $editor_field_meta);
    }

    public function test_get_editor_meta_without_upload_capabilities() {
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        $application_context_id = $this->application->get_context()->id;
        $application_interactor = $this->application->get_interactor($this->user->id);

        // The user is both owner and applicant, so disable both capabilities.
        assign_capability(
            "mod/approval:attach_file_to_application_applicant",
            CAP_PREVENT,
            $user_role->id,
            $application_context_id,
            true
        );
        assign_capability(
            "mod/approval:attach_file_to_application_owner",
            CAP_PREVENT,
            $user_role->id,
            $application_context_id,
            true
        );

        $application_editor = new application_editor($application_interactor);
        $editor_field_meta = $application_editor->get_editor_meta();
        $expected_meta = [
            'usageIdentifier' => [
                'instanceId' => $this->application->id,
                'component' => application_editor::FILE_COMPONENT,
                'area' => application_editor::FILE_AREA,
                'context' => $this->application->context->id,
            ],
            'variant' => application_editor::VARIANT,
            'extraExtensions' => [],
        ];

        $this->assertEqualsCanonicalizing($expected_meta['usageIdentifier'], $editor_field_meta['usageIdentifier']);
        $this->assertEqualsCanonicalizing($expected_meta['variant'], $editor_field_meta['variant']);
        $this->assertEqualsCanonicalizing($expected_meta['extraExtensions'], $editor_field_meta['extraExtensions']);
        $this->assertArrayNotHasKey('fileItemId', $editor_field_meta);
    }
}
