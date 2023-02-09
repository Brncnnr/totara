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

use totara_engage\bookmark\bookmark;
use totara_engage\userdata\bookmark as user_data_bookmark;
use totara_userdata\userdata\target_user;

/**
 * Test GDPR functionality for bookmark within engage
 */
class totara_engage_userdata_bookmark_testcase extends advanced_testcase {
    /**
     * Test to assure that when the user is deleted, all the bookmarks that had been created for the user
     * are also being purged as well.
     *
     * @return void
     */
    public function test_purge_bookmark(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article();

        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $bookmark = new bookmark($user_two->id, $article->get_id(), $article::get_resource_type());
        $bookmark->add_bookmark();

        // Bookmark created
        $this->assertTrue(
            $DB->record_exists('engage_bookmark', ['userid' => $user_two->id])
        );

        // Now start deleting the second user to check if the purge is happening.
        $user_two->deleted = 1;
        $DB->update_record('user', $user_two);

        $target_user = new target_user($user_two);
        $context = context_system::instance();

        $result = user_data_bookmark::execute_purge($target_user, $context);
        $this->assertEquals(user_data_bookmark::RESULT_STATUS_SUCCESS, $result);

        $this->assertFalse(
            $DB->record_exists('engage_bookmark', ['userid' => $user_two->id])
        );
    }

    /**
     * Test to assure that the bookmarks are exported.
     *
     * @return void
     */
    public function test_export_bookmarks(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $bookmark = new bookmark(
            $user_two->id,
            $playlist->get_id(),
            $playlist::get_resource_type()
        );

        $bookmark->add_bookmark();
        $this->assertTrue($DB->record_exists('engage_bookmark', ['userid' => $user_two->id]));

        $target_user = new target_user($user_two);
        $context = context_system::instance();

        $export = user_data_bookmark::execute_export($target_user, $context);

        $this->assertNotEmpty($export->data);
        $this->assertCount(1, $export->data);

        foreach ($export->data as $record) {
            $this->assertIsArray($record);
            $this->assertArrayHasKey('id', $record);
            $this->assertArrayHasKey('item_id', $record);
            $this->assertArrayHasKey('time_created', $record);
            $this->assertArrayHasKey('component', $record);
        }
    }
}