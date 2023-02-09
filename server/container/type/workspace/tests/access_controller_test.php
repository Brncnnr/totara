<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\member\member;
use core_user\access_controller;
use totara_core\hook\manager as hook_manager;
use core_user\hook\allow_view_profile_field;
use container_workspace\watcher\core_user;
use totara_core\advanced_feature;
use core_user\profile\display_setting;

class container_workspace_access_controller_testcase extends advanced_testcase {
    /**
     * We are clearing the hook's watcher to just accept the one from workspace, as
     * any other hook watcher can really break this test, and the result is corrupted.
     *
     * @return void
     */
    protected function setUp(): void {
        hook_manager::phpunit_replace_watchers([
            [
                'hookname' => allow_view_profile_field::class,
                'callback' => Closure::fromCallable([core_user::class, 'watch_allow_profile_field'])
            ]
        ]);
    }

    /**
     * @return void
     */
    public function test_non_member_can_see_workspace_member(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Log in as admin to create a workspace and add user one to the workspace.
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add user one to the workspace.
        member::added_to_workspace($workspace, $user_one->id, false);

        // Log in as second user and check the access controller.
        $this->setUser($user_two);

        $access_controller = access_controller::for($user_one, $workspace->get_id());
        self::assertTrue($access_controller->can_view_field('fullname'));
        self::assertTrue($access_controller->can_view_field('id'));
    }

    /**
     * This scenario is for adding users to the workspace.
     * @return void
     */
    public function test_member_can_see_non_member_in_public_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Check whether user one is able to see user two in workspace context.
        $access_controller = access_controller::for($user_two, $workspace->get_id());
        self::assertTrue($access_controller->can_view_field('fullname'));
        self::assertTrue($access_controller->can_view_field('id'));
    }

    /**
     * This test is intentional because the watcher is not responsible to check in different state of workspace.
     * It just allows user to view straight away. The test is only to be sure that somebody changes the logic
     * will have to look after this test.
     *
     * @return void
     */
    public function test_non_member_can_see_member_in_private_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Log in as admin to create a private workspace.
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Add user one to the workspace
        member::added_to_workspace($workspace, $user_one->id, false);

        // Log in as user two to check whether the user two is able to see user one in private workspace.
        $this->setUser($user_two);
        $access_controller = access_controller::for($user_one, $workspace->get_id());

        // Note that id is always visible to different user.
        self::assertTrue($access_controller->can_view_field('id'));

        // However fullname will not be
        self::assertTrue($access_controller->can_view_field('fullname'));
    }

    /**
     * This scenario is to cover whether the member user is able to see any other user within private workspace.
     * @return void
     */
    public function test_member_can_see_non_member_in_private_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Log in as first user to create a workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Check that if user one is able to see user two.
        $access_controller = access_controller::for($user_two, $workspace->get_id());

        // Id can always be viewed
        self::assertTrue($access_controller->can_view_field('id'));

        // Check the field fullname.
        self::assertTrue($access_controller->can_view_field('fullname'));
    }

    /**
     * @return void
     */
    public function test_non_member_view_member_in_public_workspace_when_feature_is_disabled(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Log in as first user to create a workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Log in as second user to view the workspace member.
        $this->setUser($user_two);

        $access_controller = access_controller::for($user_one, $workspace->get_id());
        self::assertTrue($access_controller->can_view_field('id'));
        self::assertTrue($access_controller->can_view_field('fullname'));

        // Disable the feature container_workspace.
        advanced_feature::disable('container_workspace');
        access_controller::clear_instance_cache();

        $access_controller = access_controller::for($user_one, $workspace->get_id());

        // Id can always be viewed.
        self::assertTrue($access_controller->can_view_field('id'));

        // However fullname won't be.
        self::assertFalse($access_controller->can_view_field('fullname'));
    }

    /**
     * @return void
     */
    public function test_admin_can_see_member_in_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Set admin user to check whether admin user is able to see the fields.
        $this->setAdminUser();
        $access_controller = access_controller::for($user_one, $workspace->get_id());

        self::assertTrue($access_controller->can_view_field('id'));
        self::assertTrue($access_controller->can_view_field('fullname'));
    }

    /**
     * @return void
     */
    public function test_user_can_see_member_display_field_in_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $this->setUser($user_two);
        $access_controller = access_controller::for($user_one, $workspace->get_id());

        // Email field is not being in the display settings for mini profile card.
        self::assertFalse($access_controller->can_view_field('email'));

        // Profile picture fields are viewable - as it is enabled by default.
        self::assertTrue($access_controller->can_view_field('profileimageurl'));
        self::assertTrue($access_controller->can_view_field('profileimageurlsmall'));
        self::assertTrue($access_controller->can_view_field('profileimagealt'));

        // Update display settings.
        display_setting::save_display_fields(['fullname', 'email']);
        display_setting::save_display_user_profile(false);

        access_controller::clear_instance_cache();
        $access_controller = access_controller::for($user_one, $workspace->get_id());

        self::assertTrue($access_controller->can_view_field('email'));

        // We hide the profile image. However the fields should not be affected at all.
        self::assertTrue($access_controller->can_view_field('profileimageurl'));
        self::assertTrue($access_controller->can_view_field('profileimageurlsmall'));
        self::assertTrue($access_controller->can_view_field('profileimagealt'));
    }
}