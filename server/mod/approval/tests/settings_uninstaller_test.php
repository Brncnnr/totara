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

use container_approval\approval;
use core\entity\tenant;
use core\entity\user;
use core\orm\query\builder;
use mod_approval\entity\application\application as application_entity;
use mod_approval\entity\application\application_action as application_action_entity;
use mod_approval\entity\application\application_submission as application_submission_entity;
use mod_approval\entity\form\form as form_entity;
use mod_approval\entity\form\form_version as form_version_entity;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\entity\workflow\workflow_stage_formview as workflow_stage_formview_entity;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_version;
use totara_comment\comment;
use totara_comment\entity\comment as comment_entity;
use totara_tenant\testing\generator as tenant_generator;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\settings\uninstaller
 */
class mod_approval_settings_uninstaller_testcase extends mod_approval_testcase {

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    private static function do_uninstall(): void {
        $type = 'mod';
        $name = 'approval';
        // partially taken from adminlib.php
        $component = $type . '_' . $name;
        if ($type === 'mod') {
            $pluginname = $name;
        } else {
            $pluginname = $component;
        }
        $plugindirectory = core_component::get_plugin_directory($type, $name);
        $uninstalllib = $plugindirectory . '/db/uninstall.php';
        require_once($uninstalllib);
        $uninstallfunction = 'xmldb_' . $pluginname . '_uninstall';
        $uninstallfunction();
    }

    /**
     * @covers ::uninstall
     */
    public function test_uninstall_by_admin(): void {
        $this->setAdminUser();
        self::do_uninstall();
    }

    /**
     * @covers ::uninstall
     */
    public function test_uninstall_by_user(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        try {
            self::do_uninstall();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('You do not have permission', $ex->getMessage());
        }
    }

    /**
     * @covers ::delete_comments
     */
    public function test_delete_comments(): void {
        $tengen = tenant_generator::instance();
        $tengen->enable_tenants();
        $ten1 = new tenant($tengen->create_tenant());
        $user0 = new user($this->getDataGenerator()->create_user(['tenantid' => null]));
        $user1 = new user($this->getDataGenerator()->create_user(['tenantid' => $ten1->id]));

        $comment1 = comment::create(42, 'Hooray!', 'comment', 'mod_approval', FORMAT_PLAIN, $user0->id);
        $reply1 = comment::create(42, 'Whee!', 'comment', 'mod_approval', FORMAT_PLAIN, $user0->id, $comment1->get_id());
        $comment2 = comment::create(77, 'Wow!', 'comment', 'mod_approval', FORMAT_PLAIN, $user0->id);
        $comment3 = comment::create(10, 'Ten Ant!', 'comment', 'mod_approval', FORMAT_PLAIN, $user1->id);
        $whatever = comment::create(89, 'Wow!', 'whatever', 'mod_approval', FORMAT_PLAIN, $user0->id);
        $fake1 = comment::create(11, 'Fake!', 'fake', 'test_component', FORMAT_PLAIN, $user0->id);
        $fake2 = comment::create(12, 'Fake!', 'fake', 'test_component', FORMAT_PLAIN, $user1->id);

        $vanished = [$comment1->get_id(), $reply1->get_id(), $comment2->get_id(), $comment3->get_id(), $whatever->get_id()];
        $preserved = [$fake1->get_id(), $fake2->get_id()];

        $this->setAdminUser();
        self::do_uninstall();
        $this->assertEquals([], comment_entity::repository()->where_in('id', $vanished)->select('id')->get()->keys());
        $this->assertEquals($preserved, comment_entity::repository()->where_in('id', $preserved)->select('id')->get()->keys());
    }

    /**
     * @covers ::delete_categories
     */
    public function test_delete_categories_preserves_user_categories(): void {
        $this->setAdminUser();
        $this->create_workflow_for_user();
        $usercat_id = $this->getDataGenerator()->create_category(['name' => approval::get_container_category_name()])->id;
        $syscat_id = approval::get_default_category_id();

        self::do_uninstall();
        $this->assertTrue(builder::table('course_categories')->where('id', $usercat_id)->exists());
        $this->assertFalse(builder::table('course_categories')->where('id', $syscat_id)->exists());
    }

