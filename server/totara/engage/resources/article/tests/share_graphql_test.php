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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package engage_article
 */

defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use core_user\totara_engage\share\recipient\user as user_recipient;
use engage_article\totara_engage\resource\article;
use core\webapi\execution_context;
use totara_webapi\graphql;
use totara_engage\share\recipient\helper as recipient_helper;
use core\json_editor\node\paragraph;

class engage_article_share_graphql_testcase extends advanced_testcase {

    /**
     * Validate the following:
     *   1. We can share an article using the graphql query.
     */
    public function test_share_item() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        // Create users.
        $users = $articlegen->create_users(3);

        // Create article.
        $this->setUser($users[0]);
        $article = $articlegen->create_article([
            'access' => access::PUBLIC
        ]);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $articlegen->set_capabilities(CAP_ALLOW, $user->id, $article->get_context());
        }

        // Set user to someone other than the owner of the survey.
        $this->setUser($users[1]);

        // Create share via graphql.
        $ec = execution_context::create('ajax', 'totara_engage_share');
        $parameters = [
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'recipients' => [
                [
                    'instanceid' => $users[2]->id,
                    'component' => recipient_helper::get_component(user_recipient::class),
                    'area' => user_recipient::AREA
                ]
            ]
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('shares', $result->data);

        $shares = $result->data['shares'];
        $this->assertArrayHasKey('sharedbycount', $shares);
        $this->assertEquals(1, $shares['sharedbycount']);
    }

    /**
     * Validate the following:
     *   1. We can share an article during creation.
     */
    public function test_article_create() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        // Create users.
        $users = $articlegen->create_users(2);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $articlegen->set_capabilities(CAP_ALLOW, $user->id, context_system::instance());
        }

        // Set owner of article.
        $this->setUser($users[0]);

        // Create share via graphql.
        $ec = execution_context::create('ajax', 'engage_article_create_article');
        $parameters = [
            'content' => json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('Bundles of joy')]
            ]),
            'name' => 'This are tickle',
            'access' => 'RESTRICTED',
            'format' => FORMAT_JSON_EDITOR,
            'shares' => [
                [
                    'instanceid' => $users[1]->id,
                    'component' => recipient_helper::get_component(user_recipient::class),
                    'area' => user_recipient::AREA
                ]
            ]
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('article', $result->data);

        $article = $result->data['article'];
        $this->assertArrayHasKey('sharedbycount', $article);
        $this->assertEquals(0, $article['sharedbycount']);
    }

    /**
     * Validate the following:
     *   1. We can share an article during update.
     */
    public function test_article_update() {
        $this->setAdminUser();
        /** @var \totara_topic\testing\generator $topicgen */
        $topicgen = $this->getDataGenerator()->get_plugin_generator('totara_topic');
        $topics[] = $topicgen->create_topic('topic1')->get_id();
        $topics[] = $topicgen->create_topic('topic2')->get_id();

        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        // Create users.
        $users = $articlegen->create_users(2);
        $this->setUser($users[0]);

        // Create article.
        $article = $articlegen->create_article([
            'access' => access::PUBLIC
        ]);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $articlegen->set_capabilities(CAP_ALLOW, $user->id, $article->get_context());
        }

        // Create share via graphql.
        $ec = execution_context::create('ajax', 'engage_article_update_article');
        $parameters = [
            'resourceid' => $article->get_id(),
            'format' => FORMAT_JSON_EDITOR,
            'shares' => [
                [
                    'instanceid' => $users[1]->id,
                    'component' => recipient_helper::get_component(user_recipient::class),
                    'area' => user_recipient::AREA
                ]
            ],
            'access' => 'PUBLIC',
            'topics' => $topics
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('article', $result->data);

        $article = $result->data['article'];
        $this->assertArrayHasKey('sharedbycount', $article);
        $this->assertEquals(0, $article['sharedbycount']);
    }

    /**
     * Validate the following:
     *   1. We can query share totals via graphql.
     */
    public function test_share_totals() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        // Create users.
        $users = $articlegen->create_users(2);

        // Create article.
        $this->setUser($users[0]);
        $article = $articlegen->create_article([
            'access' => access::PUBLIC
        ]);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $articlegen->set_capabilities(CAP_ALLOW, $user->id, $article->get_context());
        }

        // Share article.
        $this->setUser($users[1]);
        $recipients = $articlegen->create_user_recipients([$users[0]]);
        $articlegen->share_article($article, $recipients);

        // Get share totals.
        $ec = execution_context::create('ajax', 'totara_engage_share_totals');
        $parameters = [
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type()
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('shares', $result->data);

        $shares = $result->data['shares'];
        $this->assertEquals(1, $shares['totalrecipients']);

        $this->assertArrayHasKey('recipients', $shares);
        $recipients = $shares['recipients'];
        $this->assertEquals(1, sizeof($recipients));

        $recipient = reset($recipients);
        $this->assertEquals(user_recipient::AREA, $recipient['area']);
        $this->assertEquals(1, $recipient['total']);
    }

    /**
     * Validate the following:
     *   1. We can query recipients of a specific shared item.
     */
    public function test_recipients() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        // Create users.
        $users = $articlegen->create_users(2);
        $this->setUser($users[1]);

        // Create article.
        $article = $articlegen->create_article([
            'access' => access::PUBLIC
        ]);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $articlegen->set_capabilities(CAP_ALLOW, $user->id, $article->get_context());
        }

        // Share article.
        $recipients = $articlegen->create_user_recipients([$users[0]]);
        $articlegen->share_article($article, $recipients);

        // Switch to admin user to not be blocked by privacy checks.
        $this->setUser(2);

        // Get recipients.
        $ec = execution_context::create('ajax', 'totara_engage_share_recipients');
        $parameters = [
            'itemid' => $article->get_id(),
            'component' => article::get_resource_type(),
            'theme' => 'ventura',
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('recipients', $result->data);

        $recipients = $result->data['recipients'];
        $this->assertNotEmpty($recipients);
        $this->assertEquals(1, sizeof($recipients));
        $recipient = reset($recipients);
        $this->assertArrayHasKey('user', $recipient);
        $user = $recipient['user'];
        $this->assertArrayHasKey('fullname', $user);
        $this->assertEquals('Some1 Any1', $user['fullname']);
    }
}