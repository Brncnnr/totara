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
use totara_catalog\task\refresh_catalog_adhoc;
use core\event\manager as event_manager;

class engage_article_catalog_remove_user_testcase extends advanced_testcase {
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
    public function test_delete_user_remove_article_in_catalog(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        // Create articles for user one.
        for ($i = 0; $i < 5; $i++) {
            $article_generator->create_article([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);
        }

        // Check that the catalog table is populated
        self::assertEquals(5, $DB->count_records('catalog'));

        // Delete user then the articles in catalog should be deleted.
        delete_user($user_one);
        self::assertEquals(0, $DB->count_records('catalog'));
    }

    /**
     * @return void
     */
    public function test_suspend_user_remove_article_in_catalog(): void {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        // Create articles for user
        for ($i = 0; $i < 3; $i++) {
            $article_generator->create_article([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);
        }

        self::assertEquals(3, $DB->count_records('catalog'));

        // Suspend the user.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_one->id);

        // Suspend user should not remove the content in catalog.
        self::assertEquals(3, $DB->count_records('catalog'));
    }

    /**
     * @return void
     */
    public function test_recalculate_catalog_after_user_is_deleted(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        // Create articles for user
        for ($i = 0; $i < 3; $i++) {
            $article_generator->create_article([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);
        }

        self::assertEquals(3, $DB->count_records('catalog'));

        // Delete user then recalculate the catalog.
        delete_user($user_one);

        // Recalculate the catalog.
        $task = new refresh_catalog_adhoc();
        $task->execute();

        self::assertEquals(0, $DB->count_records('catalog'));
    }

    /**
     * @return void
     */
    public function test_recalculate_catalog_after_user_is_suspended(): void {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        // Create articles for user
        for ($i = 0; $i < 3; $i++) {
            $article_generator->create_article([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);
        }

        self::assertEquals(3, $DB->count_records('catalog'));

        // Clear the observers so that noone can break this test via event observers
        event_manager::phpunit_replace_observers([]);

        // Suspend user then recalculate the catalog.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_one->id);

        // Recalculate the catalog.
        $task = new refresh_catalog_adhoc();
        $task->execute();

        // Suspend user should not remove the content.
        self::assertEquals(3, $DB->count_records('catalog'));
    }
}