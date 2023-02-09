<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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

use totara_engage\access\access;
use totara_catalog\catalog_retrieval;
use core_user\totara_engage\share\recipient\user;

class engage_article_catalog_multi_tenancy_testcase extends advanced_testcase {
    /**
     * Test to assure that one user in a tenant should not be able to see articles
     * created by other user that lives within different tenant.
     *
     * @return void
     */
    public function test_user_cannot_see_articles_from_other_tenant_user(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        $topic_ids = [];

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Adding user one/two to tenant one/two.
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        // Login as user two and start creating article.
        $this->setUser($user_two);

        for ($i = 0; $i < 5; $i++) {
            $article_generator->create_article([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }

        // Now log in as user one and check if the user one is able to see
        // any articles created by user two or not - via catalog.
        $this->setUser($user_one);

        $retrieval = new catalog_retrieval();
        $user_one_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_one_result);
        $this->assertCount(0, $user_one_result->objects);
    }

    /**
     * Test to assure when a user member is migrated to different tenant should not see the
     * articles from user's former tenant.
     *
     * @return void
     */
    public function test_migrate_tenant_member_cannot_see_articles_from_old_tenant(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        // Create topics.
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic_ids = [];

        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        // Log in as the second user, and create the articles that are shared to user one.
        $this->setUser($user_two);
        $recipient = new user($user_one->id);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        for ($i = 0; $i < 5; $i++) {
            $article = $article_generator->create_article([
                'access' => access::RESTRICTED,
                'topics' => $topic_ids
            ]);

            $article_generator->share_article($article, [$recipient]);
        }

        $this->executeAdhocTasks();

        // Log in as user one and check if the user is able to see the content or not.
        $this->setUser($user_one);

        $retrieval = new catalog_retrieval();
        $user_one_tenant_one_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_one_tenant_one_result);
        $this->assertCount(5, $user_one_tenant_one_result->objects);

        // Move the user to tenant two and check that if this user is still able to see the shared resources or not.
        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_two->id);

        $user_one_tenant_two_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_one_tenant_two_result);
        $this->assertEmpty($user_one_tenant_two_result->objects);
    }

    /**
     * Test to assure that system level users can see each other content.
     *
     * @return void
     */
    public function test_system_level_users_can_see_each_other_articles(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $topic_ids = [];
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        // Login as first user and start creating articles.
        $this->setUser($user_one);
        for ($i = 0; $i < 5; $i++) {
            $article_generator->create_article([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }

        // Login as second user and start creating articles
        $this->setUser($user_two);
        for ($i = 0; $i < 5; $i++) {
            $article_generator->create_article([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }

        // Create user three and assign it to a tenant so that we can be sure that the query will not include
        // some tenant's resources.
        $tenant = $tenant_generator->create_tenant();
        $user_three = $generator->create_user();

        $tenant_generator->migrate_user_to_tenant($user_three->id, $tenant->id);

        // Log in as user three and start creating articles.
        $this->setUser($user_three);
        for ($i = 0; $i < 5; $i++) {
            $article_generator->create_article([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);
        }

        $retrieval = new catalog_retrieval();

        // Login as user one and start checking if this user is able to see user's two articles
        $this->setUser($user_one);
        $user_one_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_one_result);

        // This include self articles
        $this->assertCount(10, $user_one_result->objects);

        // Log in as user two and start checking if this user is able to see user's one articles.
        $this->setUser($user_two);
        $user_two_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_two_result);

        // This includes self articles
        $this->assertCount(10, $user_two_result->objects);

        // Login as user three and check that if this user three is able to see system level articles.
        $this->setUser($user_three);
        $user_three_result = $retrieval->get_page_of_objects(50, 0);

        $this->assertObjectHasAttribute('objects', $user_three_result);

        // Including self articles + 10 of the articles from system level users.
        $this->assertCount(15, $user_three_result->objects);
    }
}