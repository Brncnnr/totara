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
use mod_approval\totara_notification\placeholder\approval_level as workflow_stage_approval_level_placeholder_group;
use mod_approval\testing\approval_workflow_test_setup;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/totara_notification_base.php');

abstract class mod_approval_totara_notification_level_base_testcase extends mod_approval_totara_notification_base_testcase {
    protected function setup_applications(): stdClass {
        $result = parent::setup_assignments();

        $result->application1 = $this->create_submitted_application(
            $result->workflow,
            $result->assignment,
            $result->applicant1,
            $result->applicant1_job->id
        );
        $result->application2 = $this->create_submitted_application(
            $result->workflow,
            $result->assignment,
            $result->applicant2
        );

        return $result;
    }
}
