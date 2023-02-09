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
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use core_user\totara_engage\share\recipient\user;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_engage_webapi_share_recipient_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_share_recipient_of_deleted_user(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Create a resource for user two that share to user one.

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article([
            'userid' => $user_two->id,
            'access' => access::PUBLIC
        ]);

        // Share to user one.
        $user_one_recipient = new user($user_one->id);
        $article_generator->share_article($article, [$user_one_recipient]);

        // Fetch the share via webapi.
        $this->setUser($user_two);
        $before_delete_recipient = $this->resolve_graphql_query(
            'totara_engage_share_recipients',
            [
                'itemid' => $article->get_id(),
                'component' => $article::get_resource_type(),
                'theme' => 'ventura',
            ]
        );

        self::assertIsArray($before_delete_recipient);
        self::assertCount(1, $before_delete_recipient);

        $first_before_delete_recipient = reset($before_delete_recipient);
        self::assertIsArray($first_before_delete_recipient);
        self::assertArrayHasKey('instanceid', $first_before_delete_recipient);
        self::assertArrayHasKey('component', $first_before_delete_recipient);
        self::assertArrayHasKey('area', $first_before_delete_recipient);

        self::assertEquals($user_one->id, $first_before_delete_recipient['instanceid']);
        self::assertEquals('core_user', $first_before_delete_recipient['component']);
        self::assertEquals(user::AREA, $first_before_delete_recipient['area']);

        // Delete the user and re-run the query.
        delete_user($user_one);
        $after_delete_result = $this->resolve_graphql_query(
            'totara_engage_share_recipients',
            [
                'itemid' => $article->get_id(),
                'component' => $article::get_resource_type(),
                'theme' => 'ventura',
            ]
        );

        self::assertIsArray($after_delete_result);
        self::assertEmpty($after_delete_result);
    }

    /**
     * Suspend user should not remove share.
     * @return void
     */
    public function test_fetch_share_recipient_of_suspended_user(): void {
        global $CFG;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Create a resource for user two that share to user one.

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article([
            'userid' => $user_two->id,
            'access' => access::PUBLIC
        ]);

        // Share to user one.
        $user_one_recipient = new user($user_one->id);
        $article_generator->share_article($article, [$user_one_recipient]);

        // Fetch the share via webapi.
        $this->setUser($user_two);
        $before_suspend_recipient = $this->resolve_graphql_query(
            'totara_engage_share_recipients',
            [
                'itemid' => $article->get_id(),
                'component' => $article::get_resource_type(),
                'theme' => 'ventura',
            ]
        );

        self::assertIsArray($before_suspend_recipient);
        self::assertCount(1, $before_suspend_recipient);

        $first_before_suspend_recipient = reset($before_suspend_recipient);
        self::assertIsArray($first_before_suspend_recipient);
        self::assertArrayHasKey('instanceid', $first_before_suspend_recipient);
        self::assertArrayHasKey('component', $first_before_suspend_recipient);
        self::assertArrayHasKey('area', $first_before_suspend_recipient);

        self::assertEquals($user_one->id, $first_before_suspend_recipient['instanceid']);
        self::assertEquals('core_user', $first_before_suspend_recipient['component']);
        self::assertEquals(user::AREA, $first_before_suspend_recipient['area']);

        // Delete the user and re-run the query.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_one->id);
        $after_suspend_result = $this->resolve_graphql_query(
            'totara_engage_share_recipients',
            [
                'itemid' => $article->get_id(),
                'component' => $article::get_resource_type(),
                'theme' => 'ventura',
            ]
        );

        self::assertIsArray($after_suspend_result);
        self::assertNotEmpty($after_suspend_result);

        self::assertCount(1, $after_suspend_result);
    }
}