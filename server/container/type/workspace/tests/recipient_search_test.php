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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package container_workspace
 */

use container_workspace\member\member;
use container_workspace\totara_engage\share\recipient\library;
use totara_tenant\local\util;

class totara_engage_workspace_search_testcase extends advanced_testcase {

    public function test_search_workspaces() {
        $this->setAdminUser();

        $owner = $this->getDataGenerator()->create_user();
        $member = $this->getDataGenerator()->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');

        $priv_workspace_member = $workspace_generator->create_private_workspace(
            'private workspace member',
            'description',
            null,
            $owner->id
        );
        $priv_hidden_workspace_member1 = $workspace_generator->create_hidden_workspace(
            'private hidden workspace member',
            'description',
            null,
            $owner->id
        );
        $priv_workspace_non_member = $workspace_generator->create_private_workspace(
            'private workspace w/o member',
            'description',
            null,
            $owner->id
        );

        $pub_workspace_member = $workspace_generator->create_workspace(
            'public workspace member',
            'description',
            null,
            $owner->id
        );
        $pub_workspace_non_member = $workspace_generator->create_workspace(
            'public workspace w/o member',
            'description',
            null,
            $owner->id
        );

        member::added_to_workspace($priv_workspace_member, $member->id, false);
        member::added_to_workspace($priv_hidden_workspace_member1, $member->id, false);
        member::added_to_workspace($pub_workspace_member, $member->id, false);

        // Admin user is not a member so shouldn't find anything
        $result = library::search('', null);
        $this->assertCount(0, $result);

        $this->setUser($member);

        $result = library::search('', null);
        $this->assertCount(3, $result);

        $recipient_ids = [];
        foreach ($result as $recipient) {
            $recipient_ids[] = $recipient->get_id();
        }

        $this->assertEqualsCanonicalizing(
            [
                $priv_workspace_member->get_id(),
                $priv_hidden_workspace_member1->get_id(),
                $pub_workspace_member->get_id()
            ],
            $recipient_ids
        );

        // Now unenrol user from workspace
        $member = member::from_user($member->id, $pub_workspace_member->get_id());
        $member->leave();

        $result = library::search('', null);
        $this->assertCount(2, $result);

        $recipient_ids = [];
        foreach ($result as $recipient) {
            $recipient_ids[] = $recipient->get_id();
        }

        $this->assertEqualsCanonicalizing(
            [
                $priv_workspace_member->get_id(),
                $priv_hidden_workspace_member1->get_id(),
            ],
            $recipient_ids
        );
    }

    public function test_search_workspaces_with_multi_tenancy() {
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = \totara_tenant\testing\generator::instance();
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $systemuser1 = $this->getDataGenerator()->create_user();
        $systemuser2 = $this->getDataGenerator()->create_user();
        util::add_other_participant($tenant1->id, $systemuser1->id);
        util::add_other_participant($tenant2->id, $systemuser1->id);

        $this->setAdminUser();

        $system_workspace1 = $workspace_generator->create_private_workspace(
            'system private workspace member',
            'description',
            null,
            $systemuser1->id
        );
        $system_workspace2 = $workspace_generator->create_private_workspace(
            'system private workspace member',
            'description',
            null,
            $systemuser2->id
        );
        member::added_to_workspace($system_workspace2, $systemuser1->id, false);

        $owner1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $member1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $owner2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $member2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $this->setUser($owner1);

        $priv_workspace_member1 = $workspace_generator->create_private_workspace(
            'private workspace member',
            'description',
            null,
            $owner1->id
        );
        $priv_hidden_workspace_member1 = $workspace_generator->create_hidden_workspace(
            'private hidden workspace member',
            'description',
            null,
            $owner1->id
        );
        $priv_workspace_non_member1 = $workspace_generator->create_private_workspace(
            'private workspace w/o member',
            'description',
            null,
            $owner1->id
        );

        $pub_workspace_member1 = $workspace_generator->create_workspace(
            'public workspace member',
            'description',
            null,
            $owner1->id
        );
        $pub_workspace_non_member1 = $workspace_generator->create_workspace(
            'public workspace w/o member',
            'description',
            null,
            $owner1->id
        );

        member::added_to_workspace($priv_workspace_member1, $member1->id, false);
        member::added_to_workspace($priv_hidden_workspace_member1, $member1->id, false);
        member::added_to_workspace($pub_workspace_member1, $member1->id, false);
        member::added_to_workspace($pub_workspace_member1, $systemuser1->id, false);

        $this->setUser($owner2);

        $priv_workspace_member2 = $workspace_generator->create_private_workspace(
            'private workspace member',
            'description',
            null,
            $owner2->id
        );
        $priv_workspace_non_member2 = $workspace_generator->create_private_workspace(
            'private workspace w/o member',
            'description',
            null,
            $owner2->id
        );

        $pub_workspace_member2 = $workspace_generator->create_workspace(
            'public workspace member',
            'description',
            null,
            $owner2->id
        );
        $pub_workspace_non_member2 = $workspace_generator->create_workspace(
            'public workspace w/o member',
            'description',
            null,
            $owner2->id
        );

        member::added_to_workspace($priv_workspace_member2, $systemuser1->id, false);
        member::added_to_workspace($priv_workspace_member2, $member2->id, false);
        member::added_to_workspace($pub_workspace_member2, $member2->id, false);

        $this->setUser($member1);

        $result = library::search('', null);
        $this->assertCount(3, $result);

        $recipient_ids = [];
        foreach ($result as $recipient) {
            $recipient_ids[] = $recipient->get_id();
        }

        $this->assertEqualsCanonicalizing(
            [
                $priv_hidden_workspace_member1->get_id(),
                $priv_workspace_member1->get_id(),
                $pub_workspace_member1->get_id()
            ],
            $recipient_ids
        );

        // Now as a system user
        $this->setUser($systemuser1);

        $result = library::search('', null);
        $this->assertCount(4, $result);

        $recipient_ids = [];
        foreach ($result as $recipient) {
            $recipient_ids[] = $recipient->get_id();
        }

        $this->assertEqualsCanonicalizing(
            [
                $system_workspace1->get_id(),
                $system_workspace2->get_id(),
                $priv_workspace_member2->get_id(),
                $pub_workspace_member1->get_id(),
            ],
            $recipient_ids
        );
    }

    public function test_search_workspaces_when_workspace_deleted(): void {
        $owner = $this->getDataGenerator()->create_user();
        self::setUser($owner);
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');

        $workspace1 = $workspace_generator->create_workspace(
            'public workspace member',
            'description',
            null,
            $owner->id
        );

        $workspace2 = $workspace_generator->create_workspace(
            'public workspace member',
            'description',
            null,
            $owner->id
        );

        $result = library::search('', null);
        $this->assertCount(2, $result);

        $workspace2->delete();

        $result = library::search('', null);
        $this->assertCount(1, $result);
    }

}