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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use core\entity\tenant;
use totara_engage\access\access;
use totara_engage\access\access_manager;
use totara_playlist\playlist;

class totara_engage_access_manager_testcase extends advanced_testcase {

    public function test_can_manage_engage() {
        // Two users
        $generator = $this->getDataGenerator();
        $owner = $generator->create_user();
        $other = $generator->create_user();

        // Create engage instance by first user
        $this->setUser($owner);
        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $generator->get_plugin_generator('engage_article');
        $context = $articlegen->create_article(['access' => access::PUBLIC])->get_context();

        // Confirm no capability
        $this->assertFalse(access_manager::can_manage_engage($context, $other->id));
        $this->setUser($other);
        $this->assertFalse(access_manager::can_manage_engage($context));

        // Assign capability
        $syscontext = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $roleid, $syscontext);
        role_assign($roleid, $other->id, $syscontext);

        // Check capability
        $this->setUser($owner);
        $this->assertTrue(access_manager::can_manage_engage($context, $other->id));
        $this->setUser($other);
        $this->assertTrue(access_manager::can_manage_engage($context));
    }

    public function test_can_manage_engage_multi_tenant() {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        $tenant1 = new tenant($tenant_generator->create_tenant());
        $tenant2 = new tenant($tenant_generator->create_tenant());

        $u1t1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $u2t1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $u1t2 = $generator->create_user(['tenantid' => $tenant2->id]);

        // Make u2t1 and u1t2 tenant managers
        $tenant1_context = context_tenant::instance($tenant1->id);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $roleid, $tenant1_context);
        role_assign($roleid, $u2t1->id, $tenant1_context);

        $tenant2_context = context_tenant::instance($tenant2->id);
        assign_capability('totara/engage:manage', CAP_ALLOW, $roleid, $tenant2_context);
        role_assign($roleid, $u1t2->id, $tenant2_context);

        $this->setUser($u1t1);
        $context = playlist::create('Hello world', access::PUBLIC)->get_context();

        $this->assertFalse(access_manager::can_manage_engage($context, $u1t1->id));
        $this->assertTrue(access_manager::can_manage_engage($context, $u2t1->id));
        $this->assertFalse(access_manager::can_manage_engage($context, $u1t2->id));

        $this->setUser($u2t1);
        $this->assertTrue(access_manager::can_manage_engage($context));
        $this->assertFalse(access_manager::can_manage_engage($context, $u1t2->id));

        $this->setUser($u1t2);
        $this->assertFalse(access_manager::can_manage_engage($context));

    }

    public function test_can_manage_tenant_participants() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->assertFalse(access_manager::can_manage_tenant_participants($user->id));

        // Assign capability
        $syscontext = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('totara/tenant:manageparticipants', CAP_ALLOW, $roleid, $syscontext);
        role_assign($roleid, $user->id, $syscontext);

        $this->assertTrue(access_manager::can_manage_tenant_participants($user->id));

        $this->setUser($user);
        $this->assertTrue(access_manager::can_manage_tenant_participants());
    }

    public function test_can_access() {
        $generator = $this->getDataGenerator();
        $owner = $generator->create_user();
        $shared = $generator->create_user();
        $manager = $generator->create_user();
        $prohibited = $generator->create_user();
        $other = $generator->create_user();


        $this->setUser($owner);
        /** @var \engage_article\testing\generator $articlegen */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $shared_article = $article_generator->create_article(['access' => access::RESTRICTED]);
        $private_article = $article_generator->create_article(['access' => access::PRIVATE]);
        $public_article = $article_generator->create_article(['access' => access::PUBLIC]);

        // Shared
        $recipients = $article_generator->create_user_recipients([$shared]);
        $article_generator->share_article($shared_article, $recipients);

        // Manager
        $syscontext = context_system::instance();
        $managerrole = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $managerrole, $syscontext);
        role_assign($managerrole, $manager->id, $syscontext);

        // Prohibited
        $syscontext = context_system::instance();
        $prohibitrole = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:viewlibrary', CAP_PROHIBIT, $prohibitrole, $syscontext);
        role_assign($prohibitrole, $prohibited->id, $syscontext);

        // Capability totara/engage:viewlibrary is a must for access anything
        $this->assertFalse(access_manager::can_access($shared_article, $prohibited->id));
        $this->assertFalse(access_manager::can_access($private_article, $prohibited->id));
        $this->assertFalse(access_manager::can_access($public_article, $prohibited->id));

        // Check that second parameter applied consistently.
        $this->setUser($prohibited);

        // Owner has access
        $this->assertTrue(access_manager::can_access($shared_article, $owner->id));
        $this->assertTrue(access_manager::can_access($private_article, $owner->id));
        $this->assertTrue(access_manager::can_access($public_article, $owner->id));

        // Capability totara/engage:manage has access
        $this->assertTrue(access_manager::can_access($shared_article, $manager->id));
        $this->assertTrue(access_manager::can_access($private_article, $manager->id));
        $this->assertTrue(access_manager::can_access($public_article, $manager->id));

        // If private (and not owner and no capability - no access)
        $this->assertFalse(access_manager::can_access($shared_article, $other->id));
        $this->assertFalse(access_manager::can_access($private_article, $other->id));
        $this->assertTrue(access_manager::can_access($public_article, $other->id));

        // Shared has access recipient
        $this->assertTrue(access_manager::can_access($shared_article, $shared->id));
        $this->assertFalse(access_manager::can_access($private_article, $shared->id));
        $this->assertTrue(access_manager::can_access($public_article, $shared->id));

        // Check that second parameter applied consistently.
        $this->setUser($manager);
        $this->assertTrue(access_manager::can_access($shared_article));
        $this->assertTrue(access_manager::can_access($private_article));
        $this->assertTrue(access_manager::can_access($public_article));

    }

    public function test_can_access_multi_tenant() {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        $tenant1 = new tenant($tenant_generator->create_tenant());
        $tenant2 = new tenant($tenant_generator->create_tenant());

        $owner = $generator->create_user(['tenantid' => $tenant1->id]);
        $shared = $generator->create_user(['tenantid' => $tenant1->id]);
        $manager = $generator->create_user(['tenantid' => $tenant1->id]);
        $other = $generator->create_user(['tenantid' => $tenant1->id]);

        $shared2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $manager2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $other2 = $generator->create_user(['tenantid' => $tenant2->id]);

        $this->setUser($owner);
        /** @var \engage_article\testing\generator $articlegen */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $shared_article = $article_generator->create_article(['access' => access::RESTRICTED]);
        $private_article = $article_generator->create_article(['access' => access::PRIVATE]);
        $public_article = $article_generator->create_article(['access' => access::PUBLIC]);

        // Shared
        $recipients = $article_generator->create_user_recipients([$shared, $shared2]);
        $article_generator->share_article($shared_article, $recipients);

        // Managers
        $tenant1_context = context_tenant::instance($tenant1->id);
        $managerrole = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $managerrole, $tenant1_context);
        role_assign($managerrole, $manager->id, $tenant1_context);

        $tenant2_context = context_tenant::instance($tenant2->id);
        $managerrole2 = $this->getDataGenerator()->create_role();
        assign_capability('totara/engage:manage', CAP_ALLOW, $managerrole2, $tenant2_context);
        role_assign($managerrole2, $manager2->id, $tenant2_context);

        // Owner has access
        $this->assertTrue(access_manager::can_access($shared_article, $owner->id));
        $this->assertTrue(access_manager::can_access($private_article, $owner->id));
        $this->assertTrue(access_manager::can_access($public_article, $owner->id));

        // Capability totara/engage:manage has access (within tenant
        $this->assertTrue(access_manager::can_access($shared_article, $manager->id));
        $this->assertTrue(access_manager::can_access($private_article, $manager->id));
        $this->assertTrue(access_manager::can_access($public_article, $manager->id));

        $this->assertFalse(access_manager::can_access($shared_article, $manager2->id));
        $this->assertFalse(access_manager::can_access($private_article, $manager2->id));
        $this->assertFalse(access_manager::can_access($public_article, $manager2->id));

        // If private (and not owner and no capability - no access)
        $this->assertFalse(access_manager::can_access($shared_article, $other->id));
        $this->assertFalse(access_manager::can_access($private_article, $other->id));
        $this->assertTrue(access_manager::can_access($public_article, $other->id));

        $this->assertFalse(access_manager::can_access($shared_article, $other2->id));
        $this->assertFalse(access_manager::can_access($private_article, $other2->id));
        $this->assertFalse(access_manager::can_access($public_article, $other2->id));

        // Shared has access recipient
        $this->assertTrue(access_manager::can_access($shared_article, $shared->id));
        $this->assertFalse(access_manager::can_access($private_article, $shared->id));
        $this->assertTrue(access_manager::can_access($public_article, $shared->id));

        $this->assertFalse(access_manager::can_access($shared_article, $shared2->id));
        $this->assertFalse(access_manager::can_access($private_article, $shared2->id));
        $this->assertFalse(access_manager::can_access($public_article, $shared2->id));

        // Check that second argument applies consitently
        $this->setUser($shared);
        $this->assertTrue(access_manager::can_access($shared_article));
        $this->assertFalse(access_manager::can_access($private_article));
        $this->assertTrue(access_manager::can_access($public_article));

        $this->setUser($shared2);
        $this->assertFalse(access_manager::can_access($shared_article));
        $this->assertFalse(access_manager::can_access($private_article));
        $this->assertFalse(access_manager::can_access($public_article));
    }

    public function test_has_shared_access() {
        // Three users: owner, no shared, shared
        $generator = $this->getDataGenerator();
        $owner = $generator->create_user();
        $notshared = $generator->create_user();
        $shared = $generator->create_user();

        // Create engage instance by the owner and share with the user
        $this->setUser($owner);
        /** @var \engage_article\testing\generator $articlegen */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['access' => access::RESTRICTED]);
        $recipients = $article_generator->create_user_recipients([$shared]);
        $article_generator->share_article($article, $recipients);

        $this->assertFalse(access_manager::has_shared_access($article, $owner->id));
        $this->assertFalse(access_manager::has_shared_access($article, $notshared->id));
        $this->assertTrue(access_manager::has_shared_access($article, $shared->id));

    }

    public function test_can_update_access() {
        $this->assertTrue(access_manager::can_update_access(access::PUBLIC, access::PUBLIC));
        $this->assertFalse(access_manager::can_update_access(access::PUBLIC, access::RESTRICTED));
        $this->assertFalse(access_manager::can_update_access(access::PUBLIC, access::PRIVATE));

        $this->assertTrue(access_manager::can_update_access(access::RESTRICTED, access::PUBLIC));
        $this->assertTrue(access_manager::can_update_access(access::RESTRICTED, access::RESTRICTED));
        $this->assertFalse(access_manager::can_update_access(access::RESTRICTED, access::PRIVATE));

        $this->assertTrue(access_manager::can_update_access(access::PRIVATE, access::PUBLIC));
        $this->assertTrue(access_manager::can_update_access(access::PRIVATE, access::RESTRICTED));
        $this->assertTrue(access_manager::can_update_access(access::PRIVATE, access::PRIVATE));
    }
}