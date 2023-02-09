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
use core\orm\query\builder;
use container_approval\approval as approval_container;
use core_phpunit\language_pack_faker_trait;
use mod_approval\controllers\application\base;
use mod_approval\controllers\application\dashboard;
use mod_approval\controllers\application\pending;
use mod_approval\controllers\application\preview;
use mod_approval\entity\application\application_action as application_action_entity;
use mod_approval\model\application\action\action;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\testing\approval_workflow_test_setup;
use totara_job\job_assignment;

global $CFG;
require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @group applications_dashboard
 */
class mod_approval_controller_application_testcase extends mod_approval_testcase {
    use approval_workflow_test_setup;
    use language_pack_faker_trait;

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Gets the generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * Ensure application controllers set a url with the application_id parameter, with a few exceptions.
     */
    public function test_page_url(): void {
        $application_id = $this->set_application();
        $classes = array_flip(core_component::get_namespace_classes('controllers\\application', base::class, 'mod_approval'));
        unset($classes[dashboard::class]);
        unset($classes[pending::class]);
        foreach ($classes as $class => $x) {
            /** @var base */
            $controller = new $class();
            ob_start();
            try {
                $controller->process();
            } catch (Throwable $ex) {
                $this->assertStringNotContainsString('You have to define an url', $ex->getMessage());
                $this->assertStringNotContainsString('Expected controller action', $ex->getMessage());
                $this->assertStringNotContainsString('No default action defined', $ex->getMessage());
            } finally {
                ob_end_clean();
            }
            $this->resetDebugging();
            $prop = new ReflectionProperty($controller, 'url');
            $prop->setAccessible(true);
            /** @var moodle_url $url */
            $url = $prop->getValue($controller);
            $this->assertInstanceOf(moodle_url::class, $url);
            $this->assertEquals($application_id, $url->param('application_id'), "{$class} does not set application_id");
        }
    }

    /**
     * @covers mod_approval\controllers\application\dashboard::can_view_others_applications
     */
    public function test_can_view_others_applications(): void {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $application = application::load_by_id($this->set_application());
        $role1 = self::getDataGenerator()->create_role();
        $role2 = self::getDataGenerator()->create_role();
        assign_capability(
            'mod/approval:view_in_dashboard_application_any',
            CAP_ALLOW,
            $role1,
            $application->get_context(),
            true
        );
        assign_capability(
            'mod/approval:view_in_dashboard_application_user',
            CAP_ALLOW,
            $role2,
            context_user::instance($application->user_id),
            true
        );
        role_assign($role1, $user3->id, $application->get_context());
        role_assign($role2, $user4->id, context_user::instance($application->user_id));
        role_assign($role1, $user5->id, $application->get_context());
        role_assign($role2, $user5->id, context_user::instance($application->user_id));
        $rm = new ReflectionMethod(dashboard::class, 'can_view_others_applications');
        $rm->setAccessible(true);
        $this->set_user_with_capability_maps($user1);
        $this->assertFalse($rm->invoke(null, $user1->id));
        $this->set_user_with_capability_maps($user2);
        $this->assertFalse($rm->invoke(null, $user2->id));
        $this->set_user_with_capability_maps($user3);
        $this->assertTrue($rm->invoke(null, $user3->id));
        $this->set_user_with_capability_maps($user4);
        $this->assertTrue($rm->invoke(null, $user4->id));
        $this->set_user_with_capability_maps($user5);
        $this->assertTrue($rm->invoke(null, $user5->id));
    }

    public function test_can_create_application_user() {
        $user = self::getDataGenerator()->create_user();

        $this->set_user_with_capability_maps($user);
        $dashboard = (new dashboard())->action();

        $props = [
            'current-user-id' => $user->id,
            'contextId' => approval_container::get_default_category_context()->id,
            'page-props' => [
                'tabs' => [
                    'my-applications' => true,
                    'applications-from-others' => false,
                ],
                'new-application-on-behalf' => false
            ]
        ];
        self::assertEquals($props, $dashboard->get_data());
    }

    public function test_can_create_application_manager() {
        $user = self::getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        job_assignment::create_default($user->id, ['managerjaid' => job_assignment::create_default($manager->id)->id]);

        $this->set_user_with_capability_maps($manager);
        $dashboard = (new dashboard())->action();

        $props = [
            'current-user-id' => $manager->id,
            'contextId' => approval_container::get_default_category_context()->id,
            'page-props' => [
                'tabs' => [
                    'my-applications' => true,
                    'applications-from-others' => true,
                ],
                'new-application-on-behalf' => false
            ]
        ];
        self::assertEquals($props, $dashboard->get_data());
    }

