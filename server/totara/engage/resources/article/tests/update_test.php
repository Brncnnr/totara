<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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

use engage_article\totara_engage\resource\article;
use totara_engage\access\access;
use totara_engage\exception\resource_exception;
use core\webapi\execution_context;
use totara_engage\timeview\time_view;
use totara_webapi\graphql;
use core\json_editor\node\paragraph;
use core\json_editor\node\mention;
use core\json_editor\node\text;

class engage_article_update_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_article(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $data = [
            'name' => 'hello world',
            'content' => 'bolobala',
            'timeview' => time_view::LESS_THAN_FIVE,
            'access' => access::PRIVATE
        ];

        /** @var article $resource */
        $resource = article::create($data);

        $this->assertEquals($data['name'], $resource->get_name());
        $this->assertEquals($data['content'], $resource->get_content());

        $result = $resource->can_update($user->id);

        $data['name'] = "Bolobala xxoxo";
        $data['access'] = access::PUBLIC;
        $resource->update($data);

        $this->assertTrue($result);
        $this->assertEquals($data['name'], $resource->get_name());
        $this->assertEquals($data['content'], $resource->get_content());
        $this->assertEquals($data['access'], $resource->get_access());
    }

    /**
     * @return void
     */
    public function test_update_article_without_access(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $data = [
            'name' => 'hello world',
            'content' => 'Article content',
        ];

        /** @var article $resource */
        $resource = article::create($data);

        $this->assertEquals($data['name'], $resource->get_name());
        $this->assertEquals($data['content'], $resource->get_content());

        $data['name'] = "hello new world";
        $data['content'] = "New article content";
        $resource->update($data, $user->id);

        $this->assertEquals($data['name'], $resource->get_name());
        $this->assertEquals($data['content'], $resource->get_content());
    }

    /**
     * @return void
     */
    public function test_update_article_without_permissions(): void {
        $gen = $this->getDataGenerator();

        $user = $gen->create_user();
        $this->setUser($user);

        $data = [
            'name' => 'hello world',
            'content' => 'bolobala',
            'timeview' => time_view::LESS_THAN_FIVE
        ];

        /** @var article $resource */
        $resource = article::create($data);

        $user2 = $gen->create_user();
        $this->setUser($user2);

        $exception = null;
        try {
            $data['name'] = ";pkdowq dddwew";
            $resource->update($data, $user2->id);
        } catch (resource_exception $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertEquals(get_string('error:update', 'engage_article'), $exception->getMessage());
        $this->assertNotEquals($data['name'], $resource->get_name());
    }

    /**
     * @return void
     */
    public function test_update_article_via_graphql(): void {
        $this->setAdminUser();
        /** @var \totara_topic\testing\generator $topicgen */
        $topicgen = $this->getDataGenerator()->get_plugin_generator('totara_topic');
        $topics[] = $topicgen->create_topic('topic1')->get_id();
        $topics[] = $topicgen->create_topic('topic2')->get_id();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $article = article::create(
            [
                'name' => "Hello world",
                'content' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('Abcde eee')]
                ]),
                'timeview' => time_view::LESS_THAN_FIVE
            ]
        );

        $args = [
            'resourceid' => $article->get_id(),
            'name' => "Bolobala",
            'access' => 'PUBLIC',
            'format' => FORMAT_JSON_EDITOR,
            'topics' => $topics,
            'content' => json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('new content')]
            ]),
        ];

        $ec = execution_context::create('ajax', 'engage_article_update_article');
        $result = graphql::execute_operation($ec, $args);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('article', $result->data);
        $article = $result->data['article'];
        $this->assertEquals('Bolobala', $article['resource']['name']);
        $this->assertEquals('PUBLIC', $article['resource']['access']);
        $this->assertEquals('new content', format_string($article['content']));
    }

    /**
     * @return void
     */
    public function test_article_name_validation(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $article = article::create(
            [
                'name' => "Hello world",
                'content' => "test content",
                'timeview' => time_view::LESS_THAN_FIVE
            ]
        );

        $data['name'] = "TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax";
        $this->assertEquals(76, strlen('TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax'));

        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Validation run for property 'name' has been failed");
        $article->update($data, $user->id);
    }

    /**
     * @return void
     */
    public function test_article_name_validation_via_graphql(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $article = article::create(
            [
                'name' => "Hello world",
                'content' => "Abcde eee",
                'timeview' => time_view::LESS_THAN_FIVE
            ]
        );

        $args = [
            'resourceid' => $article->get_id(),
            'name' => "TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax",
            'format' => FORMAT_JSON_EDITOR,
            'content' => json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('x')]
            ])
        ];

        $ec = execution_context::create('ajax', 'engage_article_update_article');
        $result = graphql::execute_operation($ec, $args);

        $this->assertNotEmpty($result->errors);
        $error = current($result->errors);
        $this->assertEquals(
            "Coding error detected, it must be fixed by a programmer: Validation run for property 'name' has been failed",
            $error->getMessage()
        );
    }

    /**
     * @return void
     */
    public function test_update_api_as_guest() {
        $generator = $this->getDataGenerator();

        // Set up the user as this user will be passed as argument to the function
        // so that we can see if this user is going thru all of the process or not.
        $user_one = $generator->create_user();

        // But we're going to do it as the guest, to bump into user id mismatching and global USER problems.
        $this->setGuestUser();
        $guest_user = guest_user();

        $original_content = json_encode([
            'type' => 'doc',
            'content' => [paragraph::create_json_node_from_text("This is document")]
        ]);

        /** @var article $article */
        $article = article::create(
            [
                'name' => "Hello world",
                'content' => $original_content,
                'format' => FORMAT_JSON_EDITOR

            ],
            $user_one->id
        );

        self::assertInstanceOf(article::class, $article);
        self::assertEquals("Hello world", $article->get_name());
        self::assertEquals($original_content, $article->get_content());

        $article_owner_id = $article->get_userid();
        self::assertEquals($user_one->id, $article_owner_id);
        self::assertNotEquals($guest_user->id, $article_owner_id);

        // Update the article as same user actor, but the content to be mention the second user.
        // We are using the mention content because we want to be sure that the content processor
        // is working as expected.
        $user_two = $generator->create_user();
        $mention_content = json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => paragraph::get_type(),
                    'content' => [
                        text::create_json_node_from_text("This is article is about user two"),
                        mention::create_raw_node($user_two->id)
                    ]
                ]
            ]
        ]);

        $article->update(
            [
                'content' => $mention_content,
                'format' => FORMAT_JSON_EDITOR
            ],
            $user_one->id
        );

        self::assertEquals($mention_content, $article->get_content());
        self::assertNotEquals($original_content, $article->get_content());

        // User one is still the owner of this article.
        self::assertEquals($user_one->id, $article_owner_id);
        self::assertNotEquals($guest_user->id, $article_owner_id);
    }
}