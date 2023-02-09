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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_course
 */

use core_course\local\archive_progress_helper\base;
use core_phpunit\testcase;
global $CFG;
require_once($CFG->dirroot . '/course/tests/fixtures/archive_progress_helper_mock.php');

/**
 * @covers \core_course\local\archive_progress_helper\base
 */
class core_course_archive_progress_helper_base_testcase extends testcase {

    private function get_instance(stdClass $course, $has_progress = false) {
        return new archive_progress_helper_mock($course, $has_progress);
    }

    public function test_get_unable_to_archive_reason() {
        global $CFG;

        // When enable completion is disabled on the site.
        $CFG->enablecompletion = false;
        $instance = $this->get_instance((object)[
            'enablecompletion' => false
        ]);

        // Then user can not archive course.
        $this->assertEquals('Completion is not enabled for the site', $instance->get_unable_to_archive_reason());

        // When enable completion is disabled on the course.
        $CFG->enablecompletion = true;
        $instance = $this->get_instance((object)[
            'enablecompletion' => false
        ]);

        // Then user can not archive course.
        $this->assertEquals('Completion is not enabled for the course', $instance->get_unable_to_archive_reason());

        // When enable completion is enabled for both the course and site.
        $instance = $this->get_instance((object)[
            'enablecompletion' => true
        ]);

        // Then user can archive course.
        $this->assertNull($instance->get_unable_to_archive_reason());
    }

    public function test_reset_user_progress() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        /** @var \mod_forum\testing\generator $forum_generator */
        $forum_generator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $forum = $forum_generator->create_instance(['course' => $course]);
        $discussion = $forum_generator->create_discussion(['course' => $course->id, 'forum' => $forum->id, 'userid' => $user->id]);
        $forum_generator->create_post(['discussion' => $discussion->id, 'userid' => $user->id, 'parent' => $discussion->id]);

        /** @var \core_completion\testing\generator $completion_generator */
        $completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $completion_generator->enable_completion_tracking($course);

        // WHEN a user completes the course.
        $completion_generator->complete_course($course, $user, time());

        // Confirm the user is complete and a forum post exists.
        $course1info = new completion_info($course);
        $this->assertTrue($course1info->is_course_complete($user->id));
        [$course, $cm] = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);
        self::assertSame('1', forum_get_discussions_count($cm));
        $posts = forum_get_all_discussion_posts($discussion->id, 'id DESC');
        self::assertSame(2, count($posts));
        foreach ($posts as $post) {
            // Confirm the posts have not been archived.
            self::assertSame('0', $post->archived);
        }

        // THEN Users progress is archived.
        $instance = $this->get_instance((object)[
            'enablecompletion' => true
        ]);
        $rm = new ReflectionMethod($instance, 'reset_user_progress');
        $rm->setAccessible(true);
        $rm->invoke($instance, $course, $user->id);
        // base::reset_user_progress($course, $user->id);

        // Confirm the user is progress is archived.
        $course1info = new completion_info($course);
        $this->assertFalse($course1info->is_course_complete($user->id));
        [, $cm] = get_course_and_cm_from_cmid($forum->cmid, 'forum', $course);

        self::assertSame('1', forum_get_discussions_count($cm));
        $posts = forum_get_all_discussion_posts($discussion->id, 'id DESC');

        self::assertSame(2, count($posts));
        // AND Confirm the posts are not archived.
        foreach ($posts as $post) {
            self::assertSame('1', $post->archived);
        }
    }

    public function test_linked_programs_and_certifications() {
        $generator = $this->getDataGenerator();

        // When a course does not have linked programs and certifications.
        $course = $generator->create_course();

        // Then has no linked programs or certifications.
        $instance = $this->get_instance($course);
        $rm = new ReflectionMethod($instance, 'get_linked_progs_and_certs');
        $rm->setAccessible(true);

        $linked_items = $rm->invoke($instance);

        $this->assertCount(2, $linked_items);
        $this->assertCount(0, $linked_items['programs']);
        $this->assertCount(0, $linked_items['certifications']);

        // When the course is linked to programs and certifications.
        /** @var \totara_program\testing\generator $program_generator */
        $program_generator = $generator->get_plugin_generator('totara_program');
        $program = $program_generator->create_program();
        $certification = $program_generator->create_certification();
        $program_generator->legacy_add_courseset_program($program->id, [$course->id]);
        $program_generator->legacy_add_courseset_program($certification->id, [$course->id]);

        // Then there is a program and a certification linked to the course.
        $instance = $this->get_instance($course);
        $rm = new ReflectionMethod($instance, 'get_linked_progs_and_certs');
        $rm->setAccessible(true);

        $linked_items = $rm->invoke($instance);

        $this->assertCount(2, $linked_items);
        $this->assertCount(1, $linked_items['programs']);
        $this->assertCount(1, $linked_items['certifications']);

        $this->assertEquals($program->fullname, $linked_items['programs'][$program->id]);
        $this->assertEquals($certification->fullname, $linked_items['certifications'][$certification->id]);
    }
}