    public function test_can_create_application_tempmanager() {
        $user = $this->getDataGenerator()->create_user();
        $tempmanager = $this->getDataGenerator()->create_user();

        $tempmanagerja = job_assignment::create_default($tempmanager->id);
        $userja = job_assignment::create_default($user->id);

        $tempmanagerexpirydate = totara_date_parse_from_format('d/m/Y', '29/06/2022');
        $userja->update(
            [
                'tempmanagerjaid' => $tempmanagerja->id,
                'tempmanagerexpirydate' => $tempmanagerexpirydate
            ]
        );

        $this->set_user_with_capability_maps($tempmanager);
        $dashboard = (new dashboard())->action();

        $props = [
            'current-user-id' => $tempmanager->id,
            'contextId' => approval_container::get_default_category_context()->id,
            'page-props' => [
                'tabs' => [
                    'my-applications' => true,
                    'applications-from-others' => true,
                ],
                'new-application-on-behalf' => false
            ]
        ];
        self::assertEquals($props, $dashboard->get_data());
    }

    public function test_can_create_application_any_capability() {
        $context = approval_container::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        // By default capability is allowed.
        $this->set_user_with_capability_maps($workflow_manager_user);
        $dashboard = (new dashboard())->action();

        $props = [
            'current-user-id' => $workflow_manager_user->id,
            'contextId' => $context->id,
            'page-props' => [
                'tabs' => [
                    'my-applications' => true,
                    'applications-from-others' => false,
                ],
                'new-application-on-behalf' => true
            ]
        ];
        self::assertEquals($props, $dashboard->get_data());

        // Remove capability from the role.
        $this->setAdminUser();
        assign_capability('mod/approval:create_application_any', CAP_PREVENT, $workflow_manager_role->id, $context, true);

        // Test again.
        $this->set_user_with_capability_maps($workflow_manager_user);
        $dashboard = (new dashboard())->action();

        $props = [
            'current-user-id' => $workflow_manager_user->id,
            'contextId' => $context->id,
            'page-props' => [
                'tabs' => [
                    'my-applications' => true,
                    'applications-from-others' => false,
                ],
                'new-application-on-behalf' => false
            ]
        ];
        self::assertEquals($props, $dashboard->get_data());
    }

    public function test_can_create_application_user_capability() {
        $applicant_user = self::getDataGenerator()->create_user();
        $staffmanager_user = self::getDataGenerator()->create_user();
        $staffmanager_role_id = self::getDataGenerator()->create_role();
        role_assign($staffmanager_role_id, $staffmanager_user->id, context_user::instance($applicant_user->id)->id);

        $this->set_user_with_capability_maps($staffmanager_user);
        $dashboard = (new dashboard())->action();

        $props = [
            'current-user-id' => $staffmanager_user->id,
            'contextId' => approval_container::get_default_category_context()->id,
            'page-props' => [
                'tabs' => [
                    'my-applications' => true,
                    'applications-from-others' => false,
                ],
                'new-application-on-behalf' => false
            ]
        ];
        self::assertEquals($props, $dashboard->get_data());

        // Grant capability to the role.
        $this->setAdminUser();
        assign_capability('mod/approval:create_application_user', CAP_ALLOW, $staffmanager_role_id, context_user::instance($applicant_user->id)->id, true);

        // Test again.
        $this->set_user_with_capability_maps($staffmanager_user);
        $dashboard = (new dashboard())->action();

        $props = [
            'current-user-id' => $staffmanager_user->id,
            'contextId' => approval_container::get_default_category_context()->id,
            'page-props' => [
                'tabs' => [
                    'my-applications' => true,
                    'applications-from-others' => false,
                ],
                'new-application-on-behalf' => true
            ]
        ];
        self::assertEquals($props, $dashboard->get_data());
    }

