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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use core_phpunit\testcase;
use ml_recommender\loader\recommended_item\articles_loader;
use ml_recommender\local\environment;
use ml_recommender\recommendations;
use totara_core\advanced_feature;
use totara_engage\access\access;
use totara_webapi\graphql;

/**
 * test the endpoints for recommending articles by article or user from the ML service
 */
class ml_recommender_get_service_recommended_articles_testcase extends testcase {
    /**
     * Test articles are recommended by article id
     */
    public function test_recommended_articles_graphql() {
        $generator = $this->getDataGenerator();
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');

        $this->setAdminUser();
        $topic = $topic_generator->create_topic();

        $user = $generator->create_user();
        $this->setUser($user);

        // This is our target article. We're going to ask for recommendations related to this article
        $target_article = $article_generator->create_article([
            'name' => 'Target Article',
            'userid' => $user->id,
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()],
        ]);
        $target_article2 = $article_generator->create_article([
            'name' => 'Target Article',
            'userid' => $user->id,
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()],
        ]);

        // Going to create a few articles, then recommend *some* of them
        $recommended = [];
        for ($i = 1; $i <= 10; $i++) {
            $article = $article_generator->create_article([
                'name' => 'A' . $i,
                'userid' => $user->id,
                'access' => access::PUBLIC,
                'topics' => [$topic->get_id()],
            ]);

            // Recommend it if it's > 5
            if ($i > 5) {
                $recommended[] = $article->get_id();
            }
        }
        $this->set_recommended_data($recommended);

        // Now we're going to ask for some recommended articles
        advanced_feature::enable('ml_recommender');
        $ec = execution_context::create('ajax', 'ml_recommender_get_recommended_articles');
        $parameters = [
            'article_id' => $target_article->get_id(),
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        $cursor = $result->data['cursor'];
        $results = $result->data['articles'];

        $this->assertEquals(5, $cursor['total']);
        $this->assertCount(environment::get_related_items_count(), $results);

        // Quick check
        $expected = ['A5', 'A6', 'A7', 'A8', 'A9', 'A10'];
        foreach ($results as $result) {
            $this->assertTrue(in_array($result['name'], $expected));
        }

        // Now check for no results
        $this->set_recommended_data([]);
        $parameters = [
            'article_id' => $target_article2->get_id(),
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        $cursor = $result->data['cursor'];
        $results = $result->data['articles'];

        $this->assertEquals(0, $cursor['total']);
        $this->assertCount(0, $results);

        // Disable the feature & make sure we get no results back
        advanced_feature::disable('ml_recommender');
        $ec = execution_context::create('ajax', 'ml_recommender_get_recommended_articles');
        $parameters = [
            'article_id' => $target_article->get_id(),
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        $cursor = $result->data['cursor'];
        $results = $result->data['articles'];

        $this->assertNull($cursor);
        $this->assertEmpty($results);
    }

    /**
     * Verify that we cannot be recommended articles belonging to another tenant
     */
    public function test_recommended_articles_graphql_tenancy() {
        $generator = $this->getDataGenerator();
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        advanced_feature::enable('ml_recommender');
        advanced_feature::enable('engage_resources');

        $this->setAdminUser();
        $topic = $topic_generator->create_topic();

        // User 1 & 2 belong to tenant 1, 3 & 4 belong to tenant 2
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $tenant_generator->migrate_user_to_tenant($user1->id, $tenant1->id);
        $tenant_generator->migrate_user_to_tenant($user2->id, $tenant1->id);
        $tenant_generator->migrate_user_to_tenant($user3->id, $tenant2->id);
        $tenant_generator->migrate_user_to_tenant($user4->id, $tenant2->id);

        // Create an article in tenant1
        $this->setUser($user1);
        $base_article = $article_generator->create_article([
            'userid' => $user1->id,
            'access' => access::PUBLIC,
            'name' => 'Tenant 1 Article',
            'topics' => [$topic->get_id()],
        ]);

        // Create an article in tenant 1 & tenant 2, and recommend both to base article
        $tenant1_article = $article_generator->create_article([
            'userid' => $user1->id,
            'access' => access::PUBLIC,
            'name' => 'Tenant 1 Recommended',
            'topics' => [$topic->get_id()],
        ]);

        $this->setUser($user3);
        $tenant2_article = $article_generator->create_article([
            'userid' => $user3->id,
            'access' => access::PUBLIC,
            'name' => 'Tenant 2 Recommended',
            'topics' => [$topic->get_id()],
        ]);

        $this->setAdminUser();
        $recommended = [];
        foreach ([$tenant1_article, $tenant2_article] as $article) {
            $recommended[] = $article->get_id();
        }
        $this->set_recommended_data($recommended);

        // Make sure users can see profiles (make it global)
        $roles = get_archetype_roles('user');
        foreach ($roles as $role) {
            assign_capability('moodle/user:viewdetails', CAP_ALLOW, $role->id, context_system::instance(), true);
        }

        // User 2 should be able to see the recommended article
        $this->setUser($user2);
        $ec = execution_context::create('ajax', 'ml_recommender_get_recommended_articles');
        $parameters = [
            'article_id' => $base_article->get_id(),
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);
        $this->assertIsArray($result->data['articles']);
        $this->assertCount(1, $result->data['articles']);

        $article = current($result->data['articles']);
        $this->assertSame('Tenant 1 Recommended', $article['name']);

        // User 4 should see nothing
        $this->setUser($user4);
        $ec = execution_context::create('ajax', 'ml_recommender_get_recommended_articles');
        $parameters = [
            'article_id' => $base_article->get_id(),
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->errors);
        $this->assertIsArray($result->errors);
        $this->assertEmpty($result->data['articles']);
    }

    /**
     * Enable ML
     */
    protected function setUp(): void {
        global $CFG;
        $CFG->ml_service_key = 'abc';
        $CFG->ml_service_url = 'http://example.com:5000';
    }

    /**
     * Cleanup
     */
    protected function tearDown(): void {
        $this->set_recommended_data(null);
    }

    /**
     * @param array|null $data
     */
    protected function set_recommended_data(?array $data): void {
        $mock_helper = null;
        if (null !== $data) {
            $mock_helper = $this->createMock(recommendations::class);
            $mock_helper
                ->method('get_similar_items')
                ->willReturn($data);
        }

        $reflection = new ReflectionProperty(articles_loader::class, 'recommendations_helper');
        $reflection->setAccessible(true);
        $reflection->setValue($mock_helper);
        $reflection->setAccessible(false);
    }
}