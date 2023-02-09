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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core\orm\query\builder;
use core\pagination\offset_cursor as cursor;
use core\entity\user;
use core_phpunit\testcase;
use mod_approval\data_provider\application\applications_for_applicant;
use mod_approval\data_provider\application\applications_for_others;
use mod_approval\data_provider\application\capability_map\capability_map_controller;
use mod_approval\data_provider\application\role_map\role_map_controller;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\assignment;
use mod_approval\model\form\form_data;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @coversDefaultClass \mod_approval\data_provider\application\applications_for_applicant
 *
 * @group approval_workflow
 * @group applications_dashboard
 */
class mod_approval_data_provider_applications_for_applicant_testcase extends testcase {

    use approval_workflow_test_setup;

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * @covers ::process_fetched_items
     */
    public function test_is_application_model() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();

        // Create a provider
        $provider = new applications_for_applicant($user1->id);

        // Create an application as user1
        $this->set_user_with_capability_maps($user1);
        $user_entity = new user($user1->id);
        $this->create_application($workflow, $assignment, $user_entity);
        $applications = $provider->fetch()->get();
        $this->assertInstanceOf(application::class, $applications->first());
    }

    /**
     * @covers ::build_query
     */
    public function test_exclusive_to_applicant() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user_entity2 = new user($user2->id);

        // Create a data provider for each
        $this->set_user_with_capability_maps($user1);
        $user1_provider = new applications_for_applicant($user1->id);
        $this->set_user_with_capability_maps($user2);
        $user2_provider = new applications_for_applicant($user2->id);

        // Create two applications as user1
        $this->setUser($user1);
        for ($i = 0; $i < 2; $i++) {
            $this->create_application($workflow, $assignment, $user_entity1);
        }
        // Create two applications as user2
        $this->setUser($user2);
        for ($i = 0; $i < 2; $i++) {
            $this->create_application($workflow, $assignment, $user_entity2);
        }
        // Create a third application as user1
        $this->setUser($user1);
        $this->create_application($workflow, $assignment, $user_entity1);

        // Check limiting by user id
        $user1_applications = $user1_provider->fetch()->get();
        $this->assertEquals(3, $user1_applications->count());
        $user2_applications = $user2_provider->fetch()->get();
        $this->assertEquals(2, $user2_applications->count());

        // Check that each user's applications belong to them
        foreach ($user1_applications as $application) {
            $this->assertEquals($user1->id, $application->user_id);
        }
        foreach ($user2_applications as $application) {
            $this->assertEquals($user2->id, $application->user_id);
        }

        // Delete user2
        delete_user($user2);
        $user1_applications = $user1_provider->fetch()->get();
        $this->assertEquals(3, $user1_applications->count());
        $user2_applications = $user2_provider->fetch()->get();
        $this->assertEquals(0, $user2_applications->count());
    }

    /**
     * @covers ::build_query
     */
    public function test_state_visibility_to_applicant() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user_entity2 = new user($user2->id);

        // Create a data provider for each
        $this->set_user_with_capability_maps($user1);
        $user1_provider = new applications_for_applicant($user1->id);
        $this->set_user_with_capability_maps($user2);
        $user2_provider = new applications_for_applicant($user2->id);
        // Also for admin
        $this->setAdminUser();
        capability_map_controller::regenerate_all_maps(get_admin()->id);
        $admin_provider = new applications_for_applicant(get_admin()->id);
        $admin_provider_from_others = new applications_for_others(get_admin()->id);

        // Create one application as admin for user1 and user2. These applications are in DRAFT state and could be seen by owner
        $application1 = $this->create_application($workflow, $assignment, $user_entity1);
        $application2 = $this->create_application($workflow, $assignment, $user_entity2);

        // Check if users can see applications created for them in DRAFT state
        $user1_applications = $user1_provider->fetch()->get();
        $this->assertEquals(0, $user1_applications->count());
        $user2_applications = $user2_provider->fetch()->get();
        $this->assertEquals(0, $user2_applications->count());
        $admin_applications = $admin_provider->fetch()->get();
        $this->assertEquals(0, $admin_applications->count());

        // And applications created on behalf have a different applicant from the creator/owner
        foreach ($admin_applications as $application) {
            $this->assertNotEquals(get_admin()->id, $application->user_id);
        }

        $form_data = form_data::from_json('{"agency_code":"hurray!"}');
        $submission = application_submission::create_or_update($application1, $application1->user_id, $form_data);
        $submission->publish(user::logged_in()->id);
        submit::execute($application1, user::logged_in()->id);

        $submission = application_submission::create_or_update($application2, $application2->user_id, $form_data);
        $submission->publish(user::logged_in()->id);
        submit::execute($application2, user::logged_in()->id);

        // Check if users can see applications created for them in IN_PROGRESS state
        $user1_applications = $user1_provider->fetch()->get();
        $this->assertEquals(1, $user1_applications->count());
        $user2_applications = $user2_provider->fetch()->get();
        $this->assertEquals(1, $user2_applications->count());
        $admin_applications = $admin_provider->fetch()->get();
        $this->assertEquals(0, $admin_applications->count());

        // Admin can see submitted applications in "Applications from others"
        $applications = $admin_provider_from_others->fetch()->get();
        $this->assertCount(2, $applications);
    }

    /**
     * @covers ::build_query
     */
    public function test_visibility_checks() {
        $this->setAdminUser();
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment();
        $this->assertNotEquals($workflow1->id, $workflow2->id);
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        $this->set_user_with_capability_maps($user1);
        $user1_provider = new applications_for_applicant($user1->id);

        // Create two applications as user1
        $this->create_application($workflow1, $assignment1, $user_entity1);
        $this->create_application($workflow2, $assignment2, $user_entity1);
        $user1_applications = $user1_provider->fetch()->get();
        $this->assertEquals(2, $user1_applications->count());
    }

    /**
     * @covers ::build_query
     */
    public function test_capability_checks() {
        $this->setAdminUser();
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment();
        $this->assertNotEquals($workflow1->id, $workflow2->id);
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        $this->set_user_with_capability_maps($user1);
        $user1_provider = new applications_for_applicant($user1->id);

        // And one submitted application as admin so user not an owner
        $this->setAdminUser();
        $this->create_submitted_application($workflow1, $assignment1, $user_entity1);

        // Create application as user1
        $this->setUser($user1);
        $this->create_application($workflow2, $assignment2, $user_entity1);

        $user1_applications = $user1_provider->fetch()->get();
        $this->assertEquals(2, $user1_applications->count());

        // Prohibit user from viewing applications in workflow1
        $role = builder::table('role')->where('shortname', 'user')->one();
        $assignment1_model = assignment::load_by_entity($assignment1);
        assign_capability('mod/approval:view_in_dashboard_application_applicant', CAP_PREVENT, $role->id, $assignment1_model->get_context()->id,true);
        role_map_controller::regenerate_all_maps();
        capability_map_controller::regenerate_all_maps($user1->id);

        $user1_provider = new applications_for_applicant($user1->id);
        $user1_applications = $user1_provider->fetch()->get();
        $this->assertEquals(1, $user1_applications->count());
        foreach ($user1_applications as $item) {
            $this->assertEquals($workflow2->id, $item->workflow_version->workflow_id);
        }

        // Assign mod/approval:view_in_dashboard_application_applicant to user to check if he can see wokrflow1 again
        assign_capability('mod/approval:view_in_dashboard_application_applicant', CAP_ALLOW, $role->id, $assignment1_model->get_context()->id, true);
        role_map_controller::regenerate_all_maps();
        capability_map_controller::regenerate_all_maps($user1->id);
        $user1_provider = new applications_for_applicant($user1->id);
        $user1_applications = $user1_provider->fetch()->get();
        $this->assertEquals(2, $user1_applications->count());
    }

    /**
     * @covers ::sort_query_by_newest_first
     * @covers ::sort_query_by_oldest_first
     */
    public function test_sorting_by_newest_oldest_first() {
        global $DB;

        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        // Create a data provider for each sorting
        $this->set_user_with_capability_maps($user1);
        $newest_provider = (new applications_for_applicant($user1->id))->sort_by('newest_first');
        $oldest_provider = (new applications_for_applicant($user1->id))->sort_by('oldest_first');

        // Create three applications as user1
        $apps = [];
        for ($i = 0; $i < 3; $i++) {
            $apps[$i] = $this->create_application($workflow, $assignment, $user_entity1);
        }

        // Backdate the 2nd one
        $record = new stdClass();
        $record->id = $apps[1]->id;
        $record->created = $apps[1]->created - 3600;
        $DB->update_record('approval_application', $record);

        // Test newest first
        $applications = $newest_provider->fetch()->get();
        $this->assertEquals(3, $applications->count());
        $this->assertEquals($apps[2]->id, $applications->first()->id);
        $this->assertEquals($apps[1]->id, $applications->last()->id);

        // Test oldest first
        $applications = $oldest_provider->fetch()->get();
        $this->assertEquals(3, $applications->count());
        $this->assertEquals($apps[1]->id, $applications->first()->id);
        $this->assertEquals($apps[2]->id, $applications->last()->id);
    }

    /**
     * @covers ::sort_query_by_submitted
     */
    public function test_sorting_by_submitted() {
        global $DB;

        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        // Create a data provider for sorting
        $this->set_user_with_capability_maps($user1);
        $provider = (new applications_for_applicant($user1->id))->sort_by('submitted');

        // Create some applications as user1, some draft some submitted to test COALESCE
        $apps = [];
        $apps[0] = $this->create_application($workflow, $assignment, $user_entity1);

        $apps[1] = $this->create_application($workflow, $assignment, $user_entity1);
        $submission = application_submission::create_or_update($apps[1], $user1->id, form_data::create_empty());
        $submission->publish($user1->id);
        submit::execute($apps[1], $user1->id);

        $apps[2] = $this->create_application($workflow, $assignment, $user_entity1);
        $submission = application_submission::create_or_update($apps[2], $user1->id, form_data::create_empty());
        $submission->publish($user1->id);
        submit::execute($apps[2], $user1->id);

        $apps[3] = $this->create_application($workflow, $assignment, $user_entity1);
        $submission = application_submission::create_or_update($apps[3], $user1->id, form_data::create_empty());
        $submission->publish($user1->id);
        submit::execute($apps[3], $user1->id);

        $apps[4] = $this->create_application($workflow, $assignment, $user_entity1);

        // Backdate the 1st draft application to one hour ago.
        $record0 = new stdClass();
        $record0->id = $apps[0]->id;
        $record0->created = $apps[0]->created - 3600;
        $record0->updated = $apps[0]->updated - 3600;
        $DB->update_record('approval_application', $record0);

        // Backdate the first submitted application to created yesterday, submitted 2 hours ago.
        $record1 = new stdClass();
        $record1->id = $apps[1]->id;
        $record1->created = $apps[1]->created - 86400;
        $record1->updated = $apps[1]->updated - 7200;
        $record1->submitted = $apps[1]->submitted - 7200;
        $DB->update_record('approval_application', $record1);

        // Order should be apps[3], apps[2], apps[1], apps[4], apps[0]. Application in DRAFT state always in the end
        $expected_keys = [
            $apps[3]->id,
            $apps[2]->id,
            $apps[1]->id,
            $apps[4]->id,
            $apps[0]->id,
        ];

        // Test sorting
        $applications = $provider->fetch()->get();
        $this->assertEquals(5, $applications->count());
        $this->assertEquals($expected_keys, $applications->keys());
    }

    /**
     * @covers ::sort_query_by_workflow_type_name
     */
    public function test_sorting_by_workflow_type_name() {
        $this->setAdminUser();
        // Creates workflow_type name = Testing
        list($workflow1, , $assignment1) = $this->create_workflow_and_assignment();
        list($workflow2, , $assignment2) = $this->create_workflow_and_assignment('Apple');
        list($workflow3, , $assignment3) = $this->create_workflow_and_assignment('Zebra');
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        // Create a data provider for sorting
        $this->set_user_with_capability_maps($user1);
        $provider = (new applications_for_applicant($user1->id))->sort_by('workflow_type_name');

        // Create some applications as user1, some draft some submitted to test COALESCE
        $apps = [];
        $apps[0] = $this->create_application($workflow1, $assignment1, $user_entity1);
        $apps[1] = $this->create_submitted_application($workflow2, $assignment2, $user_entity1);
        $apps[2] = $this->create_submitted_application($workflow3, $assignment3, $user_entity1);
        $apps[3] = $this->create_submitted_application($workflow2, $assignment2, $user_entity1);
        $apps[4] = $this->create_application($workflow1, $assignment1, $user_entity1);

        // Order should be apps[3], apps[1], apps[4], apps[0], apps[2]
        $expected_keys = [
            $apps[3]->id,
            $apps[1]->id,
            $apps[4]->id,
            $apps[0]->id,
            $apps[2]->id,
        ];

        // Test sorting
        $applications = $provider->fetch()->get();
        $this->assertEquals(5, $applications->count());
        $this->assertEquals($expected_keys, $applications->keys());
    }

    /**
     * @return array title or id_number
     */
    public function data_title_or_id_number(): array {
        return [['title'], ['id_number']];
    }

    /**
     * @covers ::sort_query_by_title
     * @covers ::sort_query_by_id_number
     * @dataProvider data_title_or_id_number
     */
    public function test_sort_query_by_title_or_id_number(string $field) {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user = new user($this->getDataGenerator()->create_user());
        $this->set_user_with_capability_maps($user);
        $app1 = $this->create_application($workflow, $assignment, $user);
        $app2 = $this->create_application($workflow, $assignment, $user);
        $app3 = $this->create_application($workflow, $assignment, $user);
        builder::table('approval_application')->where('id', $app1->id)->update([$field => 'P']);
        builder::table('approval_application')->where('id', $app2->id)->update([$field => 'p']);
        builder::table('approval_application')->where('id', $app3->id)->update([$field => 'A']);
        $provider = (new applications_for_applicant($user->id))->sort_by($field);
        $applications = $provider->fetch()->get();
        $expected = [$app3->id, $app2->id, $app1->id];
        $this->assertEquals($expected, $applications->keys());
    }

    /**
     * @covers ::filter_query_by_application_id
     * @covers \mod_approval\data_provider\application\filter\application_id::apply
     */
    public function test_filter_by_application_id() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        // Create a provider
        $this->set_user_with_capability_maps($user1);
        $provider = new applications_for_applicant($user1->id);

        // Create three applications as user1
        $apps = [];
        for ($i = 0; $i < 5; $i++) {
            $apps[$i] = $this->create_application($workflow, $assignment, $user_entity1);
        }

        // Test with one ID
        $provider->add_filters(['application_id' => $apps[1]->id]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(1, $applications->count());
        $this->assertEquals($apps[1]->id, $applications->first()->id);

        // Test with two IDs
        $provider = new applications_for_applicant($user1->id);
        $provider->add_filters(['application_id' => [$apps[1]->id, $apps[4]->id]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(2, $applications->count());
        $this->assertEquals($apps[1]->id, $applications->first()->id);
        $this->assertEquals($apps[4]->id, $applications->last()->id);
    }

    /**
     * @covers ::filter_query_by_overall_progress
     */
    public function test_filter_by_overall_progress() {

        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        // Create a provider
        $this->set_user_with_capability_maps($user1);
        $provider = new applications_for_applicant($user1->id);

        // Create three applications as user1
        $apps = [];
        for ($i = 0; $i < 5; $i++) {
            $apps[$i] = $this->create_application($workflow, $assignment, $user_entity1);
        }

        // Test with the DRAFT filter
        $provider->add_filters(['overall_progress' => ["DRAFT"]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(5, $applications->count());
        $this->assertEquals($apps[1]->current_state, $applications->first()->current_state);

        // Submit all the applications.
        for ($i = 0; $i < 5; $i++) {
            $submission = application_submission::create_or_update($apps[$i], $user1->id, form_data::create_empty());
            $submission->publish($user1->id);
            submit::execute($apps[$i], $user1->id);
        }

        // Test with the DRAFT filter again
        $provider->add_filters(['overall_progress' => ["DRAFT"]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(0, $applications->count());

        // Withdraw one application.
        withdraw_in_approvals::execute($apps[1], $user1->id);

        // Mark another as completed.
        $final_stage = $apps[2]->get_next_stage();
        $apps[2]->set_current_state(new application_state($final_stage->id));

        // Mark another as rejected.
        reject::execute($apps[3], $user1->id);

        // Test with the IN_PROGRESS filter
        $provider->add_filters(['overall_progress' => ["IN_PROGRESS"]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(2, $applications->count());

        // Test with the REJECTED filter
        $provider->add_filters(['overall_progress' => ["REJECTED"]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(1, $applications->count());

        // Test with the WITHDRAWN filter
        $provider->add_filters(['overall_progress' => ["WITHDRAWN"]]);
        $applications = $provider->fetch()->get();
        $this->assertEquals(1, $applications->count());

        // Test with the invalid filter
        $provider = new applications_for_others($user1->id);
        $provider->add_filters(['overall_progress' => ["APPROVED"]]);
        self::expectException(invalid_parameter_exception::class);
        self::expectExceptionMessage('invalid value(s): APPROVED');
        $provider->fetch()->get();
    }

    /**
     * @covers ::get_paginator
     */
    public function test_paged_results() {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $user_entity1 = new user($user1->id);

        // Create 12 applications as user1
        $this->set_user_with_capability_maps($user1);
        $apps = [];
        for ($i = 0; $i < 12; $i++) {
            $apps[$i] = $this->create_application($workflow, $assignment, $user_entity1);
        }

        // Set a page size of 5
        $item_counts = [5, 5, 2];
        $expected_ids = [
            0 => [$apps[0]->id, $apps[1]->id, $apps[2]->id, $apps[3]->id, $apps[4]->id],
            1 => [$apps[5]->id, $apps[6]->id, $apps[7]->id, $apps[8]->id, $apps[9]->id],
            2 => [$apps[10]->id, $apps[11]->id]
        ];
        $cursor = cursor::create()->set_limit(5);
        for ($i = 0; $i < 3; $i++) {
            $paginator = (new applications_for_applicant($user1->id))
                ->get_paginator($cursor);

            $items = $paginator->get_items();
            $this->assertCount($item_counts[$i], $items);
            $actual_ids = $items->pluck('id');
            // Order should be the same
            $this->assertSame($expected_ids[$i], $actual_ids);

            $cursor = $paginator->get_next_cursor();
        }

        // Set a page size of 4
        $item_counts = [4, 4, 4];
        $expected_ids = [
            0 => [$apps[0]->id, $apps[1]->id, $apps[2]->id, $apps[3]->id],
            1 => [$apps[4]->id, $apps[5]->id, $apps[6]->id, $apps[7]->id],
            2 => [$apps[8]->id, $apps[9]->id, $apps[10]->id, $apps[11]->id]
        ];
        $cursor = cursor::create()->set_limit(4);
        for ($i = 0; $i < 3; $i++) {
            $paginator = (new applications_for_applicant($user1->id))
                ->get_paginator($cursor);

            $items = $paginator->get_items();
            $this->assertCount($item_counts[$i], $items);
            $actual_ids = $items->pluck('id');
            // Order should be the same
            $this->assertSame($expected_ids[$i], $actual_ids);

            $cursor = $paginator->get_next_cursor();
        }

        $this->assertNull($cursor);
    }
}
