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
use totara_comment\resolver_factory;
use totara_comment\event\comment_updated;

class totara_comment_event_comment_updated_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_event_without_actor_id(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Set the user in session as user two. So that we can check
        // the process of event respect the user in session.
        $this->setUser($user_two);

        $comment = comment_helper::create_comment(
            'totara_comment',
            'comment_view',
            42,
            'This is content',
            FORMAT_PLAIN,
            null,
            $user_one->id
        );

        $resolver = resolver_factory::create_resolver('totara_comment');
        $context_id = $resolver->get_context_id(42, 'comment_view');
        $context = context::instance_by_id($context_id);

        $event = comment_updated::from_comment($comment, $context);

        self::assertEquals($user_two->id, $event->userid);
        self::assertNotEquals($user_one->id, $event->userid);
        self::assertNotEquals($comment->get_userid(), $event->userid);
    }

    /**
     * @return void
     */
    public function test_create_event_with_actor_id(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Set the user in session as user two. So that we can check
        // the process of event respect the user in session.
        $this->setUser($user_two);

        $comment = comment_helper::create_comment(
            'totara_comment',
            'comment_view',
            42,
            'This is content',
            FORMAT_PLAIN,
            null,
            $user_one->id
        );

        $resolver = resolver_factory::create_resolver('totara_comment');
        $context_id = $resolver->get_context_id(42, 'comment_view');
        $context = context::instance_by_id($context_id);

        $event = comment_updated::from_comment($comment, $context, $user_one->id);

        self::assertNotEquals($user_two->id, $event->userid);
        self::assertEquals($user_one->id, $event->userid);
        self::assertEquals($comment->get_userid(), $event->userid);
    }
}