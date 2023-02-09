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

use totara_userdata\userdata\target_user;
use totara_comment\userdata\reply as user_data_reply;
use totara_comment\userdata\comment as user_data_comment;
use totara_comment\comment;

/**
 * Test to purge comments/replies via user data API.
 */
class totara_comment_userdata_testcase extends advanced_testcase {
    /**
     * This test is to assure that the replies are being purged which leaves all the comments related to
     * the user behind.
     *
     * @return void
     */
    public function test_purge_replies_with_comments(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        // Now start creating a comment
        for ($i = 0; $i < 5; $i++) {
            $comment = $comment_generator->create_comment(
                42,
                'dota_pugna',
                'skill_one'
            );

            // For every comment, we will create at least two replies.
            for ($x = 0; $x < 2; $x++) {
                $comment_generator->create_reply($comment->get_id());
            }
        }

        // After everything is created, time to purge the replies.
        $this->assertEquals((5 * 2) + 5, $DB->count_records('totara_comment', ['userid' => $user_one->id]));

        $user_one->deleted = 1;
        $DB->update_record('user', $user_one);

        $target_user = new target_user($user_one);
        $context = context_system::instance();

        $result = user_data_reply::execute_purge($target_user, $context);
        $this->assertEquals(user_data_reply::RESULT_STATUS_SUCCESS, $result);

        $this->assertEquals(5, $DB->count_records('totara_comment', ['userid' => $user_one->id]));
        $this->assertFalse(
            $DB->record_exists_sql(
                'SELECT * FROM "ttr_totara_comment" tc WHERE tc.userid = :user_id AND tc.parentid IS NOT NULL',
                ['user_id' => $user_one->id]
            )
        );
    }

    /**
     * A test to make sure that the replies that are not related to the comments should not be removed, when the
     * purge is happening. However, any comments that are related to the comment should be removed once the comment
     * is removed.
     *
     * @return void
     */
    public function test_purge_comments_with_replies(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $instance = new stdClass();
        $instance->instance_id = 42;
        $instance->component = 'dota_pudge';
        $instance->area = 'fresh_meats';

        $this->setUser($user_one);
        $comments = [];

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        // Start creating 5 comments of an instance.
        for ($i = 0; $i < 5; $i++) {
            $comments[] = $comment_generator->create_comment(
                $instance->instance_id,
                $instance->component,
                $instance->area
            );
        }

        // After creating a first 5 comments, start creating replies for these comments but it should be done
        // by someone else not the user one.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        /** @var comment $comment */
        foreach ($comments as $comment) {
            $comment_generator->create_reply(
                $comment->get_id(),
                null,
                null,
                $user_two->id
            );
        }

        // Start creating comments for the instance, so that the user_one's replies is pointing to different comments
        // and therefore we can check if the replies are being purged during the purge of comments.
        $user_two_comment = $comment_generator->create_comment(
            $instance->instance_id,
            $instance->component,
            $instance->area
        );

        // 2 more replies for the user's one to be pointing to the user_two's comment.
        $this->setUser($user_one);
        for ($i = 0; $i < 2; $i++) {
            $comment_generator->create_reply(
                $user_two_comment->get_id(),
                null,
                null,
                $user_one->id
            );
        }

        // Everything is setup, start the assertion.
        $this->assertEquals(
            (5 + 2),
            $DB->count_records('totara_comment', ['userid' => $user_one->id])
        );

        // 5 replies from user two which each of it is pointing to a single comment of user one.
        // Plus that there is one comment created by this user
        $this->assertEquals(6, $DB->count_records('totara_comment', ['userid' => $user_two->id]));

        $user_one->deleted = 1;
        $DB->update_record('user', $user_one);

        $target_user = new target_user($user_one);
        $context = context_system::instance();

        $result = user_data_comment::purge($target_user, $context);
        $this->assertEquals(user_data_comment::RESULT_STATUS_SUCCESS, $result);

        // Assertion to make sure that comment purge will not touch the replies of this
        // user one.
        $this->assertEquals(2, $DB->count_records('totara_comment', ['userid' => $user_one->id]));

        // Assertion to make sure that the replies made by user two related to the comments
        // from user one will be removed. Which it leaves only one comment left.
        $this->assertEquals(1, $DB->count_records('totara_comment', ['userid' => $user_two->id]));
    }
}