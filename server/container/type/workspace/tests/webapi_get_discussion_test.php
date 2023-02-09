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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\discussion\discussion;
use container_workspace\member\member;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_get_discussion_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    public function setUp(): void {
        advanced_feature::enable('container_workspace');
    }

    /**
     * Validate that the owner can access discussions in their own workspace
     */
    public function test_owner_sees_discussions(): void {
        $owner = $this->getDataGenerator()->create_user();
        $this->setUser($owner);
        $generated = $this->make_workspaces();
        $discussions = $generated['discussions'];

        $this->assert_can_see_discussion(
            $discussions['public']['id'],
            $discussions['public']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['private']['id'],
            $discussions['private']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['hidden']['id'],
            $discussions['hidden']['content']
        );
    }

    /**
     * Validate that a member can access discussions in workspaces they've joined
     */
    public function test_member_sees_discussions(): void {
        $owner = $this->getDataGenerator()->create_user();
        $this->setUser($owner);
        $generated = $this->make_workspaces();
        $workspaces = $generated['workspaces'];
        $discussions = $generated['discussions'];

        $user = $this->getDataGenerator()->create_user();

        // Check they can only see public discussions
        $this->setUser($user);
        $this->assert_can_see_discussion(
            $discussions['public']['id'],
            $discussions['public']['content']
        );
        $this->assert_cannot_see_discussion(
            $discussions['private']['id']
        );
        $this->assert_cannot_see_discussion(
            $discussions['hidden']['id']
        );

        // Make sure the user is a member of each workspace.
        member::added_to_workspace($workspaces['public'], $user->id, false, $owner->id);
        member::added_to_workspace($workspaces['private'], $user->id, false, $owner->id);
        member::added_to_workspace($workspaces['hidden'], $user->id, false, $owner->id);

        $this->setUser($user);

        $this->assert_can_see_discussion(
            $discussions['public']['id'],
            $discussions['public']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['private']['id'],
            $discussions['private']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['hidden']['id'],
            $discussions['hidden']['content']
        );
    }

    /**
     * Validate that users in different tenancies cannot see discussions from
     * another tenancy.
     */
    public function test_tenancy_discussion_restrictions(): void {
        $this->tenant_generator()->enable_tenants();

        $owner = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $tenant1 = $this->tenant_generator()->create_tenant();
        $tenant2 = $this->tenant_generator()->create_tenant();

        $this->tenant_generator()->migrate_user_to_tenant($owner->id, $tenant1->id);
        $owner->tenantid = $tenant1->id;
        $this->tenant_generator()->migrate_user_to_tenant($user1->id, $tenant1->id);
        $user1->tenantid = $tenant1->id;
        $this->tenant_generator()->migrate_user_to_tenant($user2->id, $tenant2->id);
        $user2->tenantid = $tenant2->id;

        $this->setUser($owner);
        $generated = $this->make_workspaces();
        $discussions = $generated['discussions'];
        $workspaces = $generated['workspaces'];

        // Assert that the owner can see their workspace discussions
        $this->assert_can_see_discussion(
            $discussions['public']['id'],
            $discussions['public']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['private']['id'],
            $discussions['private']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['hidden']['id'],
            $discussions['hidden']['content']
        );

        // Assert the same-tenant non-member can see public discussions
        $this->setUser($user1);
        $this->assert_can_see_discussion(
            $discussions['public']['id'],
            $discussions['public']['content']
        );
        $this->assert_cannot_see_discussion(
            $discussions['private']['id']
        );
        $this->assert_cannot_see_discussion(
            $discussions['hidden']['id']
        );

        // User joins the member, can see same-tenant discussions
        member::added_to_workspace($workspaces['public'], $user1->id, false, $owner->id);
        member::added_to_workspace($workspaces['private'], $user1->id, false, $owner->id);
        member::added_to_workspace($workspaces['hidden'], $user1->id, false, $owner->id);

        $this->assert_can_see_discussion(
            $discussions['public']['id'],
            $discussions['public']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['private']['id'],
            $discussions['private']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['hidden']['id'],
            $discussions['hidden']['content']
        );

        // Assert different tenant user cannot see discussions
        $this->setUser($user2);
        $this->assert_cannot_see_discussion(
            $discussions['public']['id']
        );
        $this->assert_cannot_see_discussion(
            $discussions['private']['id']
        );
        $this->assert_cannot_see_discussion(
            $discussions['hidden']['id']
        );
    }

    /**
     * Validate users apart of two different tenants can see system level
     * discussions while isolation mode is off.
     */
    public function test_system_tenancy_discussion_restrictions(): void {
        $this->tenant_generator()->enable_tenants();
        set_config('tenantsisolated', 0);

        $owner = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $tenant1 = $this->tenant_generator()->create_tenant();
        $tenant2 = $this->tenant_generator()->create_tenant();
        $this->tenant_generator()->migrate_user_to_tenant($user1->id, $tenant1->id);
        $this->tenant_generator()->migrate_user_to_tenant($user2->id, $tenant2->id);

        // $owner is not part of any tenancy
        $this->setUser($owner);
        $generated = $this->make_workspaces();
        $discussions = $generated['discussions'];
        $workspaces = $generated['workspaces'];

        // Assert that user1 & user2 can both see public but not private/hidden discussions
        $this->setUser($user1);
        $this->assert_can_see_discussion(
            $discussions['public']['id'],
            $discussions['public']['content']
        );
        $this->assert_cannot_see_discussion(
            $discussions['private']['id']
        );
        $this->assert_cannot_see_discussion(
            $discussions['hidden']['id']
        );
        $this->setUser($user2);
        $this->assert_can_see_discussion(
            $discussions['public']['id'],
            $discussions['public']['content']
        );
        $this->assert_cannot_see_discussion(
            $discussions['private']['id']
        );
        $this->assert_cannot_see_discussion(
            $discussions['hidden']['id']
        );

        // Assert both user1 & user2 can join & now see the system level workspace discussions
        member::added_to_workspace($workspaces['public'], $user1->id, false, $owner->id);
        member::added_to_workspace($workspaces['private'], $user1->id, false, $owner->id);
        member::added_to_workspace($workspaces['hidden'], $user1->id, false, $owner->id);
        member::added_to_workspace($workspaces['public'], $user2->id, false, $owner->id);
        member::added_to_workspace($workspaces['private'], $user2->id, false, $owner->id);
        member::added_to_workspace($workspaces['hidden'], $user2->id, false, $owner->id);

        $this->setUser($user1);
        $this->assert_can_see_discussion(
            $discussions['public']['id'],
            $discussions['public']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['private']['id'],
            $discussions['private']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['hidden']['id'],
            $discussions['hidden']['content']
        );
        $this->setUser($user2);
        $this->assert_can_see_discussion(
            $discussions['public']['id'],
            $discussions['public']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['private']['id'],
            $discussions['private']['content']
        );
        $this->assert_can_see_discussion(
            $discussions['hidden']['id'],
            $discussions['hidden']['content']
        );

        // Now if we turn isolation mode on, user1 & user2 should no longer see discussions
        set_config('tenantsisolated', 1);

        $this->setUser($user1);
        $this->assert_cannot_see_discussion(
            $discussions['public']['id']
        );
        $this->assert_cannot_see_discussion(
            $discussions['private']['id']
        );
        $this->assert_cannot_see_discussion(
            $discussions['hidden']['id']
        );
        $this->setUser($user2);
        $this->assert_cannot_see_discussion(
            $discussions['public']['id']
        );
        $this->assert_cannot_see_discussion(
            $discussions['private']['id']
        );
        $this->assert_cannot_see_discussion(
            $discussions['hidden']['id']
        );
    }

    /**
     * @return \container_workspace\testing\generator
     */
    private function workspace_generator(): \container_workspace\testing\generator {
        return $this->getDataGenerator()->get_plugin_generator('container_workspace');
    }

    /**
     * @return \totara_tenant\testing\generator
     */
    private function tenant_generator(): \totara_tenant\testing\generator {
        return \totara_tenant\testing\generator::instance();
    }

    /**
     * @return array
     */
    private function make_workspaces(): array {
        $gen = $this->workspace_generator();

        $workspaces = [
            'public' => $gen->create_workspace(),
            'private' => $gen->create_private_workspace(),
            'hidden' => $gen->create_hidden_workspace(),
        ];

        $discussions = [];
        foreach (['public', 'private', 'hidden'] as $access) {
            $discussion = $gen->create_discussion($workspaces[$access]->get_id(), $access . ' discussion');
            $discussions[$access] = [
                'id' => $discussion->get_id(),
                'content' => $discussion->get_content(),
            ];
        }
        return compact('workspaces', 'discussions');
    }

    /**
     * Assert the graphql correct returns the discussion
     *
     * @param int $discussion_id
     * @param string $content
     */
    private function assert_can_see_discussion(int $discussion_id, string $content): void {
        $result = $this->resolve_graphql_query('container_workspace_discussion', [
            'id' => $discussion_id
        ]);
        $this->assertInstanceOf(discussion::class, $result);
        $this->assertSame($content, $result->get_content());
    }

    /**
     * Assert the graphql will throw the invalid access description
     *
     * @param int $discussion_id
     */
    private function assert_cannot_see_discussion(int $discussion_id): void {
        $exception = null;
        try {
            $this->resolve_graphql_query('container_workspace_discussion', [
                'id' => $discussion_id
            ]);
        } catch (moodle_exception $ex) {
            $exception = $ex;
        }
        $this->assertNotNull($exception);
        $this->assertInstanceOf(moodle_exception::class, $exception);
        $this->assertSame('Invalid access', $exception->getMessage());
    }
}
