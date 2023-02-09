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
 * @package mod_approval
 */

use core\entity\user;
use core_phpunit\testcase;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\totara_notification\recipient\applicant_manager;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * @group approval_workflow
 * @group totara_notification
 */
class mod_approval_totara_notification_recipient_applicant_manager_testcase extends testcase {

    use approval_workflow_test_setup;

    public function test_get_name(): void {
        self::assertEquals(get_string('manager', 'totara_job'), applicant_manager::get_name());
    }

    public function test_get_user_ids(): void {
        $generator = $this->getDataGenerator();

        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user = new user($generator->create_user()->id);
        $manager = $generator->create_user();
        $manager_job = job_assignment::create_default($manager->id);
        $job_assignment = job_assignment::create_default($user->id, ['managerjaid' => $manager_job->id]);
        $application = $this->create_application($workflow, $assignment, $user, $job_assignment->id);

        self::assertEquals([$manager->id], applicant_manager::get_user_ids(['application_id' => $application->id]));
    }

    public function test_get_user_ids_with_no_job(): void {
        $generator = $this->getDataGenerator();

        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user = new user($generator->create_user()->id);
        $application = $this->create_application($workflow, $assignment, $user);

        self::assertEmpty(applicant_manager::get_user_ids(['application_id' => $application->id]));
    }

    public function test_get_user_ids_with_empty_job(): void {
        $generator = $this->getDataGenerator();

        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user = new user($generator->create_user()->id);
        $job_assignment = job_assignment::create_default($user->id);
        $application = $this->create_application($workflow, $assignment, $user, $job_assignment->id);

        self::assertEmpty(applicant_manager::get_user_ids(['application_id' => $application->id]));
    }
}
