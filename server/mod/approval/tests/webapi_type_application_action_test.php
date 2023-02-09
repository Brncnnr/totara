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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core\format;
use core_phpunit\testcase;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\model\application\action\action;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\form\form_data;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\type\application_action
 */
class mod_approval_webapi_type_application_action_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_application_action';

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Wrong object is passed');

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        $context = $this->createMock(context_module::class);
        $action = $this->createMock(application_action::class);
        $field = 'unknown';
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $action, [], $context);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        list($actions, $user2, $context) = $this->create_application_action();

        // Test APPROVE
        $action = $actions[0];
        $action_label = action::from_code($action->code);
        $label = $action_label::get_label()->out();
        $value = $this->resolve_graphql_type(self::TYPE, 'id', $action, [], $context);
        $this->assertEquals($action->id, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'label', $action, ['format' => format::FORMAT_PLAIN], $context);
        $this->assertEquals($label, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'user', $action, [], $context);
        $this->assertEquals($user2->fullname, $value->fullname);

        // Test WITHDRAWN
        $action = $actions[1];
        $action_label = action::from_code($action->code);
        $label = $action_label::get_label()->out();
        $value = $this->resolve_graphql_type(self::TYPE, 'id', $action, [], $context);
        $this->assertEquals($action->id, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'label', $action, ['format' => format::FORMAT_PLAIN], $context);
        $this->assertEquals($label, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'user', $action, [], $context);
        $this->assertEquals($action->user->fullname, $value->fullname);

        // Test REJECT
        $action = $actions[2];
        $action_label = action::from_code($action->code);
        $label = $action_label::get_label()->out();
        $value = $this->resolve_graphql_type(self::TYPE, 'id', $action, [], $context);
        $this->assertEquals($action->id, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'label', $action, ['format' => format::FORMAT_PLAIN], $context);
        $this->assertEquals($label, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'user', $action, [], $context);
        $this->assertEquals($user2->fullname, $value->fullname);
    }

    /**
     * @return array
     */
    private function create_application_action(): array {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $workflow = workflow::load_by_entity($workflow);
        $workflow_version = $workflow->latest_version;
        workflow_version_entity::repository()
            ->where('id', $workflow_version->id)
            ->update([
                'status' => status::DRAFT
            ]);
        $workflow_version->refresh();

        $assignment = assignment::load_by_entity($assignment);
        /** @var workflow_stage $stage1*/
        $stage1 = $workflow_version->stages->first();
        $stage2 = $workflow_version->get_next_stage($stage1->id);

        // Add another level, otherwise application will be completed when approver approves it.
        $stage2->add_approval_level('Level 2');

        // Add an approver for level1.
        $approver = new user($this->getDataGenerator()->create_user()->id);
        $level1 = $stage2->approval_levels->first();
        assignment_approver::create($assignment, $level1, user_approver_type::TYPE_IDENTIFIER, $approver->id);
        $workflow->publish($workflow_version);

        $user1 = new user($this->getDataGenerator()->create_user([
            'firstname' => 'Tommy',
            'lastname' => 'Tom',
            'middlename' => '',
        ]));
        $application = application::create($workflow->latest_version, $assignment, $user1->id);
        $application2 = application::create($workflow->latest_version, $assignment, $user1->id);
        $application3 = application::create($workflow->latest_version, $assignment, $user1->id);

        $this->setUser($user1);
        $submission = application_submission::create_or_update(
            $application,
            $user1->id,
            form_data::from_json('{"agency_code":"what?"}')
        );
        $submission2 = application_submission::create_or_update(
            $application2,
            $user1->id,
            form_data::from_json('{"agency_code":"where?"}')
        );
        $submission3 = application_submission::create_or_update(
            $application3,
            $user1->id,
            form_data::from_json('{"agency_code":"when?"}')
        );
        $submission->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);
        $submission2->publish(user::logged_in()->id);
        submit::execute($application2, user::logged_in()->id);
        $submission3->publish(user::logged_in()->id);
        submit::execute($application3, user::logged_in()->id);

        $actions = [];
        $actions[0] = application_action::create($application, $approver->id, new approve());
        $actions[1] = application_action::create($application2, $user1->id, new withdraw_in_approvals());
        $actions[2] = application_action::create($application3, $approver->id, new reject());

        $context = $assignment->get_context();
        return [$actions, $approver, $context];
    }

}
