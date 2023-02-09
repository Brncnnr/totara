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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core\orm\entity\model;
use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use mod_approval\entity\application\application as application_entity;
use mod_approval\entity\application\application_action as application_action_entity;
use mod_approval\entity\application\application_submission as application_submission_entity;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\entity\form\form as form_entity;
use mod_approval\entity\form\form_version as form_version_entity;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\entity\workflow\workflow_stage_formview as workflow_stage_formview_entity;
use mod_approval\entity\workflow\workflow_stage_interaction;
use mod_approval\entity\workflow\workflow_stage_interaction_transition;
use mod_approval\entity\workflow\workflow_type as workflow_type_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\approve as approve_action;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\reject as reject_action;
use mod_approval\model\application\action\withdraw_before_submission as withdraw_before_submission_action;
use mod_approval\model\application\action\withdraw_in_approvals as withdraw_in_approvals_action;
use mod_approval\model\application\application_state;
use mod_approval\model\assignment\approver_type\user as approver_user;
use mod_approval\model\assignment\approver_type\relationship as approver_relationship;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form_data;
use mod_approval\model\form\form_version as form_version_model;
use mod_approval\model\status;
use mod_approval\model\workflow\interaction\transition\next;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\application_generator_object;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\generator;
use mod_approval\testing\workflow_generator_object;
use PHPUnit\Framework\MockObject\MockObject;
use totara_core\relationship\relationship;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_job\job_assignment;

/**
 * @group approval_workflow
 */
