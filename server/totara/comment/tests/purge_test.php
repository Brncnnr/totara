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
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use totara_comment\comment_helper;

/**
 * Most likely this is for performance testing.
 */
class totara_comment_purge_testcase extends advanced_testcase {
    /**
     * Test suite to assure that all the replies belong to the comment are
     * removed completely.
     *
     * @return void
     */
    public function test_purge_multiple_replies(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(42, 'totara_comment', 'bomba');

        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $comment_id = $comment->get_id();

        // Create around 200 replies related to the comment above, but by a different user.
        // Hence, we can test whether the replies are deleted properly.
        for ($i = 0; $i < 201; $i++) {
            $comment_generator->create_reply($comment_id, "This is content {$i}");
        }

        // 201 replies + 1 comment
        $this->assertEquals(201, $DB->count_records('totara_comment', ['userid' => $user_two->id]));
        $this->assertEquals(1, $DB->count_records('totara_comment', ['userid' => $user_one->id]));

        // Start purging comment
        comment_helper::purge_comment($comment);

        // 0 comment + 0 reply
        $this->assertEquals(0, $DB->count_records('totara_comment', ['userid' => $user_one->id]));
        $this->assertEquals(0, $DB->count_records('totara_comment', ['userid' => $user_two->id]));
    }

    /**
     * @return void
     */
    public function test_purge_multiple_comments(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        // Create around 200 comments and see if the purge is purging them all.
        for ($i = 0; $i < 202; $i++) {
            // Dota 2 references :)
            $comment_generator->create_comment(42, 'dota_pudge', 'fresh_meat', "They call me the Butcher. {$i}");
        }

        $this->assertEquals(202, $DB->count_records('totara_comment', ['userid' => $user->id]));

        comment_helper::purge_area_comments('dota_pudge', 'fresh_meat', 42);
        $this->assertEquals(0, $DB->count_records('totara_comment', ['userid' => $user->id]));
    }
}