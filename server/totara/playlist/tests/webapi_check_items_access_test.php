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
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_playlist_webapi_check_items_access_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_run_check_items_access_as_non_access_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist(['userid' => $user_one->id]);

        // Log in as second user to make sure that exception is thrown.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The playlist is not accessible by the user");

        $this->resolve_graphql_query(
            'totara_playlist_check_items_access',
            [
                'items' => [],
                'playlist_id' => $playlist->get_id()
            ]
        );
    }

    /**
     * @return void
     */
    public function test_check_restricted_item_on_public_playlist(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $restricted_playlist = $playlist_generator->create_playlist([
            'userid' => $user_one->id,
            'access' => access::RESTRICTED
        ]);

        // Create one private and one public articles.
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $restricted_article = $article_generator->create_article([
            'userid' => $user_one->id,
            'access' => access::RESTRICTED
        ]);

        $public_article = $article_generator->create_article([
            'userid' => $user_one->id,
            'access' => access::PUBLIC
        ]);

        // Run graphql to check the result.
        $this->setUser($user_one);
        $result = $this->resolve_graphql_query(
            'totara_playlist_check_items_access',
            [
                'playlist_id' => $restricted_playlist->get_id(),
                'items' => [
                    [
                        'itemid' => $public_article->get_id(),
                        'component' => $public_article::get_resource_type()
                    ],
                    [
                        'itemid' => $restricted_article->get_id(),
                        'component' => $restricted_article::get_resource_type()
                    ]
                ]
            ]
        );

        // Since there is a restricted article, we will have a warning.
        self::assertIsArray($result);
        self::assertArrayHasKey('warning', $result);
        self::assertArrayHasKey('message', $result);

        self::assertTrue($result['warning']);
        self::assertEquals(get_string('warning_change_to_restricted', 'totara_playlist'), $result['message']);
    }

    /**
     * @return void
     */
    public function test_check_private_item_on_public_playlist(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $public_playlist = $playlist_generator->create_playlist([
            'userid' => $user_one->id,
            'access' => access::PUBLIC
        ]);

        // Create one private and one public articles.
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $private_article = $article_generator->create_article([
            'userid' => $user_one->id,
            'access' => access::PRIVATE
        ]);

        $public_article = $article_generator->create_article([
            'userid' => $user_one->id,
            'access' => access::PUBLIC
        ]);

        // Run graphql to check the result.
        $this->setUser($user_one);
        $result = $this->resolve_graphql_query(
            'totara_playlist_check_items_access',
            [
                'playlist_id' => $public_playlist->get_id(),
                'items' => [
                    [
                        'itemid' => $public_article->get_id(),
                        'component' => $public_article::get_resource_type()
                    ],
                    [
                        'itemid' => $private_article->get_id(),
                        'component' => $private_article::get_resource_type()
                    ]
                ]
            ]
        );

        // Since there is a private article, we will have a warning.
        self::assertIsArray($result);
        self::assertArrayHasKey('warning', $result);
        self::assertArrayHasKey('message', $result);

        self::assertTrue($result['warning']);
        self::assertEquals(get_string('warning_change_to_public', 'totara_playlist'), $result['message']);
    }

    /**
     * @return void
     */
    public function test_check_public_article_against_public_playlist(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $public_playlist = $playlist_generator->create_playlist([
            'userid' => $user_one->id,
            'access' => access::PUBLIC
        ]);

        // Create one private and one public articles.
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $public_article = $article_generator->create_article([
            'userid' => $user_one->id,
            'access' => access::PUBLIC
        ]);

        // Run graphql to check the result.
        $this->setUser($user_one);
        $result = $this->resolve_graphql_query(
            'totara_playlist_check_items_access',
            [
                'playlist_id' => $public_playlist->get_id(),
                'items' => [
                    [
                        'itemid' => $public_article->get_id(),
                        'component' => $public_article::get_resource_type()
                    ],
                ]
            ]
        );

        // Since there are no non public article, we will have a warning.
        self::assertIsArray($result);
        self::assertArrayHasKey('warning', $result);
        self::assertArrayHasKey('message', $result);

        self::assertFalse($result['warning']);
        self::assertEmpty($result['message']);
    }

    /**
     * @return void
     */
    public function test_check_public_article_against_restricted_playlist(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'userid' => $user_one->id,
            'access' => access::PUBLIC
        ]);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article([
            'userid' => $user_one->id,
            'access' => access::PUBLIC
        ]);

        $this->setUser($user_one);
        $result = $this->resolve_graphql_query(
            'totara_playlist_check_items_access',
            [
                'playlist_id' => $playlist->get_id(),
                'items' => [
                    [
                        'itemid' => $article->get_id(),
                        'component' => $article::get_resource_type()
                    ]
                ]
            ]
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('warning', $result);
        self::assertArrayHasKey('message', $result);

        self::assertFalse($result['warning']);
        self::assertEmpty($result['message']);
    }
}