class mod_approval_generator_behat_testcase extends testcase {
    /** @var generator */
    private $gen;

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->gen = generator::instance();
    }

    public function tearDown(): void {
        $this->gen = null;
        parent::tearDown();
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    private function invoke(string $method, ...$args) {
        $method = new ReflectionMethod(generator::class, $method);
        $method->setAccessible(true);
        return $method->invokeArgs($this->gen, $args);
    }

    /**
     * @param array $add_methods
     * @param array $record
     * @return MockObject
     */
    private function create_mock_model(array $add_methods = [], array $record = []): MockObject {
        $record = array_merge(['id' => 42], $record);
        $entity = new user($record, false, true); // victimise the user entity
        $builder = $this->getMockBuilder(model::class)
            ->disableOriginalClone()
            ->disableProxyingToOriginalMethods()
            ->setConstructorArgs([$entity])
            ->onlyMethods(['get_entity_class']);
        if (!empty($add_methods)) {
            $builder->addMethods($add_methods);
        }
        $model = $builder->getMock();
        $model->method('get_entity_class')->willReturn(user::class);
        return $model;
    }

    /**
     * @covers mod_approval\testing\generator::resolve_json
     */
    public function test_resolve_json_default(): void {
        $record = ['kia' => '', 'ora' => 'koutou'];
        $default = '{"123":"456!"}';

        $result = $this->invoke('resolve_json', $record, 'katoa', 'form', $default);
        $this->assertEquals($default, $result);
        $result = $this->invoke('resolve_json', $record, 'kia', 'form', $default);
        $this->assertEquals($default, $result);
    }

    /**
     * @covers mod_approval\testing\generator::resolve_json
     */
    public function test_resolve_json_raw(): void {
        $json = '{"123":"456!"}';
        $result = $this->invoke('resolve_json', ['test' => $json], 'test', 'form', '{}');
        $this->assertEquals($json, $result);

        try {
            $this->invoke('resolve_json', ['test' => '{yes}'], 'test', 'form', '{}');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Invalid JSON', $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_json
     */
    public function test_resolve_json_file(): void {
        $result = $this->invoke('resolve_json', ['test' => 'small'], 'test', 'form', '{}');
        $result = rtrim($result, "\r\n");
        $this->assertEquals('{"sma":"LL"}', $result);

        try {
            $this->invoke('resolve_json', ['test' => 'dr.json'], 'test', 'form', '{}');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Invalid json file', $ex->getMessage());
        }

        try {
            $this->invoke('resolve_json', ['test' => '"a"'], 'test', 'form', '{}');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Invalid json file', $ex->getMessage());
        }

        try {
            $this->invoke('resolve_json', ['test' => '["a"]'], 'test', 'form', '{}');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Invalid json file', $ex->getMessage());
        }

        try {
            $this->invoke('resolve_json', ['test' => 'he_who_must_not_exist'], 'test', 'form', '{}');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('File not found', $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_json
     */
    public function test_resolve_json_plugin(): void {
        $result = $this->invoke('resolve_json', ['test' => 'simple:small'], 'test', 'form', '{}');
        $result = rtrim($result, "\r\n");
        $this->assertEquals('{"SM":"all"}', $result);
        try {
            $this->invoke('resolve_json', ['test' => 'he_who_must_not_exist:what'], 'test', 'form', '{}');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('File not found', $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_bool
     */
    public function test_resolve_bool_falsy(): void {
        $this->assertTrue($this->invoke('resolve_bool', ['test' => 'tRue'], 'test', false));
        $this->assertTrue($this->invoke('resolve_bool', ['test' => 'YES'], 'test', false));
        $this->assertTrue($this->invoke('resolve_bool', ['test' => '1'], 'test', false));
        $this->assertFalse($this->invoke('resolve_bool', ['test' => 'false'], 'test', false));
        $this->assertFalse($this->invoke('resolve_bool', ['test' => 'no'], 'test', false));
        $this->assertFalse($this->invoke('resolve_bool', ['test' => '0'], 'test', false));
        $this->assertFalse($this->invoke('resolve_bool', ['test' => 'oops'], 'test', false));
        $this->assertFalse($this->invoke('resolve_bool', ['test' => ''], 'test', false));
        $this->assertFalse($this->invoke('resolve_bool', [], 'test', false));
    }

    /**
     * @covers mod_approval\testing\generator::resolve_bool
     */
    public function test_resolve_bool_truthy(): void {
        $this->assertFalse($this->invoke('resolve_bool', ['test' => 'falSe'], 'test', true));
        $this->assertFalse($this->invoke('resolve_bool', ['test' => 'NO'], 'test', true));
        $this->assertFalse($this->invoke('resolve_bool', ['test' => '0'], 'test', true));
        $this->assertTrue($this->invoke('resolve_bool', ['test' => 'true'], 'test', true));
        $this->assertTrue($this->invoke('resolve_bool', ['test' => 'yes'], 'test', true));
        $this->assertTrue($this->invoke('resolve_bool', ['test' => '1'], 'test', true));
        $this->assertTrue($this->invoke('resolve_bool', ['test' => 'oops'], 'test', true));
        $this->assertTrue($this->invoke('resolve_bool', ['test' => ''], 'test', true));
        $this->assertTrue($this->invoke('resolve_bool', [], 'test', true));
    }

    /**
     * @covers mod_approval\testing\generator::resolve_sortorder
     */
    public function test_resolve_sortorder(): void {
        $type = $this->gen->create_workflow_type('test');
        $formver = $this->gen->create_form_and_version();
        $workver = $this->gen->create_workflow_and_version(
            new workflow_generator_object($type->id, $formver->form_id, $formver->id, status::DRAFT)
        );

        $builder = builder::table('approval_workflow_stage', 'aws');
        $result = $this->invoke('resolve_sortorder', $builder);
        $this->assertEquals(1, $result);

        $this->gen->create_workflow_stage($workver->id, 'stage1', form_submission::get_enum());
        $builder = builder::table('approval_workflow_stage', 'aws');
        $result = $this->invoke('resolve_sortorder', $builder);
        $this->assertEquals(2, $result);
    }

    /**
     * @covers mod_approval\testing\generator::resolve_user
     */
    public function test_resolve_user(): void {
        $bob = $this->getDataGenerator()->create_user(['username' => 'bob']);
        $rob = $this->getDataGenerator()->create_user(['username' => 'rob']);
        $record = ['test' => 'bob'];

        $result = $this->invoke('resolve_user', $record, 'test', null);
        $this->assertEquals($bob->id, $result->id);
        $result = $this->invoke('resolve_user', $record, 'oops', new user($rob));
        $this->assertEquals($rob->id, $result->id);
        $result = $this->invoke('resolve_user', $record, 'test', new user($rob));
        $this->assertEquals($bob->id, $result->id);

        try {
            $this->invoke('resolve_user', $record, 'oops', null);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Missing field: oops', $ex->getMessage());
        }
        try {
            $record = ['test' => 'he_who_must_not_exist'];
            $this->invoke('resolve_user', $record, 'test', null);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_job_assignment
     */
    public function test_resolve_job_assignment(): void {
        $record = ['test' => 'jajaja1'];
        $ja1 = job_assignment::create(['userid' => 2, 'idnumber' => 'jajaja1']);
        $ja2 = job_assignment::create(['userid' => 2, 'idnumber' => 'jajaja2']);
        $ja2_entity = new job_assignment_entity($ja2->id);

        $result = $this->invoke('resolve_job_assignment', $record, 'test', null);
        $this->assertEquals($ja1->id, $result->id);
        $result = $this->invoke('resolve_job_assignment', $record, 'oops', $ja2_entity);
        $this->assertEquals($ja2->id, $result->id);
        $result = $this->invoke('resolve_job_assignment', $record, 'oops', null);
        $this->assertNull($result);

        try {
            $record = ['test' => 'he_who_must_not_exist'];
            $this->invoke('resolve_user', $record, 'test', null);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_workflow
     */
    public function test_resolve_workflow(): void {
        $type = $this->gen->create_workflow_type('test');
        $formver = $this->gen->create_form_and_version();
        $obj = new workflow_generator_object($type->id, $formver->form_id, $formver->id);
        $obj->id_number = 'existing_id_number';
        $work_id_number_id = $this->gen->create_workflow_and_version($obj)->workflow_id;
        $obj = new workflow_generator_object($type->id, $formver->form_id, $formver->id);
        $obj->name = 'existing_name';
        $work_name_id = $this->gen->create_workflow_and_version($obj)->workflow_id;

        $result = $this->invoke('resolve_workflow', ['workflow' => 'existing_id_number']);
        $this->assertEquals($work_id_number_id, $result->id);
        $result = $this->invoke('resolve_workflow', ['workflow' => 'existing_name']);
        $this->assertEquals($work_name_id, $result->id);

        try {
            $this->invoke('resolve_workflow', ['workflow' => 'non-existent']);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_workflow_stage
     */
    public function test_resolve_workflow_stage(): void {
        $type = $this->gen->create_workflow_type('test');
        $formver = $this->gen->create_form_and_version();
        $workver = $this->gen->create_workflow_and_version(
            new workflow_generator_object($type->id, $formver->form_id, $formver->id, status::DRAFT)
        );
        $stage = $this->gen->create_workflow_stage($workver->id, 'existing stage', form_submission::get_enum());

        $result = $this->invoke('resolve_workflow_stage', ['workflow_stage' => 'existing stage']);
        $this->assertEquals($stage->id, $result->id);

        try {
            $this->invoke('resolve_workflow_stage', ['workflow_stage' => 'non-existent']);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }

        $obj = new workflow_generator_object($type->id, $formver->form_id, $formver->id, status::DRAFT);
        $obj->id_number = 'two';
        $obj->name = 'two';
        $workver = $this->gen->create_workflow_and_version($obj);
        $this->gen->create_workflow_stage($workver->id, 'existing stage', form_submission::get_enum());
        try {
            $this->invoke('resolve_workflow_stage', ['workflow_stage' => 'existing stage']);
            $this->fail('dml_exception expected');
        } catch (dml_exception $ex) {
            $this->assertStringContainsString('Multiple records found', $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_form
     */
    public function test_resolve_form(): void {
        $form_id = $this->gen->create_form_and_version('simple', 'existing form')->form_id;

        $result = $this->invoke('resolve_form', ['form' => 'existing form']);
        $this->assertEquals($form_id, $result->id);

        try {
            $this->invoke('resolve_form', ['form' => 'non-existent']);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }

        $form = new form_entity();
        $form->plugin_name = 'dontcare';
        $form->title = 'existing form';
        $form->active = true;
        $form->save();
        try {
            $this->invoke('resolve_form', ['form' => 'existing form']);
            $this->fail('dml_exception expected');
        } catch (dml_exception $ex) {
            $this->assertStringContainsString('Multiple records found', $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_assignment
     */
    public function test_resolve_assignment(): void {
        $type = $this->gen->create_workflow_type('test');
        $formver = $this->gen->create_form_and_version();
        $course_id = $this->gen->create_workflow_and_version(
            new workflow_generator_object($type->id, $formver->form_id, $formver->id)
        )->workflow->course_id;
        $obj = new assignment_generator_object($course_id, assignment_type\cohort::get_code(), $this->getDataGenerator()->create_cohort()->id);
        $obj->id_number = 'existing id_number';
        $ass_id_number_id = $this->gen->create_assignment($obj)->id;

        $result = $this->invoke('resolve_assignment', ['assignment' => 'existing id_number']);
        $this->assertEquals($ass_id_number_id, $result->id);

        try {
            $this->invoke('resolve_assignment', ['assignment' => 'non-existent']);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_assignment_type
     */
    public function test_resolve_assignment_type(): void {
        $gen = totara_hierarchy\testing\generator::instance();
        $org_fw = $gen->create_framework('organisation');
        $org_id_number_id = $gen->create_org(
            ['frameworkid' => $org_fw->id, 'idnumber' => 'existing_org_id_number', 'shortname' => 'dontcare1']
        )->id;
        $org_short_name_id = $gen->create_org(
            ['frameworkid' => $org_fw->id, 'idnumber' => 'dontcare2', 'shortname' => 'existing org shortname']
        )->id;
        $pos_fw = $gen->create_framework('position');
        $pos_id_number_id = $gen->create_pos(
            ['frameworkid' => $pos_fw->id, 'idnumber' => 'existing_pos_id_number', 'shortname' => 'dontcare3']
        )->id;
        $pos_short_name_id = $gen->create_pos(
            ['frameworkid' => $pos_fw->id, 'idnumber' => 'dontcare4', 'shortname' => 'existing pos shortname']
        )->id;
        $gen = $this->getDataGenerator();
        $cohort_id_number_id = $gen->create_cohort(['idnumber' => 'existing_cohort_id_number'])->id;
        $cohort_name_id = $gen->create_cohort(['name' => 'existing cohort name'])->id;

        [$type, $identifier] = $this->invoke(
            'resolve_assignment_type',
            ['type' => 'organisation', 'identifier' => 'existing_org_id_number']
        );
        $this->assertEquals(assignment_type\organisation::get_code(), $type);
        $this->assertEquals($org_id_number_id, $identifier);
        [$type, $identifier] = $this->invoke(
            'resolve_assignment_type',
            ['type' => 'organisation', 'identifier' => 'existing org shortname']
        );
        $this->assertEquals(assignment_type\organisation::get_code(), $type);
        $this->assertEquals($org_short_name_id, $identifier);

        [$type, $identifier] = $this->invoke(
            'resolve_assignment_type',
            ['type' => 'position', 'identifier' => 'existing_pos_id_number']
        );
        $this->assertEquals(assignment_type\position::get_code(), $type);
        $this->assertEquals($pos_id_number_id, $identifier);
        [$type, $identifier] = $this->invoke(
            'resolve_assignment_type',
            ['type' => 'position', 'identifier' => 'existing pos shortname']
        );
        $this->assertEquals(assignment_type\position::get_code(), $type);
        $this->assertEquals($pos_short_name_id, $identifier);

        [$type, $identifier] = $this->invoke(
            'resolve_assignment_type',
            ['type' => 'cohort', 'identifier' => 'existing_cohort_id_number']
        );
        $this->assertEquals(assignment_type\cohort::get_code(), $type);
        $this->assertEquals($cohort_id_number_id, $identifier);
        [$type, $identifier] = $this->invoke(
            'resolve_assignment_type',
            ['type' => 'cohort', 'identifier' => 'existing cohort name']
        );
        $this->assertEquals(assignment_type\cohort::get_code(), $type);
        $this->assertEquals($cohort_name_id, $identifier);

        try {
            $this->invoke('resolve_assignment_type', ['type' => 'invalid', 'identifier' => '?']);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Invalid assignment type', $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_approver_type
     */
    public function test_resolve_approver_type(): void {
        [$type, $identifier] = $this->invoke('resolve_approver_type', ['type' => 'relationship', 'identifier' => 'manager']);
        $this->assertEquals(approver_relationship::TYPE_IDENTIFIER, $type);
        $this->assertEquals(relationship::load_by_idnumber('manager')->id, $identifier);

        $bob = $this->getDataGenerator()->create_user(['username' => 'bob']);
        [$type, $identifier] = $this->invoke('resolve_approver_type', ['type' => 'user', 'identifier' => 'bob']);
        $this->assertEquals(approver_user::TYPE_IDENTIFIER, $type);
        $this->assertEquals($bob->id, $identifier);

        try {
            $this->invoke('resolve_approver_type', ['type' => 'invalid', 'identifier' => '?']);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Invalid approver type', $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_approval_level
     */
    public function test_resolve_approval_level(): void {
        $type = $this->gen->create_workflow_type('test');
        $formver = $this->gen->create_form_and_version();
        $workver = $this->gen->create_workflow_and_version(
            new workflow_generator_object($type->id, $formver->form_id, $formver->id, status::DRAFT)
        );
        $stage1 = $this->gen->create_workflow_stage($workver->id, 'stage 1', form_submission::get_enum());
        $stage2 = $this->gen->create_workflow_stage($workver->id, 'stage 2', form_submission::get_enum());
        $level11 = $this->gen->create_approval_level($stage1->id, 'level 1', 1);
        $level12 = $this->gen->create_approval_level($stage1->id, 'level 2', 2);
        $level22 = $this->gen->create_approval_level($stage2->id, 'level 2', 2);

        $result = $this->invoke('resolve_approval_level', ['approval_level' => 'level 1']);
        $this->assertEquals($level11->id, $result->id);
        $result = $this->invoke('resolve_approval_level', ['approval_level' => 'level 2', 'workflow_stage' => 'stage 1']);
        $this->assertEquals($level12->id, $result->id);
        $result = $this->invoke('resolve_approval_level', ['approval_level' => 'level 2', 'workflow_stage' => 'stage 2']);
        $this->assertEquals($level22->id, $result->id);

        try {
            $this->invoke('resolve_approval_level', ['approval_level' => 'non-existent']);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }

        try {
            $this->invoke('resolve_approval_level', ['approval_level' => 'non-existent', 'workflow_stage' => 'stage 1']);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }

        try {
            $this->invoke('resolve_approval_level', ['approval_level' => 'level 2']);
            $this->fail('dml_exception expected');
        } catch (dml_exception $ex) {
            $this->assertStringContainsString('Multiple records found', $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_application
     */
    public function test_resolve_application(): void {
        $type = $this->gen->create_workflow_type('test');
        $formver = $this->gen->create_form_and_version();
        $workver = $this->gen->create_workflow_and_version(
            new workflow_generator_object($type->id, $formver->form_id, $formver->id, status::DRAFT)
        );
        $this->gen->create_workflow_stage($workver->id, 'Test stage', form_submission::get_enum());
        $ass = $this->gen->create_assignment(
            new assignment_generator_object(
                $workver->workflow->course_id,
                assignment_type\cohort::get_code(),
                $this->getDataGenerator()->create_cohort()->id
            )
        );
        $obj = new application_generator_object($workver->id, $workver->form_version_id, $ass->id);
        $obj->title = 'existing title';
        $app_title_id = $this->gen->create_application($obj)->id;

        $result = $this->invoke('resolve_application', ['application' => 'existing title']);
        $this->assertEquals($app_title_id, $result->id);

        try {
            $this->invoke('resolve_application', ['application' => 'non-existent']);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
    }

    /**
     * @covers mod_approval\testing\generator::resolve_interaction
     */
    public function test_resolve_interaction(): void {
        $type = $this->gen->create_workflow_type('test');
        $formver = $this->gen->create_form_and_version();
        $workver = $this->gen->create_workflow_and_version(
            new workflow_generator_object($type->id, $formver->form_id, $formver->id, status::DRAFT)
        );
        $workflow_stage = $this->gen->create_workflow_stage($workver->id, 'Test stage', form_submission::get_enum());
        $interaction = $this->gen->create_workflow_stage_interaction(
            $workflow_stage->id,
            approve::get_code()
        );

        $result = $this->invoke('resolve_interaction', ['interaction' => $interaction->id]);
        $this->assertEquals($interaction->id, $result->id);

        try {
            $this->invoke('resolve_interaction', ['interaction' => 78]);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
    }

    /**
     * @covers mod_approval\testing\generator::activate_model
     */
    public function test_activate_model(): void {
        $model = $this->create_mock_model(['activate', 'deactivate']);
        $model->expects($this->once())->method('activate');
        $model->expects($this->never())->method('deactivate');
        $this->invoke('activate_model', [], $model);

        $model = $this->create_mock_model(['activate', 'deactivate']);
        $model->expects($this->once())->method('activate');
        $model->expects($this->never())->method('deactivate');
        $this->invoke('activate_model', ['active' => 'true'], $model);

        $model = $this->create_mock_model(['activate', 'deactivate']);
        $model->expects($this->never())->method('activate');
        $model->expects($this->never())->method('deactivate');
        $this->invoke('activate_model', ['active' => 'false'], $model);

        $model = $this->create_mock_model(['activate', 'deactivate'], ['active' => 'boohoo']);
        $model->expects($this->never())->method('activate');
        $model->expects($this->once())->method('deactivate');
        $this->invoke('activate_model', ['active' => 'false'], $model);
    }

    /**
     * @covers mod_approval\testing\generator::set_model_status
     */
    public function test_set_model_status_mock(): void {
        $model = $this->create_mock_model(['activate', 'deactivate']);
        $model->expects($this->once())->method('activate');
        $model->expects($this->never())->method('deactivate');
        $this->invoke('set_model_status', ['active' => 'true'], $model);

        $model = $this->create_mock_model([]);
        try {
            $this->invoke('set_model_status', ['status' => '?', 'active' => '?'], $model);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Cannot use both active and status', $ex->getMessage());
        }

        $model = $this->create_mock_model([]);
        try {
            $this->invoke('set_model_status', ['status' => 'invalid.i.am'], $model);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Invalid status: invalid.i.am', $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::set_model_status
     */
    public function test_set_model_status_real(): void {
        $form = new form_entity();
        $form->plugin_name = 'simple';
        $form->title = 'test';
        $form->active = true;
        $form->save();

        $entity = $this->gen->create_form_version($form->id, '1', '{}', status::DRAFT);
        $model = new form_version_model($entity);
        $this->invoke('set_model_status', ['status' => 'draft'], $model);
        $this->assertEquals(status::DRAFT, $entity->status);

        $entity = $this->gen->create_form_version($form->id, '2', '{}', status::DRAFT);
        $model = new form_version_model($entity);
        $this->invoke('set_model_status', ['status' => 'archived'], $model);
        $this->assertEquals(status::ARCHIVED, $entity->status);
    }

    /**
     * @covers mod_approval\testing\generator::fix_up_form_data
     */
    public function test_fix_up_form_data(): void {
        $form_data = form_data::from_json('{"kia":"## -3 day ## Y-m-d ##","ora":"##today##j F Y##"}');
        $time = strtotime('2021-03-04T05:06:07+0800');
        $result = $this->invoke('fix_up_form_data', $form_data, $time);
        $this->assertEquals('2021-03-01', $result->get_value('kia'));
        $this->assertEquals('4 March 2021', $result->get_value('ora'));
    }

    /**
     * @covers mod_approval\testing\generator::do_work
     */
    public function test_do_work(): void {
        $callback = function ($record) {
            $this->assertEquals(['kia' => 'ora'], $record);
            return 42;
        };
        $result = $this->invoke('do_work', ['kia' => 'ora'], $callback);
        $this->assertSame(42, $result);

        $callback = function ($record) {
            throw new coding_exception('boom');
        };
        try {
            $this->invoke('do_work', ['kia' => 'ora'], $callback);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('boom', $ex->getMessage());
        }

        $callback = function ($record) {
            throw new coding_exception('boom', 'debug me');
        };
        try {
            $this->invoke('do_work', ['kia' => 'ora'], $callback);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('boom', $ex->getMessage());
            $this->assertStringContainsString('(debug me)', $ex->getMessage());
        }
    }

    /**
     * @return array
     */
    public function data_active_provider(): array {
        return [
            'default' => [[], true],
            'active' => [['active' => 'true'], true],
            'inactive' => [['active' => 'false'], false],
        ];
    }

    /**
     * @return array
     */
    public function data_active_status_provider(): array {
        return [
            'default' => [[], status::ACTIVE],
            'draft' => [['active' => 'false'], status::DRAFT],
            'active' => [['active' => 'true'], status::ACTIVE],
        ];
    }

    /**
     * @param array $record
     * @param boolean $expected
     * @covers mod_approval\testing\generator::create_form_for_behat
     * @dataProvider data_active_provider
     */
    public function test_create_form_for_behat(array $record, bool $expected): void {
        $id = $this->gen->create_form_for_behat(array_merge(['title' => 'Test form'], $record));
        $entity = new form_entity($id);
        $this->assertEquals(1, form_entity::repository()->count());
        $this->assertEquals(0, form_version_entity::repository()->count());
        $this->assertEquals('Test form', $entity->title);
        $this->assertEquals($expected, $entity->active);
    }

    /**
     * @param array $record
     * @param integer $expected
     * @covers mod_approval\testing\generator::create_form_version_for_behat
     * @dataProvider data_active_status_provider
     */
    public function test_create_form_version_for_behat(array $record, int $expected): void {
        $form_id = $this->gen->create_form_for_behat(['title' => 'Test form']);
        $id = $this->gen->create_form_version_for_behat(array_merge(['version' => 'one', 'form' => 'Test form'], $record));
        $entity = new form_version_entity($id);
        $this->assertEquals(1, form_version_entity::repository()->count());
        $this->assertEquals('one', $entity->version);
        $this->assertEquals($form_id, $entity->form_id);
        $this->assertEquals($expected, $entity->status);
    }

    /**
     * @param array $record
     * @param boolean $expected
     * @covers mod_approval\testing\generator::create_workflow_type_for_behat
     * @dataProvider data_active_provider
     */
    public function test_create_workflow_type_for_behat(array $record, bool $expected): void {
        $id = $this->gen->create_workflow_type_for_behat(array_merge(['name' => 'Test workflow type'], $record));
        $entity = new workflow_type_entity($id);
        $this->assertEquals(1, workflow_type_entity::repository()->count());
        $this->assertEquals('Test workflow type', $entity->name);
        $this->assertEquals($expected, $entity->active);
    }

    /**
     * @param array $record
     * @param boolean $expected
     * @covers mod_approval\testing\generator::create_workflow_for_behat
     * @dataProvider data_active_provider
     */
    public function test_create_workflow_for_behat(array $record, bool $expected): void {
        $type_id = $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $form_id = $this->gen->create_form_for_behat(['title' => 'Test form 1']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form 1']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $id = $this->gen->create_workflow_for_behat(array_merge(
            [
                'name' => 'Test workflow 1',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form 1',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ],
            $record
        ));
        $entity = new workflow_entity($id);
        $this->assertEquals(1, workflow_entity::repository()->count());
        $this->assertEquals(1, workflow_version_entity::repository()->count());
        $this->assertEquals('Test workflow 1', $entity->name);
        $this->assertEquals('WKF001', $entity->id_number);
        $this->assertEquals('', $entity->description);
        $this->assertEquals($type_id, $entity->workflow_type_id);
        $this->assertEquals($form_id, $entity->form_id);
        $this->assertEquals($expected, $entity->active);

        $this->gen->create_form_for_behat(['title' => 'Test form 2']);
        try {
            $this->gen->create_workflow_for_behat(array_merge(
                [
                    'name' => 'Test workflow 2',
                    'id_number' => 'WKF002',
                    'workflow_type' => 'Test workflow type',
                    'form' => 'Test form 2',
                    'type' => 'cohort',
                    'identifier' => 'AUD001'
                ],
                $record
            ));
            $this->fail('dml_exception expected');
        } catch (dml_exception $ex) {
            $this->assertStringContainsString('Can not find data record in database', $ex->getMessage());
        }
    }

    /**
     * @param array $record
     * @param integer $expected
     * @covers mod_approval\testing\generator::create_workflow_version_for_behat
     * @dataProvider data_active_status_provider
     */
    public function test_create_workflow_version_for_behat(array $record, int $expected): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form 1']);
        $formver_id = $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form 1']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $workflow_id = $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form 1',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );

        $id = $this->gen->create_workflow_version_for_behat(
            array_merge(['workflow' => 'Test workflow', 'form_version' => 'one'], $record)
        );
        $entity = new workflow_version_entity($id);
        $this->assertEquals(2, workflow_version_entity::repository()->count());
        $this->assertEquals($formver_id, $entity->form_version_id);
        $this->assertEquals($workflow_id, $entity->workflow_id);
        $this->assertEquals($expected, $entity->status);

        try {
            $this->gen->create_workflow_version_for_behat(
                array_merge(['workflow' => 'Test workflow', 'form_version' => 'two'], $record)
            );
            $this->fail('dml_exception expected');
        } catch (dml_exception $ex) {
            $this->assertStringContainsString('Can not find data record in database', $ex->getMessage());
        }

        $this->gen->create_form_for_behat(['title' => 'Test form 2']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form 2']);
        try {
            $this->gen->create_workflow_version_for_behat(
                array_merge(['workflow' => 'Test workflow', 'form_version' => 'one'], $record)
            );
            $this->fail('dml_exception expected');
        } catch (dml_exception $ex) {
            $this->assertStringContainsString('Multiple records found', $ex->getMessage());
        }
    }

    /**
     * @param array $record
     * @param bool $expected
     * @covers mod_approval\testing\generator::create_workflow_stage_for_behat
     * @dataProvider data_active_provider
     */
    public function test_create_workflow_stage_for_behat(array $record, bool $expected): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $workver_id = $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $id = $this->gen->create_workflow_stage_for_behat(array_merge(['workflow' => 'WKF001', 'name' => 'Test stage', 'type' => form_submission::get_enum()], $record));
        $entity = new workflow_stage_entity($id);
        $this->assertEquals(1, workflow_stage_entity::repository()->count());
        $this->assertEquals(1, $entity->sortorder);
        $this->assertEquals('Test stage', $entity->name);
        $this->assertEquals($workver_id, $entity->workflow_version_id);
        $this->assertEquals($expected, $entity->active);
    }

    /**
     * @param array $record
     * @param boolean $expected
     * @covers mod_approval\testing\generator::create_approval_level_for_behat
     * @dataProvider data_active_provider
     */
    public function test_create_approval_level_for_behat(array $record, bool $expected): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $stage_id = $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Test stage', 'type' => approvals::get_enum()]);
        $id = $this->gen->create_approval_level_for_behat(
            array_merge(['workflow_stage' => 'Test stage', 'name' => 'Test level'], $record)
        );
        $entity = new workflow_stage_approval_level_entity($id);
        $this->assertEquals(2, workflow_stage_approval_level_entity::repository()->count());
        $this->assertEquals(2, $entity->sortorder);
        $this->assertEquals('Test level', $entity->name);
        $this->assertEquals($stage_id, $entity->workflow_stage_id);
        $this->assertEquals($expected, $entity->active);
    }

    /**
     * @param array $record
     * @param boolean $expected
     * @covers mod_approval\testing\generator::create_formview_for_behat
     * @dataProvider data_active_provider
     */
    public function test_create_formview_for_behat(array $record, bool $expected): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $stage_id = $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Test stage', 'type' => form_submission::get_enum()]);

        $id = $this->gen->create_formview_for_behat(
            array_merge(['workflow_stage' => 'Test stage', 'field_key' => 'request'], $record)
        );

        $entity = new workflow_stage_formview_entity($id);
        $this->assertEquals(3, workflow_stage_formview_entity::repository()->count());
        $this->assertNull($entity->default_value);
        $this->assertEquals('request', $entity->field_key);
        $this->assertFalse($entity->required);
        $this->assertFalse($entity->disabled);
        $this->assertEquals($stage_id, $entity->workflow_stage_id);
        $this->assertEquals($expected, $entity->active);
    }

    /**
     * @param array $record
     * @param integer $expected
     * @covers mod_approval\testing\generator::create_assignment_for_behat
     * @dataProvider data_active_status_provider
     */
    public function test_create_assignment_for_behat(array $record, int $expected): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $default_cohort_id = $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001'])->id;
        $override_cohort_id = $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD002'])->id;
        $workflow_id = $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Test stage', 'type' => form_submission::get_enum()]);
        $course_id = (new workflow_entity($workflow_id))->course_id;

        $workflow = workflow::load_by_id($workflow_id);
        $ass_id = $workflow->get_default_assignment()->id;

        $entity = new assignment_entity($ass_id);
        $this->assertEquals(1, assignment_entity::repository()->count());
        $this->assertEquals('Cohort 1', $entity->name);
        $this->assertEquals('AUD001', $entity->id_number);
        $this->assertTrue($entity->is_default);
        $this->assertEquals($course_id, $entity->course);
        $this->assertEquals(assignment_type\cohort::get_code(), $entity->assignment_type);
        $this->assertEquals($default_cohort_id, $entity->assignment_identifier);

        $id = $this->gen->create_assignment_for_behat(array_merge(
            [
                'name' => 'Test assignment 2',
                'id_number' => 'ASS002',
                'workflow' => 'WKF001',
                'type' => 'cohort',
                'identifier' => 'AUD002',
                'default' => 'false',
            ],
            $record
        ));
        $entity = new assignment_entity($id);
        $this->assertEquals(2, assignment_entity::repository()->count());
        $this->assertEquals('Cohort 2', $entity->name);
        $this->assertEquals('ASS002', $entity->id_number);
        $this->assertFalse($entity->is_default);
        $this->assertEquals($expected, $entity->status);
    }

    /**
     * @param array $record
     * @param boolean $expected
     * @covers mod_approval\testing\generator::create_approver_for_behat
     * @dataProvider data_active_provider
     */
    public function test_create_approver_for_behat(array $record, bool $expected): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $workflow = $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Test stage', 'type' => approvals::get_enum()]);
        $level1_id = $this->gen->create_approval_level_for_behat(['workflow_stage' => 'Test stage', 'name' => 'Test level 1']);
        $level2_id = $this->gen->create_approval_level_for_behat(['workflow_stage' => 'Test stage', 'name' => 'Test level 2']);
        $user_id = $this->getDataGenerator()->create_user(['username' => 'bob'])->id;

        $workflow = workflow::load_by_id($workflow);
        $ass_id = $workflow->get_default_assignment()->id;
        $ass_id_number = $workflow->get_default_assignment()->id_number;

        $id = $this->gen->create_approver_for_behat(array_merge(
            ['assignment' => $ass_id_number, 'approval_level' => 'Test level 1', 'type' => 'user', 'identifier' => 'bob'],
            $record
        ));
        $entity = new assignment_approver_entity($id);
        $this->assertEquals(1, assignment_approver_entity::repository()->count());
        $this->assertEquals($ass_id, $entity->approval_id);
        $this->assertEquals($level1_id, $entity->workflow_stage_approval_level_id);
        $this->assertEquals(approver_user::TYPE_IDENTIFIER, $entity->type);
        $this->assertEquals($user_id, $entity->identifier);
        $this->assertEquals($expected, $entity->active);

        $manager_id = relationship::load_by_idnumber('manager')->id;
        $id = $this->gen->create_approver_for_behat(array_merge(
            ['assignment' => $ass_id_number, 'approval_level' => 'Test level 2', 'type' => 'relationship', 'identifier' => 'manager'],
            $record
        ));
        $entity = new assignment_approver_entity($id);
        $this->assertEquals(2, assignment_approver_entity::repository()->count());
        $this->assertEquals($ass_id, $entity->approval_id);
        $this->assertEquals($level2_id, $entity->workflow_stage_approval_level_id);
        $this->assertEquals(approver_relationship::TYPE_IDENTIFIER, $entity->type);
        $this->assertEquals($manager_id, $entity->identifier);
        $this->assertEquals($expected, $entity->active);
    }

    /**
     * @covers mod_approval\testing\generator::create_application_for_behat
     */
    public function test_create_application_for_behat(): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $workflow = $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $stage1_id = $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Test stage 1', 'type' => form_submission::get_enum()]);
        $stage2_id = $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Test stage 2', 'type' => form_submission::get_enum()]);

        $workflow = workflow::load_by_id($workflow);
        $workflow->get_default_assignment()->activate();
        $ass_id = $workflow->get_default_assignment()->id;
        $ass_id_number = $workflow->get_default_assignment()->id_number;

        $applicant_id = $this->getDataGenerator()->create_user(['username' => 'bob'])->id;
        $creator_id = $this->getDataGenerator()->create_user(['username' => 'rob'])->id;
        $ja_id = job_assignment::create(['userid' => $applicant_id, 'idnumber' => 'jajaja'])->id;

        $id = $this->gen->create_application_for_behat(
            ['title' => 'Test application 1', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]
        );
        $entity = new application_entity($id);
        $this->assertEquals(1, application_entity::repository()->count());
        $this->assertEquals('Test application 1', $entity->title);
        $this->assertNull($entity->job_assignment_id);
        $this->assertEquals($applicant_id, $entity->user_id);
        $this->assertEquals($applicant_id, $entity->creator_id);
        $this->assertEquals($stage1_id, $entity->current_stage_id);
        $this->assertEquals($ass_id, $entity->approval_id);
        $this->assertEquals(1, $entity->is_draft);

        $id = $this->gen->create_application_for_behat(
            ['title' => 'Test application 2', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number, 'creator' => 'rob']
        );
        $entity = new application_entity($id);
        $this->assertEquals(2, application_entity::repository()->count());
        $this->assertEquals('Test application 2', $entity->title);
        $this->assertNull($entity->job_assignment_id);
        $this->assertEquals($applicant_id, $entity->user_id);
        $this->assertEquals($creator_id, $entity->creator_id);
        $this->assertEquals($stage1_id, $entity->current_stage_id);
        $this->assertEquals($ass_id, $entity->approval_id);
        $this->assertEquals(1, $entity->is_draft);

        $id = $this->gen->create_application_for_behat([
            'title' => 'Test application 3',
            'user' => 'bob',
            'workflow' => 'WKF001',
            'assignment' => $ass_id_number,
            'workflow_stage' => 'Test stage 2',
        ]);
        $entity = new application_entity($id);
        $this->assertEquals(3, application_entity::repository()->count());
        $this->assertEquals('Test application 3', $entity->title);
        $this->assertNull($entity->job_assignment_id);
        $this->assertEquals($applicant_id, $entity->user_id);
        $this->assertEquals($stage2_id, $entity->current_stage_id);
        $this->assertEquals($ass_id, $entity->approval_id);
        $this->assertEquals(1, $entity->is_draft);

        $id = $this->gen->create_application_for_behat([
            'title' => 'Test application 4',
            'user' => 'bob',
            'workflow' => 'WKF001',
            'assignment' => $ass_id_number,
            'job_assignment' => 'jajaja',
        ]);
        $entity = new application_entity($id);
        $this->assertEquals(4, application_entity::repository()->count());
        $this->assertEquals('Test application 4', $entity->title);
        $this->assertEquals($ja_id, $entity->job_assignment_id);
        $this->assertEquals($applicant_id, $entity->user_id);
        $this->assertEquals($ass_id, $entity->approval_id);
        $this->assertEquals(1, $entity->is_draft);

        $id = $this->gen->create_application_for_behat(['user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]);
        $entity = new application_entity($id);
        $this->assertEquals(5, application_entity::repository()->count());
        $this->assertEquals('Test workflow type', $entity->title);
        $this->assertNull($entity->job_assignment_id);
        $this->assertEquals($applicant_id, $entity->user_id);
        $this->assertEquals($ass_id, $entity->approval_id);
        $this->assertEquals(1, $entity->is_draft);
    }

    /**
     * @covers mod_approval\testing\generator::create_application_submission_for_behat
     */
    public function test_create_application_submission_for_behat(): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $workflow = $this->gen->create_workflow_for_behat([
            'name' => 'Test workflow',
            'id_number' => 'WKF001',
            'workflow_type' => 'Test workflow type',
            'form' => 'Test form',
            'type' => 'cohort',
            'identifier' => 'AUD001'
        ]);
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Test stage', 'type' => form_submission::get_enum()]);
        $user_id = $this->getDataGenerator()->create_user(['username' => 'bob'])->id;

        $workflow = workflow::load_by_id($workflow);
        $ass_id_number = $workflow->get_default_assignment()->id_number;
        $workflow->get_default_assignment()->activate();

        $app1_id = $this->gen->create_application_for_behat(
            ['title' => 'Test application 1', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]
        );
        $app2_id = $this->gen->create_application_for_behat(
            ['title' => 'Test application 2', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]
        );

        $id = $this->gen->create_application_submission_for_behat(['application' => 'Test application 1', 'user' => 'bob']);
        $entity = new application_submission_entity($id);
        $this->assertEquals(1, application_submission_entity::repository()->count());
        $this->assertEquals($app1_id, $entity->application_id);
        $this->assertEquals($user_id, $entity->user_id);
        $this->assertEquals('{}', $entity->form_data);

        $id = $this->gen->create_application_submission_for_behat(
            ['application' => 'Test application 1', 'user' => 'bob', 'form_data' => '{"request":"datA","notes":"Data"}']
        );
        $entity = new application_submission_entity($id);
        $this->assertEquals(1, application_submission_entity::repository()->count());
        $this->assertEquals($app1_id, $entity->application_id);
        $this->assertEquals($user_id, $entity->user_id);
        $this->assertEquals('{"request":"datA","notes":"Data"}', $entity->form_data);

        $id = $this->gen->create_application_submission_for_behat(
            ['application' => 'Test application 2', 'user' => 'bob', 'form_data' => '{"notes":"$chEMa"}']
        );
        $entity = new application_submission_entity($id);
        $this->assertEquals(2, application_submission_entity::repository()->count());
        $this->assertEquals($app2_id, $entity->application_id);
        $this->assertEquals($user_id, $entity->user_id);
        $this->assertEquals('{"notes":"$chEMa"}', $entity->form_data);
    }

    /**
     * @covers mod_approval\testing\generator::create_application_action_for_behat
     */
    public function test_create_application_action_for_behat_without_submission(): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $workflow = $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Test stage', 'type' => form_submission::get_enum()]);
        $this->getDataGenerator()->create_user(['username' => 'bob'])->id;

        $workflow = workflow::load_by_id($workflow);
        $ass_id_number = $workflow->get_default_assignment()->id_number;
        $workflow->get_default_assignment()->activate();

        $this->gen->create_application_for_behat(
            ['title' => 'Test application', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]
        );

        try {
            $this->gen->create_application_action_for_behat(
                ['application' => 'Test application', 'user' => 'bob', 'action' => 'submit']
            );
            $this->fail('coding_exception expected');
        } catch (coding_exception $e) {
            $this->assertStringContainsString('No submission', $e->getMessage());
        }
    }

    /**
     * @return array
     */
    public function data_bob_or_rob(): array {
        return [
            'bob' => ['bob'],
            'rob' => ['rob'],
        ];
    }

    /**
     * @param string $actor
     * @covers mod_approval\testing\generator::create_application_action_for_behat
     * @dataProvider data_bob_or_rob
     */
    public function test_create_application_action_for_behat_submission(string $actor): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $workflow = $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 1', 'type' => form_submission::get_enum()]);
        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 2', 'type' => approvals::get_enum()]);
        $this->gen->create_approval_level_for_behat(['workflow_stage' => 'Stage 2', 'name' => 'Test level 1']);
        $this->gen->create_approval_level_for_behat(['workflow_stage' => 'Stage 2', 'name' => 'Test level 2']);
        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 3', 'type' => finished::get_enum()]);
        $this->getDataGenerator()->create_user(['username' => 'bob']);
        $this->getDataGenerator()->create_user(['username' => 'rob']);

        $workflow = workflow::load_by_id($workflow);
        $ass_id_number = $workflow->get_default_assignment()->id_number;
        $workflow->get_default_assignment()->activate();

        $app_id = $this->gen->create_application_for_behat(
            ['title' => 'Test application', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]
        );
        $sub_id = $this->gen->create_application_submission_for_behat(['application' => 'Test application', 'user' => 'bob']);
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application', 'user' => $actor, 'action' => 'submit']
        );
        $application = new application_entity($app_id);
        $this->assertEquals(approvals::get_code(), $application->current_stage->type_code);
        $this->assertEquals(0, $application->is_draft);
        $submission = new application_submission_entity($sub_id);
        $this->assertNotNull($submission->submitted);
        $this->assertEquals($actor, $submission->user->username);
    }

    /**
     * @return array
     */
    public function data_approval_actions(): array {
        return [
            'approve' => ['approve', approve_action::get_code()],
            'reject' => ['reject', reject_action::get_code()],
        ];
    }

    /**
     * @param string $action
     * @param integer $expected
     * @covers mod_approval\testing\generator::create_application_action_for_behat
     * @dataProvider data_approval_actions
     */
    public function test_create_application_action_for_behat_approval(string $action, int $expected): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $workflow = $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 1', 'type' => form_submission::get_enum()]);
        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 2', 'type' => approvals::get_enum()]);
        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 3', 'type' => finished::get_enum()]);
        $this->gen->create_approval_level_for_behat(['workflow_stage' => 'Stage 2', 'name' => 'Test level 2']);
        $this->getDataGenerator()->create_user(['username' => 'bob']);
        $this->getDataGenerator()->create_user(['username' => 'rob']);

        $workflow = workflow::load_by_id($workflow);
        $ass_id_number = $workflow->get_default_assignment()->id_number;
        $workflow->get_default_assignment()->activate();

        $app_id = $this->gen->create_application_for_behat(
            ['title' => 'Test application 1', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]
        );
        $this->gen->create_application_submission_for_behat(['application' => 'Test application 1', 'user' => 'bob']);
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 1', 'user' => 'bob', 'action' => 'submit']
        );
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 1', 'user' => 'rob', 'action' => $action]
        );
        /** @var application_action_entity $entity */
        $entity = application_action_entity::repository()->one(true);
        $this->assertEquals($expected, $entity->code);
        $this->assertEquals('Level 1', $entity->workflow_stage_approval_level->name);
        $this->assertEquals('rob', $entity->user->username);
        $this->assertEquals($app_id, $entity->application_id);

        $this->gen->create_application_for_behat(
            ['title' => 'Test application 2', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]
        );
        $this->gen->create_application_submission_for_behat(['application' => 'Test application 2', 'user' => 'bob']);
        try {
            $this->gen->create_application_action_for_behat(
                ['application' => 'Test application 2', 'user' => 'rob', 'action' => $action]
            );
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Cannot ' . $action, $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::create_application_action_for_behat
     */
    public function test_create_application_action_for_behat_withdrawal(): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $workflow = $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);

        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 1', 'type' => form_submission::get_enum()]);

        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 2', 'type' => approvals::get_enum()]);
        $this->gen->create_approval_level_for_behat(['workflow_stage' => 'Stage 2', 'name' => 'Test level 2']);

        $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 3', 'type' => finished::get_enum()]);

        $this->getDataGenerator()->create_user(['username' => 'bob']);
        $this->getDataGenerator()->create_user(['username' => 'gob']);
        $this->getDataGenerator()->create_user(['username' => 'rob']);
        $this->getDataGenerator()->create_user(['username' => 'aob']);

        $workflow = workflow::load_by_id($workflow);
        $ass_id_number = $workflow->get_default_assignment()->id_number;
        $workflow->get_default_assignment()->activate();

        // submit -> withdraw
        $app_id = $this->gen->create_application_for_behat(
            ['title' => 'Test application 1', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]
        );
        $this->gen->create_application_submission_for_behat(['application' => 'Test application 1', 'user' => 'bob']);
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 1', 'user' => 'bob', 'action' => 'submit']
        );
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 1', 'user' => 'gob', 'action' => 'withdraw']
        );
        /** @var application_action_entity $entity */
        $entity = application_action_entity::repository()->one(true);
        $this->assertEquals(withdraw_in_approvals_action::get_code(), $entity->code);
        $this->assertEquals('Level 1', $entity->workflow_stage_approval_level->name);
        $this->assertEquals('gob', $entity->user->username);
        $this->assertEquals($app_id, $entity->application_id);
        application_action_entity::repository()->delete();

        // submit -> approve -> reject -> withdraw
        $app_id = $this->gen->create_application_for_behat(
            ['title' => 'Test application 2', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]
        );
        $this->gen->create_application_submission_for_behat(['application' => 'Test application 2', 'user' => 'bob']);
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 2', 'user' => 'bob', 'action' => 'submit']
        );
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 2', 'user' => 'gob', 'action' => 'approve']
        );
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 2', 'user' => 'rob', 'action' => 'reject']
        );
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 2', 'user' => 'aob', 'action' => 'withdraw']
        );
        $this->assertEquals(3, application_action_entity::repository()->count());
        /** @var application_action_entity $entity */
        $entity = application_action_entity::repository()->order_by('id', 'desc')->first(true);
        $this->assertEquals(withdraw_before_submission_action::get_code(), $entity->code);
        $this->assertNull($entity->workflow_stage_approval_level);
        $this->assertEquals('aob', $entity->user->username);
        $this->assertEquals($app_id, $entity->application_id);

        // submit -> complete -> withdraw
        $this->gen->create_application_for_behat(
            ['title' => 'Test application 3', 'user' => 'bob', 'workflow' => 'WKF001', 'assignment' => $ass_id_number]
        );
        $this->gen->create_application_submission_for_behat(['application' => 'Test application 3', 'user' => 'bob']);
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 3', 'user' => 'bob', 'action' => 'submit']
        );
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 3', 'user' => 'gob', 'action' => 'approve']
        );
        $this->gen->create_application_action_for_behat(
            ['application' => 'Test application 3', 'user' => 'rob', 'action' => 'approve']
        );
        try {
            $this->gen->create_application_action_for_behat(
                ['application' => 'Test application 3', 'user' => 'aob', 'action' => 'withdraw']
            );
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Cannot withdraw', $ex->getMessage());
        }
    }

    /**
     * @covers mod_approval\testing\generator::create_interaction_for_behat
     */
    public function test_create_interaction_for_behat(): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $workflow = $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $workflow_stage = $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 1', 'type' => form_submission::get_enum()]);
        $interaction_id = $this->gen->create_interaction_for_behat(
            ['workflow_stage' => 'Stage 1', 'action' => 'REJECT']
        );

        $interaction = new workflow_stage_interaction($interaction_id);
        $this->assertEquals($workflow_stage, $interaction->workflow_stage->id);
        $this->assertEquals(reject::get_code(), $interaction->action_code);
    }

    /**
     * @covers mod_approval\testing\generator::create_interaction_transition_for_behat
     */
    public function test_create_interaction_transition_for_behat(): void {
        $this->gen->create_workflow_type_for_behat(['name' => 'Test workflow type']);
        $this->gen->create_form_for_behat(['title' => 'Test form']);
        $this->gen->create_form_version_for_behat(['version' => 'one', 'form' => 'Test form']);
        $this->getDataGenerator()->create_cohort(['idnumber' => 'AUD001']);
        $workflow = $this->gen->create_workflow_for_behat(
            [
                'name' => 'Test workflow',
                'id_number' => 'WKF001',
                'workflow_type' => 'Test workflow type',
                'form' => 'Test form',
                'type' => 'cohort',
                'identifier' => 'AUD001'
            ]
        );
        $this->gen->create_workflow_version_for_behat(['workflow' => 'Test workflow', 'form_version' => 'one', 'status' => status::DRAFT_ENUM]);
        $workflow_stage = $this->gen->create_workflow_stage_for_behat(['workflow' => 'WKF001', 'name' => 'Stage 1', 'type' => form_submission::get_enum()]);
        $interaction_id = $this->gen->create_interaction_for_behat(
            ['workflow_stage' => 'Stage 1', 'action' => 'REJECT']
        );
        $interaction_transition_id = $this->gen->create_interaction_transition_for_behat(
            ['interaction' => $interaction_id, 'transition' => 'NEXT', 'data' => '{}']
        );
        $interaction_transition = new workflow_stage_interaction_transition($interaction_transition_id);
        $this->assertEquals($interaction_id, $interaction_transition->workflow_stage_interaction->id);
        $this->assertEquals('NEXT', $interaction_transition->transition);
    }
}
