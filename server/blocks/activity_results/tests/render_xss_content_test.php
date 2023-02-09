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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package block_activity_results
 */

use core_grades\testing\generator;

class block_activity_results_render_xss_content_testcase extends advanced_testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/blocklib.php");
        require_once("{$CFG->dirroot}/blocks/moodleblock.class.php");
        require_once("{$CFG->dirroot}/blocks/activity_results/block_activity_results.php");
    }

    /**
     * @return void
     */
    public function test_render_content_with_xss_for_admin(): void {
        $this->setAdminUser();
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user([
            'idnumber' => /** @lang text */'<script>alert("doom_bringer")</script>'
        ]);

        $course = $generator->create_course();
        $generator->enrol_user($user_one->id, $course->id);

        $record = new stdClass();
        $record->course = $course->id;
        $assignment = $generator->create_module('assign', $record);

        /** @var generator $grade_generator */
        $grade_generator = $generator->get_plugin_generator('core_grades');
        $grade_item = $grade_generator->get_item_for_module($course->id, 'assign', $assignment);

        $grade_generator->new_grade_for_item($grade_item, 98, $user_one);

        set_config('showuseridentity', 'idnumber,email');
        $context = context_course::instance($course->id);

        $page = new moodle_page();
        $page->set_context($context);
        $page->set_pagetype('page-type');
        $page->set_url(new moodle_url('/'));

        $block_manager = new block_manager($page);
        $block_manager->add_region('top-region', false);
        $block_manager->set_default_region('top-region');

        $block_config = new stdClass();
        $block_config->activitygradeitemid = $grade_item->id;
        $block_config->showbest = 3;
        $block_config->showworst = 0;
        $block_config->usergroups = 0;
        $block_config->nameformat = B_ACTIVITYRESULTS_NAME_FORMAT_ID;
        $block_config->gradeformat = B_ACTIVITYRESULTS_GRADE_FORMAT_PCT;
        $block_config->decimalpoints = 2;
        $block_config->activityparent = 'assign';
        $block_config->activityparentid = $assignment->cmid;

        $block_manager->add_block(
            'activity_results',
            'top-region',
            0,
            false,
            null,
            null,
            $block_config
        );

        $block_manager->load_blocks();

        /** @var block_activity_results $block */
        $block = $block_manager->get_blocks_for_region('top-region')[0];
        $block_content = $block->get_content();

        self::assertIsObject($block_content);
        self::assertObjectHasAttribute('text', $block_content);
        self::assertStringNotContainsString(
            /** @lang text */'<script>alert("doom_bringer")</script>',
            $block_content->text
        );

        self::assertStringContainsString(
            s(/** @lang */'<script>alert("doom_bringer")</script>'),
            $block_content->text
        );

        self::assertTrue(true);
    }

    /**
     * This test is to make sure that a normal user will not be able to see
     * other user's idnumber, unless they have capability.
     * @return void
     */
    public function test_render_content_with_xss_for_normal_user(): void {
        $generator = self::getDataGenerator();

        $user_two = $generator->create_user();
        $user_one = $generator->create_user([
            'idnumber' => /** @lang text */'<script>alert("doom_bringer")</script>'
        ]);

        $course = $generator->create_course();
        $generator->enrol_user($user_one->id, $course->id);
        $generator->enrol_user($user_two->id, $course->id);

        $record = new stdClass();
        $record->course = $course->id;
        $assignment = $generator->create_module('assign', $record);

        /** @var generator $grade_generator */
        $grade_generator = $generator->get_plugin_generator('core_grades');
        $grade_item = $grade_generator->get_item_for_module($course->id, 'assign', $assignment);

        $grade_generator->new_grade_for_item($grade_item, 98, $user_one);

        set_config('showuseridentity', 'idnumber,email');
        $context = context_course::instance($course->id);

        $page = new moodle_page();
        $page->set_context($context);
        $page->set_pagetype('page-type');
        $page->set_url(new moodle_url('/'));

        $block_manager = new block_manager($page);
        $block_manager->add_region('top-region', false);
        $block_manager->set_default_region('top-region');

        $block_config = new stdClass();
        $block_config->activitygradeitemid = $grade_item->id;
        $block_config->showbest = 3;
        $block_config->showworst = 0;
        $block_config->usergroups = 0;
        $block_config->nameformat = B_ACTIVITYRESULTS_NAME_FORMAT_ID;
        $block_config->gradeformat = B_ACTIVITYRESULTS_GRADE_FORMAT_PCT;
        $block_config->decimalpoints = 2;
        $block_config->activityparent = 'assign';
        $block_config->activityparentid = $assignment->cmid;

        $block_manager->add_block(
            'activity_results',
            'top-region',
            0,
            false,
            null,
            null,
            $block_config
        );

        $block_manager->load_blocks();

        $this->setUser($user_two);

        /** @var block_activity_results $block */
        $block = $block_manager->get_blocks_for_region('top-region')[0];
        $block_content = $block->get_content();

        self::assertIsObject($block_content);
        self::assertObjectHasAttribute('text', $block_content);
        self::assertStringNotContainsString(
            /** @lang text */'<script>alert("doom_bringer")</script>',
            $block_content->text
        );

        self::assertStringNotContainsString(
            s(/** @lang */'<script>alert("doom_bringer")</script>'),
            $block_content->text
        );

        self::assertTrue(true);
    }
}