    /**
     * @covers ::delete_categories
     */
    public function test_delete_categories_and_data(): void {
        $tengen = tenant_generator::instance();
        $tengen->enable_tenants();
        $ten1 = new tenant($tengen->create_tenant());
        $user0 = new user($this->getDataGenerator()->create_user(['tenantid' => null]));
        $user1 = new user($this->getDataGenerator()->create_user(['tenantid' => null]));
        $user2 = new user($this->getDataGenerator()->create_user(['tenantid' => null]));
        $user3 = new user($this->getDataGenerator()->create_user(['tenantid' => null]));
        $user4 = new user($this->getDataGenerator()->create_user(['tenantid' => null]));
        $user5 = new user($this->getDataGenerator()->create_user(['tenantid' => $ten1->id]));
        $user6 = new user($this->getDataGenerator()->create_user(['tenantid' => $ten1->id]));
        $user7 = new user($this->getDataGenerator()->create_user(['tenantid' => $ten1->id]));
        $form_data = form_data::from_json('{"kia":"ora"}');

        // some courses
        $catid = $this->getDataGenerator()->create_category(['name' => 'test category'])->id;
        $this->getDataGenerator()->create_course(['category' => $catid, 'name' => 'test course 1']);
        $this->getDataGenerator()->create_course(['name' => 'test course 2']);

        $num_courses = builder::table('course')->count();
        $num_sections = builder::table('course_sections')->count();
        $num_mods = builder::table('course_modules')->count();

        // for global
        $this->setUser($user0);
        $workflow1 = $this->create_workflow_for_user();
        $this->setUser($user1);
        $application1 = $this->create_application_for_user_on($workflow1);
        $submission = application_submission::create_or_update($application1, $user1->id, $form_data);
        $submission->publish($user1->id);
        submit::execute($application1, $user1->id);
        $this->setUser($user2);
        application_action::create($application1, $user3->id, new approve());

        // for another global
        $this->setUser();
        $form1 = $workflow1->form;
        $version1 = form_version::create($form1, 'yet another form', $form1->latest_version->json_schema);
        $workflow_version1 = workflow_version::create($workflow1, $version1);
        $workflow1->refresh(true);
        $workflow_stage1 = workflow_stage::create($workflow_version1, 'stage 1', form_submission::get_enum());
        $workflow_stage2 = workflow_stage::create($workflow_version1, 'stage 2', approvals::get_enum());
        workflow_stage_formview::create($workflow_stage1, 'kia', true, false, 'KIA');
        workflow_stage_formview::create($workflow_stage1, 'ora', false, false, 'ORA');

        $workflow_stage2->add_approval_level('level 2');
        $workflow_stage2->add_approval_level('level 3');
        $workflow_version1->activate();

        $this->setUser($user3);
        $application1 = $this->create_application_for_user_on($workflow1);
        $submission = application_submission::create_or_update($application1, $user1->id, $form_data);
        $submission->publish($user3->id);
        submit::execute($application1, $user3->id);
        $this->setUser($user4);
        application_action::create($application1, $user3->id, new approve());

        // for tenant
        $this->setUser($user5);
        $workflow2 = $this->create_workflow_for_user();
        $this->setUser($user6);
        $application2 = $this->create_application_for_user_on($workflow2);
        $submission = application_submission::create_or_update($application2, $user4->id, $form_data);
        $submission->publish($user6->id);
        submit::execute($application2, $user6->id);
        $this->setUser($user7);
        application_action::create($application2, $user5->id, new reject());

        $this->assertEquals(2, builder::table('course_categories')->where('name', approval::DEFAULT_CATEGORY_NAME)->count());
        $this->assertEquals(2 + $num_courses, builder::table('course')->count());

        $this->setAdminUser();
        $forms = form_entity::repository()->count();
        $form_versions = form_version_entity::repository()->count();
        self::do_uninstall();
        $this->assertEquals(0, application_entity::repository()->count());
        $this->assertEquals(0, application_action_entity::repository()->count());
        $this->assertEquals(0, application_submission_entity::repository()->count());
        $this->assertEquals(0, workflow_entity::repository()->count());
        $this->assertEquals(0, workflow_stage_entity::repository()->count());
        $this->assertEquals(0, workflow_stage_approval_level_entity::repository()->count());
        $this->assertEquals(0, workflow_stage_formview_entity::repository()->count());
        $this->assertEquals(0, assignment_entity::repository()->count());
        $this->assertEquals(0, assignment_approver_entity::repository()->count());
        $this->assertEquals(0, builder::table('course_categories')->where('name', approval::DEFAULT_CATEGORY_NAME)->count());
        $this->assertEquals($num_courses, builder::table('course')->count());
        $this->assertEquals($num_sections, builder::table('course_sections')->count());
        $this->assertEquals($num_mods, builder::table('course_modules')->count());
        $this->assertEquals($forms, form_entity::repository()->count(), 'forms should be preserved');
        $this->assertEquals($form_versions, form_version_entity::repository()->count(), 'form_versions should be preserved');
    }
}
