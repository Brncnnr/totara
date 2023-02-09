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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access_manager;

class engage_article_multi_tenancy_access_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_system_level_user_access_tenant_article(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // Log in as tenant user and create a public article.
        $this->setUser($user_two);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_public_article();

        // Log in as system user to see if the system user is able to access the article or not.
        $this->setUser($user_one);
        self::assertTrue(access_manager::can_access($article, $user_one->id));

        // Set isolation mode on and check if user one is still able to see the article.
        set_config('tenantsisolated', 1);
        self::assertFalse(access_manager::can_access($article, $user_one->id));
    }

    /**
     * @return void
     */
    public function test_tenant_member_can_access_system_level_article(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        // Log in as system level user and create an article.
        $this->setUser($user_two);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_public_article();

        // Log in as tenant user and check if the tenant user is able to access the public article
        // created by system level user.
        $this->setUser($user_one);
        self::assertTrue(access_manager::can_access($article, $user_one->id));

        // Enable isolation mode and check if the tenant user is still able to access the public article
        // created by system level user.
        set_config('tenantsisolated', 1);
        self::assertFalse(access_manager::can_access($article, $user_one->id));
    }

    /**
     * @return void
     */
    public function test_participant_member_can_access_tenant_article(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);
        $tenant_generator->set_user_participation($user_one->id, [$tenant->id]);

        // Log in as tenant user and create a public article.
        $this->setUser($user_two);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_public_article();

        // Log in as user one and check if user one is able to see the public article.
        $this->setUser($user_one);
        self::assertTrue(access_manager::can_access($article, $user_one->id));

        set_config('tentantsisolated', 1);
        self::assertTrue(access_manager::can_access($article, $user_one->id));
    }

    /**
     * @return void
     */
    public function test_tenant_member_access_participant_article(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        // Log in as tenant participant and create a public article.
        $this->setUser($user_two);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_public_article();

        // Log in as tenant member and check the access of to the public article.
        $this->setUser($user_one);
        self::assertTrue(access_manager::can_access($article, $user_one->id));

        // Check with tenant isolation mode on.
        set_config('tenantsisolated', 1);
        self::assertFalse(access_manager::can_access($article, $user_one->id));
    }

    /**
     * @return void
     */
    public function test_tenant_member_cannot_see_different_tenant_article(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Log in as user one and create an article.
        $this->setUser($user_one);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_public_article();

        // Log in as second user and check if the user two is able to see the article.
        $this->setUser($user_two);
        self::assertFalse(access_manager::can_access($article, $user_two->id));

        // Check with isolation mode on.
        set_config('tenantsisolated', 1);
        self::assertFalse(access_manager::can_access($article, $user_two->id));
    }
}