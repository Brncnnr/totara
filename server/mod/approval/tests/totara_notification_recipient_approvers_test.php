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
use mod_approval\entity\workflow\workflow;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\model\application\application_state;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment as assignment_model;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\workflow\workflow_stage_approval_level as workflow_stage_approval_level_model;
use mod_approval\model\workflow\workflow_version as workflow_version_model;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\totara_notification\recipient\approvers;

defined('MOODLE_INTERNAL') || die();

/**
 * @group approval_workflow
 * @group totara_notification
 */
class mod_approval_totara_notification_recipient_approvers_testcase extends testcase {

    use approval_workflow_test_setup;

    public function test_get_name(): void {
        self::assertEquals(get_string('notification:recipient_approvers', 'mod_approval'), approvers::get_name());
    }

    public function test_get_user_ids(): void {
        $generator = $this->getDataGenerator();

        /** @var workflow $workflow */
        list($workflow, , $assignment_entity) = $this->create_workflow_and_assignment();
        $user = new user($generator->create_user()->id);
        $application = $this->create_application($workflow, $assignment_entity, $user);

        $workflow_version = workflow_version_model::load_latest_by_workflow_id($workflow->id);
        $stage_1 = $workflow_version->stages->first();
        $workflow_stage = $workflow_version->get_next_stage($stage_1->id);
        /** @var workflow_stage_approval_level_entity $approval_level */
        $approval_level = workflow_stage_approval_level_entity::repository()
            ->where('workflow_stage_id', '=', $workflow_stage->id)
            ->order_by('id')
            ->first();

        // Initially there are no approvers.
        self::assertEmpty( approvers::get_user_ids([
            'application_id' => $application->id,
            'approval_level_id' => $approval_level->id,
        ]));

        // Add an approver.
        $approver = $generator->create_user();
        $assignment_model = assignment_model::load_by_entity($assignment_entity);
        $approval_level_model = workflow_stage_approval_level_model::load_by_entity($approval_level);
        assignment_approver::create($assignment_model, $approval_level_model, user_approver_type::TYPE_IDENTIFIER, $approver->id);
        $next_state = $application->current_stage->state_manager->get_next_state($application->current_state);
        $application->set_current_state($next_state);

        self::assertEquals([$approver->id], approvers::get_user_ids([
            'application_id' => $application->id,
            'approval_level_id' => $approval_level->id,
        ]));
    }
}
