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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use core_user\totara_engage\share\recipient\user;
use totara_webapi\graphql;
use core\webapi\execution_context;
use totara_engage\answer\answer_type;

class totara_playlist_multi_tenancy_resources_testcase extends advanced_testcase {
    /**
     * Test cases to assure that user moving between tenants will cause the resources to
     * disappear from the collection.
     *
     * @return void
     */
    public function test_user_cannot_see_resources_from_ex_tenants_in_playlist(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        $topic_ids = [];
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        for ($i = 0; $i < 2; $i++) {
            $topic = $topic_generator->create_topic();
            $topic_ids[] = $topic->get_id();
        }

        // Log in as user one and created a share playlist to user two.
        $this->setUser($user_one);
        $user_two_recipient = new user($user_two->id);

        $playlist = $playlist_generator->create_playlist([
            'access' => access::RESTRICTED,
            'topics' => $topic_ids
        ]);

        $playlist_generator->share_playlist($playlist, [$user_two_recipient]);

        // Log in as user two and created several resources then share to the playlist.
        $this->setUser($user_two);
        for ($i = 0; $i < 5; $i++) {
            $article = $article_generator->create_article([
                'access' => access::PUBLIC,
                'topics' => $topic_ids
            ]);

            // Contribute as user's one, as for now we don't have contribution by multi users.
            $playlist->add_resource($article, $user_one->id);
        }

        // Log in as user one and start fetching the resources - via graphql.
        $this->setUser($user_one);

        $ec = execution_context::create('ajax', 'totara_playlist_cards');
        $result = graphql::execute_operation(
            $ec,
            [
                'id' => $playlist->get_id(),
                'include_footnotes' => true,
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('contribution', $result->data);
        $this->assertNotEmpty($result->data['contribution']);

        $this->assertArrayHasKey('cards', $result->data['contribution']);
        $this->assertCount(5, $result->data['contribution']['cards']);

        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Check if user one is still able to see the resources or not. Which should not be.
        $this->setUser($user_one);
        $result = graphql::execute_operation(
            $ec,
            [
                'id' => $playlist->get_id(),
                'include_footnotes' => true,
                'theme' => 'ventura',
            ]
        );
        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertNotEmpty($result->data['contribution']);
        $this->assertArrayHasKey('contribution', $result->data);

        $this->assertArrayHasKey('cards', $result->data['contribution']);
        $this->assertEmpty($result->data['contribution']['cards']);
    }

    /**
     * Test case to assure that a tenant member should not be able to see another resources from
     * other's user within different tenant.
     *
     * @return void
     */
    public function test_fetching_resources_not_added(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setAdminUser();
        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic = $topic_generator->create_topic();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        // Log in as first user, then start creating the playlist
        $this->setUser($user_one);
        $playlist = $playlist_generator->create_playlist([
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()]
        ]);

        // Login as user two and start create several resources.
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $this->setUser($user_two);
        $user_recipient = new user($user_one->id);

        for ($i = 0; $i < 5; $i++) {
            $article = $article_generator->create_article([
                'access' => access::PUBLIC,
                'topics' => [$topic->get_id()]
            ]);

            $article_generator->share_article($article, [$user_recipient]);
        }

        // Login as user one and check if the user is able to fetch all those articles
        // from the user two or not.
        $this->setUser($user_one);

        $ec = execution_context::create('ajax', 'totara_playlist_resources');
        $same_result = graphql::execute_operation(
            $ec,
            [
                'area' => 'adder',
                'playlist_id' => $playlist->get_id(),
                'include_footnotes' => false,
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($same_result->errors);
        $this->assertNotEmpty($same_result->data);

        $this->assertArrayHasKey('resources', $same_result->data);
        $this->assertArrayHasKey('cards', $same_result->data['resources']);
        $this->assertCount(5, $same_result->data['resources']['cards']);

        // Moving user two to second tenant and check that if user one is still able to
        // see the resources or not.
        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        $different_tenant_result = graphql::execute_operation(
            $ec,
            [
                'area' => 'adder',
                'playlist_id' => $playlist->get_id(),
                'include_footnotes' => false,
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($different_tenant_result->errors);
        $this->assertNotEmpty($different_tenant_result->data);

        $this->assertArrayHasKey('resources', $different_tenant_result->data);
        $this->assertArrayHasKey('cards', $different_tenant_result->data['resources']);
        $this->assertEmpty($different_tenant_result->data['resources']['cards']);
    }

    /**
     * Test case to assure that a tenant pariticipant is able to see all the resources from tenant members
     * and the system level users.
     *
     * @return void
     */
    public function test_fetching_resources_not_added_by_participant(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();
        $user_four = $generator->create_user();

        $this->setAdminUser();
        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic = $topic_generator->create_topic();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);
        $tenant_generator->migrate_user_to_tenant($user_three->id, $tenant->id);
        $tenant_generator->set_user_participation($user_one->id, [$tenant->id]);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');

        // Log in as first user, then start creating the playlist
        $this->setUser($user_one);
        $playlist = $playlist_generator->create_playlist([
            'access' => access::PUBLIC,
            'topics' => [$topic->get_id()]
        ]);

        $user_one_recipient = new user($user_one->id);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');

        // Login as second user, and start creating the surveys
        $this->setUser($user_two);
        for ($i = 0; $i < 5; $i++) {
            $survey = $survey_generator->create_survey(
                null,
                [],
                answer_type::MULTI_CHOICE,
                [
                    'access' => access::PUBLIC,
                    'topics' => [$topic->get_id()]
                ]
            );

            $survey_generator->share_survey($survey, [$user_one_recipient]);
        }

        // Log in as third user and start creating resources
        $this->setUser($user_three);
        for ($i = 0; $i < 5; $i++) {
            $article = $article_generator->create_article([
                'access' => access::PUBLIC,
                'topics' => [$topic->get_id()]
            ]);

            $article_generator->share_article($article, [$user_one_recipient]);
        }

        // Login as fourth user and start creating resources
        $this->setUser($user_four);
        for ($i = 0; $i < 5; $i++) {
            $article = $article_generator->create_article([
                'access' => access::PUBLIC,
                'topics' => [$topic->get_id()]
            ]);

            $article_generator->share_article($article, [$user_one_recipient]);
        }

        // Login as user one and check if user is able to fetch all the resources above.
        $this->setUser($user_one);

        $ec = execution_context::create('ajax', 'totara_playlist_resources');
        $result = graphql::execute_operation(
            $ec,
            [
                'area' => 'adder',
                'playlist_id' => $playlist->get_id(),
                'include_footnotes' => false,
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('resources', $result->data);
        $this->assertNotEmpty($result->data['resources']);
        $this->assertArrayHasKey('cards', $result->data['resources']);
        $this->assertCount(15, $result->data['resources']['cards']);
    }
}