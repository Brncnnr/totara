<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package contentmarketplaceactivity_linkedin
 */

defined('MOODLE_INTERNAL') || die();

use contentmarketplace_linkedin\model\user_progress;
use core\entity\course;
use core_phpunit\testcase;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\testing\generator as marketplace_generator;
use totara_contentmarketplace\testing\mock\create_course_interactor;

/**
 * @group contentmarketplaceactivity_linkedin
 */
class contentmarketplaceactivity_linkedin_user_enrolment_testcase extends testcase {

    /**
     * User enrolment should trigger the user_enrolment_created event and the observer should
     * pick it up and update user progress.
     *
     * @return void
     */
    public function test_user_enrolment(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $marketplace_generator = marketplace_generator::instance();

        // Create a LinkedIn learning object.
        $learning_object = $marketplace_generator->create_learning_object(
            'contentmarketplace_linkedin',
            'Learning Object'
        );

        // Create course with completion set to the contentmarketplace module.
        $course_id = course_builder::create_with_learning_object(
            'contentmarketplace_linkedin',
            $learning_object->get_id(),
            new create_course_interactor(get_admin()->id)
        )->set_enable_course_completion()->create_course()->get_course_id();

        // Create user.
        $user = $gen->create_user();

        // Update user progress.
        user_progress::set_progress($user->id, $learning_object->urn, 25, time());

        $params = [
            'userid' => $user->id,
            'course' => $course_id
        ];

        // Confirm that there are no user completions.
        $this->assertCount(0, $DB->get_records('course_completion_crit_compl', $params));

        // Enrol user.
        $gen->enrol_user($user->id, $course_id);

        // Confirm that there now are user completions.
        $this->assertCount(1, $DB->get_records('course_completion_crit_compl', $params));

        // Confirm that the completion is for our contentmarketplace module.
        $completion_info = new completion_info((new course($course_id))->to_record());
        $criteria = $completion_info->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY);
        foreach ($criteria as $criterion) {
            $this->assertEquals('contentmarketplace', $criterion->module);
        }
    }
}