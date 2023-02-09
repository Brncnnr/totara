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
 * @author  Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\model\user_progress;
use totara_contentmarketplace\course\course_builder;
use core_phpunit\testcase;
use totara_contentmarketplace\testing\mock\create_course_interactor;
use core\testing\generator as core_generator;
use totara_contentmarketplace\testing\generator as marketplace_generator;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_contentmarketplace
 */
class user_progress_observer_testcase extends testcase {

    /**
     * @return void
     */
    public function test_user_progress_updated_creates_completion(): void {
        global $DB;
        self::setAdminUser();

        $interactor = new create_course_interactor(get_admin()->id);
        $core_generator = core_generator::instance();
        $marketplace_generator = marketplace_generator::instance();
        $time = time();

        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin', 'One');
        $course_id = course_builder::create_with_learning_object(
            'contentmarketplace_linkedin',
            $learning_object->get_id(),
            $interactor
        )
            ->set_enable_course_completion()
            ->create_course()
            ->get_course_id();

        $user = $core_generator->create_user();
        $core_generator->enrol_user($user->id, $course_id);

        self::assertEmpty($DB->get_record('course_modules_completion', ['userid'=> $user->id]));
        self::assertEmpty($DB->get_record('course_completion_crit_compl', ['userid'=> $user->id, 'course' => $course_id]));

        $progress = user_progress::set_progress($user->id, $learning_object->urn, 25, $time);

        // Check criteria completion and module completion records have been created.
        $module_completions = $DB->get_records('course_modules_completion', ['userid'=> $user->id]);
        $criteria_completions = $DB->get_records('course_completion_crit_compl', ['userid'=> $user->id, 'course' => $course_id]);
        self::assertCount(1, $module_completions);
        self::assertCount(1, $criteria_completions);
        $module_completion = reset($module_completions);
        self::assertEquals($progress->progress, $module_completion->progress);
        self::assertEquals($progress->user_id, $module_completion->userid);

        $progress = user_progress::set_progress($user->id, $learning_object->urn, 45, $time + 100);

        // Check another criteria completion record hasn't been created.
        $module_completions = $DB->get_records('course_modules_completion', ['userid'=> $user->id]);
        $criteria_completions = $DB->get_records('course_completion_crit_compl', ['userid'=> $user->id, 'course' => $course_id]);
        self::assertCount(1, $module_completions);
        self::assertCount(1, $criteria_completions);
        $module_completion = reset($module_completions);
        self::assertEquals($progress->progress, $module_completion->progress);
        self::assertEquals($progress->user_id, $module_completion->userid);
    }
}
