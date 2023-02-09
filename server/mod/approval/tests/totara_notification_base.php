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
use core_user\totara_notification\placeholder\user as user_placeholder_group;
use mod_approval\totara_notification\placeholder\application as application_placeholder_group;
use mod_approval\totara_notification\placeholder\recipient as recipient_placeholder_group;
use mod_approval\totara_notification\placeholder\workflow_stage as workflow_stage_placeholder_group;
use mod_approval\testing\approval_workflow_test_setup;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

abstract class mod_approval_totara_notification_base_testcase extends testcase {

    use approval_workflow_test_setup;

    protected function setup_assignments(): stdClass {
        $result = new stdClass();

        list($result->workflow, $result->framework, $result->assignment) = $this->create_workflow_and_assignment();

        $result->applicant1 = new user($this->getDataGenerator()->create_user());
        $result->applicant2 = new user($this->getDataGenerator()->create_user());
        $result->applicant1_manager = new user($this->getDataGenerator()->create_user());
        $result->applicant1_manager_job = job_assignment::create_default($result->applicant1_manager->id);
        $result->applicant1_job = job_assignment::create_default(
            $result->applicant1->id,
            [
                'managerjaid' => $result->applicant1_manager_job->id,
                'organisationid' => $result->framework->agency->subagency_a->program_a->id,
            ]
        );

        return $result;
    }

    /**
     * @param string $resolver_class_name
     * @param int $min_time
     * @param int $max_time
     * @param array $expected
     */
    protected static function assert_scheduled_events(
        string $resolver_class_name,
        int $min_time,
        int $max_time,
        array $expected
    ): void {
        $events = call_user_func([$resolver_class_name, 'get_scheduled_events'], $min_time, $max_time);
        $actual = $events->to_array();
        $actual_to_array = array_map(static function (stdClass $scheduled) {
            return (array)$scheduled;
        }, $actual);
        self::assertEqualsCanonicalizing(
            $expected,
            $actual_to_array,
            'Expected: ' . json_encode($expected) . ' but got: ' . json_encode($actual_to_array)
        );
    }
}