    /**
     * @covers mod_approval\controllers\application\preview::get_approvers
     */
    public function test_preview_get_approvers() {
        // Set up the method we are testing.
        $method = new ReflectionMethod(preview::class, 'get_approvers');
        $method->setAccessible(true);

        // Set up users and application.
        $user1 = new user(
            $this->getDataGenerator()->create_user(['firstname' => 'One', 'lastname' => 'Uno', 'middlename' => ''])
        );
        $user2 = new user(
            $this->getDataGenerator()->create_user(['firstname' => 'Two', 'lastname' => 'Dos', 'middlename' => ''])
        );
        $user3 = new user(
            $this->getDataGenerator()->create_user(['firstname' => 'Three', 'lastname' => 'Tres', 'middlename' => ''])
        );
        $time1 = (new DateTime('2001-01-01T01:01:01+0800'))->getTimestamp();
        $time2 = (new DateTime('2002-02-02T02:02:02+0800'))->getTimestamp();
        $time3 = (new DateTime('2003-03-03T03:03:03+0800'))->getTimestamp();
        $application = application::load_by_id($this->set_application());

        // Create the first submission.
        $form_data =  form_data::from_json('{"kia":"Astronauts are inherently insane. And really noble."}');
        $submission = application_submission::create_or_update($application, $user1->id, $form_data);
        $submission->publish($user1->id);
        submit::execute($application, $user1->id);

        // Set up the tool which will execute the action and update the timestamp.
        $do_action = function (user $user, action $action, int $time) use ($application) {
            $action::execute($application, $user->id);
            /** @var application_action_entity $application_action */
            $application_action =  builder::table(application_action_entity::TABLE)
                ->order_by('id', 'DESC')
                ->first();
            builder::table(application_action_entity::TABLE)
                ->where('id', $application_action->id)
                ->update(['created' => $time]);
        };

        // set up controller
        $controller = new preview();
        $controller->setup_context();

        // none
        $this->assertEquals([], $method->invoke($controller, $application));
        // approved, rejected, withdrawn
        $do_action($user2, new approve(), $time3);
        $do_action($user1, new reject(), $time2);
        $submission = application_submission::create_or_update($application, user::logged_in()->id, form_data::create_empty());
        $submission->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);
        $do_action($user1, new approve(), $time1);
        $do_action($user2, new withdraw_in_approvals(), $time2);
        $submission = application_submission::create_or_update($application, user::logged_in()->id, form_data::create_empty());
        $submission->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);
        $do_action($user3, new approve(), $time3);
        $do_action($user2, new approve(), $time2);
        $expected = [
            ['level' => 'Level 1', 'fullname' => 'Three Tres', 'timestamp' => '2003-03-03T03:03:03+0800'],
            ['level' => 'Level 2', 'fullname' => 'Two Dos', 'timestamp' => '2002-02-02T02:02:02+0800'],
        ];
        $this->assertEquals($expected, $method->invoke($controller, $application));
    }

    /**
     * @covers mod_approval\controllers\application\preview::get_approvers
     */
    public function test_preview_get_approvers_multilang() {
        // Turn on the multilang filter.
        global $CFG;
        require_once($CFG->libdir . '/filterlib.php');
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', 1);

        // Add a fake language pack that we can use for the test.
        $this->add_fake_language_pack('le_et', []);

        // Set up the method we will test.
        $method = new ReflectionMethod(preview::class, 'get_approvers');
        $method->setAccessible(true);

        // Create an application.
        $user1 = $this->create_user();
        $this->set_user_with_capability_maps($user1);
        $application = $this->create_application_for_user();

        // Override level name in the workflow that was created for the application.
        $lv1id = builder::table('approval_workflow_stage_approval_level')->one()->id;
        builder::table('approval_workflow_stage_approval_level')
            ->where('id', $lv1id)
            ->update(
                ['name' => '<span lang="en" class="multilang">Level 1</span><span lang="le-et" class="multilang">|_3\/3|_ 1</span>']
            );

        // Create an "approve" application action.
        $form_data =  form_data::from_json('{"kia":"Astronauts are inherently insane. And really noble."}');
        $submission = application_submission::create_or_update($application, $user1->id, $form_data);
        $submission->publish($user1->id);
        submit::execute($application, $user1->id);
        $application->refresh();
        application_action::create(
            $application,
            $user1->id,
            new approve()
        );

        // set up controller
        $_GET['application_id'] = $application->id;
        $controller = new preview();
        $controller->setup_context();

        // check translation
        $this->assertEquals('Level 1', $method->invoke($controller, $application)[0]['level']);
        force_current_language('le_et');
        $this->assertEquals('|_3\/3|_ 1', $method->invoke($controller, $application)[0]['level']);
    }

    /**
     * @return integer
     */
    private function set_application(): int {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment('Testing', false, false);
        $workflow_model = workflow::load_by_entity($workflow);
        $workflow_version = $workflow_model->latest_version;
        $stage1 = $workflow_version->stages->first();

        // Add a second stage to the application's workflow.
        $stage2 = $workflow_model->latest_version->get_next_stage($stage1->id);
        $stage2->add_approval_level('Level 2');
        $workflow_model->publish($workflow_version);
        $application_id = $this->create_application($workflow, $assignment, user::logged_in())->id;
        $_POST['application_id'] = $application_id;
        return $application_id;
    }
}
