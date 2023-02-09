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

use totara_engage\access\access;
use totara_catalog\catalog_retrieval;
use engage_article\totara_engage\resource\article;
use core\event\manager as event_manager;

class engage_article_availability_testcase extends advanced_testcase {
    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        event_manager::phpunit_reset();
    }

    /**
     * @return void
     */
    public function test_deleted_user_should_make_article_unavailable(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['userid' => $user_one->id]);

        self::assertTrue($article->is_available());

        // Start deleting the user.
        delete_user($user_one);
        self::assertFalse($article->is_available());
    }

    /**
     * @return void
     */
    public function test_suspended_user_should_not_make_article_unavailable(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['userid' => $user_one->id]);

        self::assertTrue($article->is_available());

        // Suspend the user.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_one->id);

        // Suspend user should not make the article unavailable.
        self::assertTrue($article->is_available());
    }

    /**
     * @return void
     */
    public function test_fetch_for_catalog_should_exclude_deleted_user_content(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        // Create 5 public articles for each users.
        for ($i = 0; $i < 5; $i++) {
            $article_generator->create_article([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);

            $article_generator->create_article([
                'userid' => $user_two->id,
                'access' => access::PUBLIC
            ]);
        }

        // Fetched the articles with catalog as user one before the user two is deleted.
        $this->setUser($user_one);

        $catalog_retrieval = new catalog_retrieval();
        $before_delete_result = $catalog_retrieval->get_page_of_objects(10, 0);

        self::assertObjectHasAttribute('objects', $before_delete_result);
        self::assertIsArray($before_delete_result->objects);
        self::assertCount(10, $before_delete_result->objects);

        foreach ($before_delete_result->objects as $record) {
            $article = article::from_instance($record->objectid, article::get_resource_type());
            self::assertContainsEquals($article->get_userid(), [$user_one->id, $user_two->id]);
        }

        // delete user two and refetch the catalog again.
        // Clear all the event observers so that we can fetch it correctly.
        event_manager::phpunit_replace_observers([]);
        delete_user($user_two);

        $after_delete_result = $catalog_retrieval->get_page_of_objects(10, 0);

        self::assertObjectHasAttribute('objects', $after_delete_result);
        self::assertIsArray($after_delete_result->objects);

        // Articles from user two should be excluded.
        self::assertCount(5, $after_delete_result->objects);

        foreach ($after_delete_result->objects as $record) {
            $article = article::from_instance($record->objectid, article::get_resource_type());

            self::assertEquals($user_one->id, $article->get_userid());
            self::assertNotEquals($user_two->id, $article->get_userid());
        }
    }

    /**
     * @return void
     */
    public function test_fetch_for_catalog_when_user_is_suspended(): void {
        global $CFG;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        // Generate 5 articles for each user: one and two
        for ($i = 0; $i < 5; $i++) {
            $article_generator->create_article([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);

            $article_generator->create_article([
                'userid' => $user_two->id,
                'access' => access::PUBLIC
            ]);
        }

        // Log in as first user to fetch the catalog.
        $this->setUser($user_one);

        $retrieval = new catalog_retrieval();
        $before_suspended_result = $retrieval->get_page_of_objects(10, 0);

        self::assertObjectHasAttribute('objects', $before_suspended_result);
        self::assertIsArray($before_suspended_result->objects);
        self::assertCount(10, $before_suspended_result->objects);

        foreach ($before_suspended_result->objects as $record) {
            $article = article::from_instance($record->objectid, article::get_resource_type());
            self::assertContainsEquals($article->get_userid(), [$user_one->id, $user_two->id]);
        }

        // Start suspending the user.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_two->id);

        $after_suspended_result = $retrieval->get_page_of_objects(10, 0);

        self::assertObjectHasAttribute('objects', $after_suspended_result);
        self::assertIsArray($after_suspended_result->objects);

        // Suspending user should not exclude the content of that user.
        self::assertCount(10, $after_suspended_result->objects);

        foreach ($after_suspended_result->objects as $record) {
            $article = article::from_instance($record->objectid, article::get_resource_type());
            self::assertContainsEquals($article->get_userid(), [$user_one->id, $user_two->id]);
        }
    }
}