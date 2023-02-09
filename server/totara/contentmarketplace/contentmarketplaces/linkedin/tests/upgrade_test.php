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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\model\user_progress;
use core\testing\generator as core_generator;
use core_phpunit\testcase;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\testing\generator as marketplace_generator;
use totara_contentmarketplace\testing\mock\create_course_interactor;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_upgrade_testcase extends testcase {

    /**
     * Make sure that the progress column in the course_modules_completion table is in sync
     * with the marketplace_linkedin_user_progress table after upgrade
     */
    public function test_upgrade_activity_progress(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/contentmarketplace/contentmarketplaces/linkedin/db/upgradelib.php');

        self::setAdminUser();
        $admin_user = get_admin();
        $interactor = new create_course_interactor($admin_user->id);
        $core_generator = core_generator::instance();
        $marketplace_generator = marketplace_generator::instance();
        $time = time();

        $learning_object_1 = $marketplace_generator->create_learning_object('contentmarketplace_linkedin', 'One');
        $course_1_id = course_builder::create_with_learning_object(
            'contentmarketplace_linkedin',
            $learning_object_1->get_id(),
            $interactor
        )
            ->set_enable_course_completion()
            ->create_course()
            ->get_course_id();

        $learning_object_2 = $marketplace_generator->create_learning_object('contentmarketplace_linkedin', 'Two');
        $course_2_id = course_builder::create_with_learning_object(
            'contentmarketplace_linkedin',
            $learning_object_2->get_id(),
            $interactor
        )
            ->set_enable_course_completion()
            ->create_course()
            ->get_course_id();

        // Now create some users with progress towards the courses
        $user_1 = $core_generator->create_user();
        $core_generator->enrol_user($user_1->id, $course_1_id);
        $user_1_learning_object_1_progress = user_progress::set_progress($user_1->id, $learning_object_1->urn, 0, $time);

        $user_2 = $core_generator->create_user();
        $core_generator->enrol_user($user_2->id, $course_1_id);
        $user_2_learning_object_1_progress = user_progress::set_progress($user_2->id, $learning_object_1->urn, 25, $time);
        $core_generator->enrol_user($user_2->id, $course_2_id);
        $user_2_learning_object_2_progress = user_progress::set_progress($user_2->id, $learning_object_2->urn, 50, $time);

        $user_3 = $core_generator->create_user();
        $core_generator->enrol_user($user_3->id, $course_2_id);
        $user_3_learning_object_2_progress = user_progress::set_progress($user_3->id, $learning_object_2->urn, 100, $time);

        // Simulate an empty progress column before upgrading
        $DB->execute('UPDATE {course_modules_completion} SET progress = NULL');

        $this->assertEquals(3, $DB->count_records_select('course_modules_completion', 'progress IS NULL'));
        $this->assertEquals(0, $DB->count_records_select('course_modules_completion', 'progress IS NOT NULL'));

        contentmarketplace_linkedin_create_activity_progress_entries();

        $this->assertEquals(0, $DB->count_records_select('course_modules_completion', 'progress IS NULL'));
        $this->assertEquals(3, $DB->count_records_select('course_modules_completion', 'progress IS NOT NULL'));
        $this->assertFalse($DB->record_exists('course_modules_completion', ['userid' => $user_1->id]));
        $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $user_2->id, 'progress' => 25]));
        $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $user_2->id, 'progress' => 50]));
        $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $user_3->id, 'progress' => 100]));

        contentmarketplace_linkedin_create_activity_progress_entries();

        $this->assertEquals(0, $DB->count_records_select('course_modules_completion', 'progress IS NULL'));
        $this->assertEquals(3, $DB->count_records_select('course_modules_completion', 'progress IS NOT NULL'));
        $this->assertFalse($DB->record_exists('course_modules_completion', ['userid' => $user_1->id]));
        $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $user_2->id, 'progress' => 25]));
        $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $user_2->id, 'progress' => 50]));
        $this->assertTrue($DB->record_exists('course_modules_completion', ['userid' => $user_3->id, 'progress' => 100]));
    }

}
