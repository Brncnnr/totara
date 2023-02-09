<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_comment
 */

use core_phpunit\testcase;
use totara_comment\testing\generator as comment_generator;
use totara_comment\totara_notification\placeholder\comment as comment_placeholder_group;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_comment
 * @group totara_notification
 */
class totara_comment_totara_notification_placeholder_comment_testcase extends testcase {
    public function test_get_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, comment_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            [
                'content_text',
            ],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        // Create a comment.
        $comment_generator = comment_generator::instance();
        $user = self::getDataGenerator()->create_user();
        $comment = $comment_generator->create_comment(123, 'abc', 'xyz', 'one', null, $user->id);

        $placeholder_group = comment_placeholder_group::from_model($comment);

        // Check each placeholder.
        self::assertEquals($comment->get_content_text(), $placeholder_group->do_get('content_text'));
    }

    public function test_not_available(): void {
        $placeholder_group = new comment_placeholder_group(null);
        self::assertEquals('', $placeholder_group->get('content_text'));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The comment model is empty');
        $placeholder_group->do_get('content_text');
    }

    /**
     * Test that the from_id function is caching data correctly.
     */
    public function test_instances_are_cached(): void {
        global $DB;

        $comment_generator = comment_generator::instance();

        $user = self::getDataGenerator()->create_user();

        // Create two comments.
        $comment1 = $comment_generator->create_comment(123, 'abc', 'xyz', 'one', null, $user->id);
        $comment2 = $comment_generator->create_comment(456, 'def', 'rst', 'two', null, $user->id);

        self::assertNotEquals($comment1->get_id(), $comment2->get_id());

        $query_count = $DB->perf_get_reads();
        comment_placeholder_group::from_id($comment1->get_id());
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        comment_placeholder_group::from_id($comment1->get_id());
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        comment_placeholder_group::from_id($comment2->get_id());
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        comment_placeholder_group::from_id($comment1->get_id());
        comment_placeholder_group::from_id($comment2->get_id());
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }
}
