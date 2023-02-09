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

use core\webapi\execution_context;
use totara_engage\timeview\time_view;
use totara_webapi\graphql;
use engage_article\totara_engage\resource\article;
use engage_article\event\article_created;
use core\json_editor\node\paragraph;

class engage_article_create_article_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_article_via_graphql(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $ec = execution_context::create('ajax', 'engage_article_create_article');
        $parameters = [
            'name' => "Hello world",
            'content' => json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('Wassup bolobala')]
            ]),
            'format' => FORMAT_JSON_EDITOR,
            'timeview' => time_view::get_code(time_view::LESS_THAN_FIVE)
        ];

        $result = graphql::execute_operation($ec, $parameters);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('article', $result->data);

        $article = $result->data['article'];
        $this->assertArrayHasKey('resource', $article);
        $this->assertEquals($parameters['name'], $article['resource']['name']);
        $this->assertEquals(
            format_text(
                $parameters['content'],
                FORMAT_JSON_EDITOR,
                ['formatter' => 'totara_tui']
            ),
            $article['content']
        );
    }

    /**
     * @return void
     */
    public function test_create_article(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $data = [
            'name' => "Hello world",
            'content' => 'Wassup llowjkfwoj',
            'timeview' => time_view::LESS_THAN_FIVE
        ];

        $resourcetype = article::get_resource_type();
        $resource = article::create($data);

        $sql = '
            SELECT 1 FROM "ttr_engage_article"  ea
            INNER JOIN "ttr_engage_resource" er ON er.instanceid = ea.id AND er.resourcetype = :type
            WHERE ea.id = :id
        ';

        $params = [
            'id' => $resource->get_instanceid(),
            'type' => $resourcetype
        ];


        $this->assertTrue($DB->record_exists_sql($sql, $params));
    }

    /**
     * This test is about making sure that no article is being created when the name is not
     * given properly.
     *
     * @return void
     */
    public function test_create_article_with_empty_name_via_graphql(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);
        $parameters = [
            'name' => '',
            'content' => json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('Wassup wasabi')]
            ]),
            'timeview' => time_view::get_code(time_view::LESS_THAN_FIVE)
        ];

        $ec = execution_context::create('ajax', 'engage_article_create_article');
        $result = graphql::execute_operation($ec, $parameters);

        $this->assertNotEmpty($result->errors);
        $error = reset($result->errors);

        $this->assertStringContainsString(
            "Validation run for property 'name' has been failed",
            $error->getMessage()
        );
    }

    /**
     * @return void
     */
    public function test_article_name_validation(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);
        $data = [
            'name' => 'TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax',
            'content' => 'Wassup wasabi',
            'timeview' => time_view::LESS_THAN_FIVE
        ];

        $this->assertEquals(76, strlen('TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax'));

        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Validation run for property 'name' has been failed");
        article::create($data);
    }

    /**
     * @return void
     */
    public function test_article_name_validation_via_graphql(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $parameters = [
            'name' => 'TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax',
            'content' => json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('Wassup bolobala')]
            ]),
            'format' => FORMAT_JSON_EDITOR,
            'timeview' => time_view::get_code(time_view::LESS_THAN_FIVE)
        ];

        $ec = execution_context::create('ajax', 'engage_article_create_article');
        $result = graphql::execute_operation($ec, $parameters);

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
    public function test_create_article_trigger_event(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $event_sink = $this->redirectEvents();
        $article = article::create(
            [
                'name' => 'This is the article',
                'content' => 'Content 101',
                'format' => FORMAT_PLAIN
            ],
            $user_one->id
        );

        $events = $event_sink->get_events();
        self::assertCount(1, $events);

        /** @var article_created $created_event */
        $created_event = reset($events);

        self::assertInstanceOf(article_created::class, $created_event);

        // Making sure that the article's owner id is the same as the created event user id.
        self::assertEquals($article->get_userid(), $created_event->get_user_id());
    }

    /**
     * @return void
     */
    public function test_create_article_trigger_event_with_guest_user(): void {
        global $USER;
        $this->setGuestUser();

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $event_sink = $this->redirectEvents();
        article::create(
            [
                'content' => 'This is something else',
                'format' => FORMAT_PLAIN,
                'name' => 'Article 101'
            ],
            $user_one->id
        );

        $events = $event_sink->get_events();
        self::assertCount(1, $events);

        /** @var article_created $created_event */
        $created_event = reset($events);
        self::assertInstanceOf(article_created::class, $created_event);

        // Making sure that the user who trigger event is not the user in session, as it
        // is being passed as arguments.
        self::assertNotEquals($USER->id, $created_event->get_user_id());
        self::assertEquals($user_one->id, $created_event->get_user_id());
    }
}