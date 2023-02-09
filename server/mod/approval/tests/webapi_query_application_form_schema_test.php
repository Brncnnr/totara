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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

use core\entity\tenant;
use core\entity\user as user_entity;
use core\orm\query\builder;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_stage_formview as workflow_stage_formview_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\application\application;
use mod_approval\model\application\application_submission;
use mod_approval\model\application\application_state;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form_data;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\formview_generator_object;
use mod_approval\testing\generator as mod_approval_generator;
use mod_approval\testing\workflow_generator_object;
use totara_tenant\testing\generator as totara_tenant_generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\query\application_form_schema
 */
class mod_approval_webapi_query_application_form_schema_testcase extends mod_approval_testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private $query = 'mod_approval_application_form_schema';

    public function setUp(): void {
        $this->setAdminUser();
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Gets the approval workflow generator instance
     *
     * @return mod_approval_generator
     */
    protected function generator(): mod_approval_generator {
        return mod_approval_generator::instance();
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_without_login() {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user = new user_entity($user1->id);
        $application = $this->create_application($workflow, $assignment, $user);
        $args = ['input' => ['application_id' => $application->id]];

        $this->setUser(0);
        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('You are not logged in');
        $this->resolve_graphql_query($this->query, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_as_guest() {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user = new user_entity($user1->id);
        $application = $this->create_application($workflow, $assignment, $user);
        $args = ['input' => ['application_id' => $application->id]];

        $this->setGuestUser();
        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Must be an authenticated user');
        $this->resolve_graphql_query($this->query, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_without_capability() {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user = new user_entity($user1->id);
        $application = $this->create_application($workflow, $assignment, $user);
        $args = ['input' => ['application_id' => $application->id]];

        // Authenticated users have the capability to submit their own applications by default, so remove it.
        $roleid = builder::table('role')->where('shortname', 'user')->one(true)->id;
        assign_capability('mod/approval:view_application_applicant', CAP_PREVENT, $roleid, $application->get_context(), true);
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage('Cannot access application');
        $this->resolve_graphql_query($this->query, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_as_admin() {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user = new user_entity($user1->id);
        $application = $this->create_application($workflow, $assignment, $user);
        $args = ['input' => ['application_id' => $application->id]];

        // Mark it not draft. We're not doing it properly here, but it's good enough for the test.
        $application->set_current_state(new application_state($application->current_state->get_stage_id()));

        $this->setAdminUser();
        $result = $this->resolve_graphql_query($this->query, $args);
        $result_dec = json_decode($result->form_schema, true);
        $this->assertEquals('Test Form', $result_dec['title'], $result->form_schema);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_as_user() {
        $user1 = $this->getDataGenerator()->create_user();
        $args = $this->update_formview($user1);
        $this->setUser($user1);

        $result = $this->resolve_graphql_query($this->query, $args);
        $form_schema = json_decode($result->form_schema, true);
        $expected = [
            [
                'key' => "agency_code",
                'line' => "A",
                'label' => "Agency code",
                'instruction' => null,
                'help' => null,
                'help_html' => null,
                'type' => "text",
                'default' => '25',
                'required' => false,
                'disabled' => false,
                'meta' => null,
                'validations' => null,
                'hidden' => false,
                'conditional' => null,
                'attrs' => null,
                'rules' => null,
            ]
        ];
        $this->assertEquals($expected, $form_schema['fields'], $result->form_schema);
        $expected = [
            [
                "key" => "applicant_name",
                "line" => "1",
                "label" => "Applicant's Name",
                "instruction" => "Last, First, Middle Initial",
                'help' => null,
                'help_html' => null,
                "type" => "fullname",
                'default' => "Gordon Freeman",
                'required' => true,
                'disabled' => true,
                'meta' => null,
                'validations' => null,
                'hidden' => false,
                'conditional' => null,
                "attrs" => ['format' => "last,first,middle-initial"],
                'rules' => null,
            ]
        ];

        $this->assertCount(1, $form_schema['sections'], $result->form_schema);
        $this->assertEquals($expected, $form_schema['sections'][0]['fields'], $result->form_schema);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_foreign_tenant() {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        /** @var totara_tenant_generator $tengen */
        $tengen = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tengen->enable_tenants();

        $ten1 = new tenant($tengen->create_tenant());
        $ten2 = new tenant($tengen->create_tenant());

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $ten1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $ten2->id]);
        $this->setUser($user1);
        $user = new user_entity($user1->id);
        $application = $this->create_application($workflow, $assignment, $user);
        $args = ['input' => ['application_id' => $application->id]];

        $this->setUser($user2);

        try {
            $this->resolve_graphql_query($this->query, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot access application', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_with_invalid_parameter() {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user = new user_entity($user1->id);
        $this->create_application($workflow, $assignment, $user);
        $args = ['input' => ['application_id' => 25]];

        try {
            $this->resolve_graphql_query($this->query, $args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Invalid assignment', $ex->getMessage());
        }
    }

    public function test_query_as_user_full_test() {
        $user1 = $this->getDataGenerator()->create_user();
        $args = $this->update_formview($user1);
        $this->setUser($user1);

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $this->assertNotEmpty($result[0]['form_schema']);

        $result = json_decode($result[0]['form_schema'], true);
        $expected = [
            [
                'key' => "agency_code",
                'line' => "A",
                'label' => "Agency code",
                'instruction' => null,
                'help' => null,
                'help_html' => null,
                'type' => "text",
                'default' => '25',
                'required' => false,
                'disabled' => false,
                'meta' => null,
                'validations' => null,
                'hidden' => false,
                'conditional' => false,
                'attrs' => null,
                'rules' => null,
            ]
        ];
        $this->assertEquals($expected, $result['fields']);

        $expected = [
            [
                "key" => "applicant_name",
                "line" => "1",
                "label" => "Applicant's Name",
                "instruction" => "Last, First, Middle Initial",
                'help' => null,
                'help_html' => null,
                "type" => "fullname",
                'default' => "Gordon Freeman",
                'required' => true,
                'disabled' => true,
                'meta' => null,
                'validations' => null,
                'hidden' => false,
                'conditional' => false,
                'attrs' => ['format' => "last,first,middle-initial"],
                'rules' => null,
            ]
        ];
        $this->assertEquals($expected, $result['sections'][0]['fields']);

        $this->assertCount(1, $result['sections']);
    }

    public function test_query_with_invalid_parameter_full_test() {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user = new user_entity($user1->id);
        $this->create_application($workflow, $assignment, $user);
        $args = ['input' => ['application_id' => 25]];

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid assignment');
    }

    public function test_query_without_login_and_as_guest() {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user = new user_entity($user1->id);
        $application = $this->create_application($workflow, $assignment, $user);
        $args = ['input' => ['application_id' => $application->id]];

        $this->setUser(0);
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_failed($result, 'You are not logged in');

        $this->setGuestUser();
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_failed($result, 'Must be an authenticated user');
    }

    public function test_query_with_empty_formviews() {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->update_formview($user1);
        $this->setUser($user1);
        $user = new user_entity($user1->id);
        $application = $this->create_application($workflow, $assignment, $user);
        $args = ['input' => ['application_id' => $application->id]];

        workflow_stage_formview_entity::repository()
            ->where('workflow_stage_id', $application->current_stage->id)
            ->delete();

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $this->assertNotEmpty($result[0]['form_schema']);
        $schema = json_decode($result[0]['form_schema']);
        $this->assertEquals('Test Form', $schema->title);
        $this->assertEmpty($schema->fields);
    }

    /**
     * Update formview_entity for testing purpose
     *
     * @param $user
     * @return array
     * @throws coding_exception
     */
    private function update_formview($user): array {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $this->setUser($user);
        $user_entity = new user_entity($user->id);
        $application = $this->create_application($workflow, $assignment, $user_entity);
        $args = ['input' => ['application_id' => $application->id]];
        $current_stage = $application->current_stage;
        workflow_version_entity::repository()->where('id', $application->workflow_version_id)
            ->update([
                'status' => status::DRAFT
            ]);
        $workflow_version = $application->workflow_version->refresh();
        $this->erase_formviews_from_stages($workflow_version);

        /** @var mod_approval\model\workflow\workflow_stage_formview $formview */

        $formview_go = new formview_generator_object('agency_code', $current_stage->id);
        $formview_go->default_value = '25';
        $this->generator()->create_formview($formview_go);

        // Create new formview record for field inside section
        $formview_go = new formview_generator_object('applicant_name', $current_stage->id);
        $formview_go->default_value = "Gordon Freeman";
        $formview_go->required = true;
        $formview_go->disabled = true;
        $this->generator()->create_formview($formview_go);

        return $args;
    }

    /**
     * @param application $application
     * @param user_entity $user
     * @param workflow_stage $stage
     * @param array $form_data
     * @return application_submission
     */
    private function create_submission(
        application $application,
        user_entity $user,
        workflow_stage $stage,
        array $form_data
    ): application_submission {
        $form_data = form_data::from_json(json_encode($form_data));
        $entity = $this->generator()->create_application_submission($application->id, $user->id, $stage->id, $form_data);
        // supersede all others submissions
        builder::table('approval_application_submission')
            ->where('application_id', $application->id)
            ->where('workflow_stage_id', $stage->id)
            ->where('id', '!=', $entity->id)
            ->update(['superseded' => 1]);
        return application_submission::load_by_entity($entity);
    }

    public function test_query_full_or_not(): void {
        $form_version = $this->generator()->create_form_and_version('simple', 'test form', __DIR__ . '/fixtures/schema/test1.json');
        $version_entity = $this->generator()->create_workflow_and_version(
            new workflow_generator_object(
                $this->generator()->create_workflow_type('test workflow type')->id,
                $form_version->form_id,
                $form_version->id
            )
        );
        $version_entity->status = status::DRAFT;
        $version_entity->update();
        $workflow_version = workflow_version::load_by_entity($version_entity);

        $stage1 = workflow_stage::create($workflow_version, 'First stage', form_submission::get_enum());
        $stage2 = workflow_stage::create($workflow_version, 'Second stage', approvals::get_enum());
        $stage3 = workflow_stage::create($workflow_version, 'Third stage', approvals::get_enum());
        $this->erase_formviews_from_stages($workflow_version);

        workflow_stage_formview::create($stage1, 'gender', false, false, '?');
        workflow_stage_formview::create($stage1, 'food', true, false, null);
        workflow_stage_formview::create($stage1, 'shirt', true, false, null);

        $stage2->add_approval_level('First level');
        workflow_stage_formview::create($stage2, 'gender', false, false, '?');
        workflow_stage_formview::create($stage2, 'food', true, false, null);
        workflow_stage_formview::create($stage2, 'shirt', true, false, null);

        $stage3->add_approval_level('Second level');
        workflow_stage_formview::create($stage3, 'gender', false, false, '?');
        workflow_stage_formview::create($stage3, 'food', true, false, null);

        workflow_stage::create($workflow_version, 'End', finished::get_enum());

        $workflow_version->workflow->publish($workflow_version);
        $workflow_version->refresh(true);

        $user = new user_entity($this->getDataGenerator()->create_user());
        $approver = new user_entity($this->getDataGenerator()->create_user());
        $assignment = assignment::create(
            $workflow_version->workflow->course_id,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            true
        )->activate();
        $application = application::create($workflow_version, $assignment, $user->id);
        $this->create_submission(
            $application,
            $user,
            $stage1,
            [
                'gender' => 'M',
                'food' => 'Avocado',
                'shirt' => 'L'
            ]
        );
        $stage_2_state = $stage2->state_manager->get_initial_state();
        $application->set_current_state($stage_2_state);

        $prettify_result = function (string $form_schema, string $form_data): array {
            $form_schema = json_decode($form_schema, true);
            $form_data = json_decode($form_data, true);
            $result = ['fields' => [], 'sections' => []];
            foreach ($form_schema['fields'] ?? [] as $field) {
                $result['fields'][$field['key']] = $form_data[$field['key']];
            }
            foreach ($form_schema['sections'] ?? [] as $section) {
                foreach ($section['fields'] ?? [] as $field) {
                    $result['sections'][$section['key']][$field['key']] = $form_data[$field['key']];
                }
            }
            return $result;
        };

        $args = ['input' => ['application_id' => $application->id]];
        $stage1_state = $stage1->state_manager->get_initial_state();
        $application->set_current_state($stage1_state);

        $this->create_submission($application, $user, $stage1, ['gender' => 'M', 'food' => 'Avocado', 'shirt' => 'L']);
        $result = $this->resolve_graphql_query($this->query, $args);
        $expected = ['fields' => ['gender' => 'M'], 'sections' => ['A' => ['food' => 'Avocado'], 'B' => ['shirt' => 'L']]];
        $this->assertEquals($expected, $prettify_result($result->form_schema, $result->form_data));

        $stage_3_state = $stage3->state_manager->get_initial_state();
        $application->set_current_state($stage_3_state);

        $this->create_submission($application, $approver, $stage3, ['gender' => 'F', 'food' => 'Banana']);
        $result = $this->resolve_graphql_query($this->query, $args);
        // NOTE: default values are process in the front end
        // Return full schema as admin has special capability
        $expected = ['fields' => ['gender' => 'F'], 'sections' => ['A' => ['food' => 'Banana'], 'B' => ['shirt' => 'L']]];
        $this->assertEquals($expected, $prettify_result($result->form_schema, $result->form_data));

        $this->setUser($user);
        $result = $this->resolve_graphql_query($this->query, $args);
        // NOTE: default values are process in the front end
        $expected = ['fields' => ['gender' => 'F'], 'sections' => ['A' => ['food' => 'Banana']]];
        $this->assertEquals($expected, $prettify_result($result->form_schema, $result->form_data));

        $args = ['input' => ['application_id' => $application->id], 'full_schema' => true];
        $result = $this->resolve_graphql_query($this->query, $args);
        $expected = ['fields' => ['gender' => 'F'], 'sections' => ['A' => ['food' => 'Banana'], 'B' => ['shirt' => 'L']]];
        $this->assertEquals($expected, $prettify_result($result->form_schema, $result->form_data));

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);
        $this->assertEquals($expected, $prettify_result($result[0]['form_schema'], $result[0]['form_data']));
    }
}
