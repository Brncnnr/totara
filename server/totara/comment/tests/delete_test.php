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

class totara_comment_delete_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_comment(): void {
        global $DB;

        $this->setAdminUser();
        $comment = comment_helper::create_comment(
            'totara_comment',
            'comment',
            42,
            'hello world'
        );

        $comment = comment_helper::soft_delete($comment->get_id());

        $this->assertTrue($DB->record_exists('totara_comment', ['id' => $comment->get_id()]));
        $this->assertEmpty($comment->get_content());
        $this->assertEmpty($comment->get_content_text());
        $this->assertEquals(FORMAT_PLAIN, $comment->get_format());
    }

    /**
     * @return void
     */
    public function test_soft_delete_reply(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $comment = comment_helper::create_comment(
            'totara_comment',
            'comment',
            42,
            'hello world'
        );

        $reply = comment_helper::create_reply($comment->get_id(), 'this is reply');
        $reply = comment_helper::soft_delete($reply->get_id());

        $this->assertTrue($DB->record_exists('totara_comment', ['id' => $reply->get_id()]));

        $this->assertEquals($reply->get_parent_id(), $comment->get_id());
        $this->assertEmpty($reply->get_content());
        $this->assertEmpty($reply->get_content_text());
        $this->assertEquals(FORMAT_PLAIN, $reply->get_format());
    }

    /**
     * @return void
     */
    public function test_purge_comment(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $comment = comment_helper::create_comment(
            'totara_comment',
            'media',
            36,
            'hello world'
        );

        $reply = comment_helper::create_reply($comment->get_id(), 'this is reply');

        $comment_id = $comment->get_id();
        $reply_id = $reply->get_id();

        $this->assertTrue($DB->record_exists('totara_comment', ['id' => $comment_id]));
        $this->assertTrue($DB->record_exists('totara_comment', ['id' => $reply_id]));

        comment_helper::purge_area_comments('totara_comment', 'media', 36);

        $this->assertFalse($DB->record_exists('totara_comment', ['id' => $comment_id]));
        $this->assertFalse($DB->record_exists('totara_comment', ['id' => $reply_id]));
    }
}