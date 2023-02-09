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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\workspace;

/**
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_webapi_workspace_interactor_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_workspace_interactor_by_owner(): void {
        $this->setup_user();
        $workspace = $this->create_workspace();
        $interactor = $this->execute_query(['workspace_id' => $workspace->get_id()]);

        self::assertTrue($interactor->can_manage());
        self::assertTrue($interactor->is_owner());
        self::assertFalse($interactor->can_administrate());
        self::assertTrue($interactor->is_joined());
        self::assertTrue($interactor->can_update());
        self::assertTrue($interactor->can_delete());
        self::assertTrue($interactor->can_add_members());
        self::assertTrue($interactor->can_invite());
        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_request_to_join());
        self::assertTrue($interactor->can_remove_members());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_members());
        self::assertTrue($interactor->can_share_resources());
        self::assertTrue($interactor->can_unshare_resources());
    }

    /**
     * @return void
     */
    public function test_workspace_interactor_by_admin(): void {
        $this->setup_user();
        $workspace = $this->create_workspace();

        $this->setAdminUser();
        $interactor = $this->execute_query(['workspace_id' => $workspace->get_id()]);

        self::assertTrue($interactor->can_manage());
        self::assertFalse($interactor->is_owner());
        self::assertTrue($interactor->can_administrate());
        self::assertFalse($interactor->is_joined());
        self::assertTrue($interactor->can_update());
        self::assertTrue($interactor->can_delete());
        self::assertTrue($interactor->can_add_members());
        self::assertTrue($interactor->can_invite());
        self::assertTrue($interactor->can_join());
        self::assertTrue($interactor->can_remove_members());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_members());
        self::assertTrue($interactor->can_share_resources());
        self::assertTrue($interactor->can_unshare_resources());
    }

    /**
     * @return void
     */
    public function test_workspace_interactor_by_member(): void {
        $this->setup_user();
        $workspace = $this->create_workspace();
        $member = $this->getDataGenerator()->create_user();

        \container_workspace\member\member::added_to_workspace($workspace, $member->id);
        $this->setUser($member);
        $interactor = $this->execute_query(['workspace_id' => $workspace->get_id()]);
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_administrate());
        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_remove_members());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_members());
        self::assertTrue($interactor->can_share_resources());
        self::assertFalse($interactor->can_unshare_resources());
    }

    /**
     * @return void
     */
    public function test_workspace_interactor_by_non_member(): void {
        $this->setup_user();
        $workspace = $this->create_workspace();
        $visitor = $this->getDataGenerator()->create_user();
        $this->setUser($visitor);
        $interactor = $this->execute_query(['workspace_id' => $workspace->get_id()]);

        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
        self::assertTrue($interactor->can_join());
        self::assertFalse($interactor->can_remove_members());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_unshare_resources());
    }

    /**
     * @return void
     */
    public function test_check_if_user_can_add_audiences(): void {
        $user = $this->setup_user();
        $workspace = $this->create_workspace();

        // Admins always should be able to add audiences
        $this->setAdminUser();

        $interactor = $this->execute_query(['workspace_id' => $workspace->get_id()]);
        $this->assertTrue($interactor->can_add_audiences());

        $this->setUser($user);

        $interactor = $this->execute_query(['workspace_id' => $workspace->get_id()]);
        $this->assertFalse($interactor->can_add_audiences());

        // Now give the user the capability
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $user_role->id, SYSCONTEXTID);

        $interactor = $this->execute_query(['workspace_id' => $workspace->get_id()]);
        $this->assertTrue($interactor->can_add_audiences());
    }

    /**
     * @param array $args
     * @return mixed|null
     */
    private function execute_query(array $args = []) {
        return $this->resolve_graphql_query('container_workspace_workspace_interactor', $args);
    }

    /**
     * @return array|stdClass|null
     */
    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        return $user;
    }

    /**
     * @return workspace
     */
    private function create_workspace(): workspace {
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');
        return $workspace_generator->create_workspace();
    }
}