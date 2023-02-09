<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\enrol\manager;
use container_workspace\loader\member\loader;
use container_workspace\member\member;
use container_workspace\query\member\query;
use container_workspace\query\member\sort;
use core\entity\enrol;
use core\entity\user_enrolment;
use core_phpunit\testcase;

/**
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_member_loader_testcase extends testcase {
    /**
     * @return void
     */
    public function test_load_members_that_load_admin_first(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        for ($i = 0; $i < 10; $i++) {
            $user = $generator->create_user();
            member::join_workspace($workspace, $user->id);
        }


        $query = new query($workspace->get_id());
        $result_one = loader::get_members($query);

        // 10 members plus admin.
        $this->assertEquals(11, $result_one->get_total());
        $result_one_items = $result_one->get_items()->all();

        // First item should be the user one as the user is an owner of workspace.
        /** @var member $result_one_first */
        $result_one_first = reset($result_one_items);
        $this->assertInstanceOf(member::class, $result_one_first);

        $this->assertEquals($user_one->id, $result_one_first->get_user_id());

        $query->set_sort(sort::RECENT_JOIN);
        $result_two = loader::get_members($query);

        $this->assertEquals(11, $result_two->get_total());
        $result_two_items = $result_two->get_items()->all();

        /** @var member $result_two_first */
        $result_two_first = reset($result_two_items);
        $this->assertInstanceOf(member::class, $result_two_first);

        $this->assertEquals($user_one->id, $result_two_first->get_user_id());
    }

    /**
     * @return void
     */
    public function test_load_members_does_not_include_members_from_different_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $workspace_one = $workspace_generator->create_workspace();
        $workspace_two = $workspace_generator->create_workspace();

        // Add 5 members to the first workspace and 3 to second workspace.
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            member::join_workspace($workspace_one, $user->id);
        }

        $users_in_workspace_two = [];
        for ($i = 0; $i < 3; $i++) {
            $user = $generator->create_user();
            member::join_workspace($workspace_two, $user->id);

            $users_in_workspace_two[] = $user->id;
        }

        // Now load the members of workspace one - that it should not return any records from workspace two's members
        $query = new query($workspace_one->get_id());
        $result = loader::get_members($query);

        // 5 members plus our special user_one.
        $this->assertEquals(6, $result->get_total());
        $members = $result->get_items()->all();

        /** @var member $member */
        foreach ($members as $member) {
            if ($user_one->id == $member->get_user_id()) {
                // Skip the owner.
                continue;
            }

            $this->assertFalse(in_array($member->get_user_id(), $users_in_workspace_two));
        }
    }

    /**
     * @return void
     */
    public function test_search_from_members_with_case_insensitive(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();

        $user_two = $generator->create_user([
            'firstname' => 'User',
            'lastname' => 'Two'
        ]);

        $user_three = $generator->create_user([
            'firstname' => 'User',
            'lastname' => 'Three'
        ]);

        // Log in as first user to create a workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add user two and three to the workspace.
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);
        member::added_to_workspace($workspace, $user_three->id, false, $user_one->id);

        // Fetch for user two with search term.
        $query = new query($workspace->get_id());
        $query->set_search_term('two');

        $user_two_result = loader::get_members($query);
        self::assertEquals(1, $user_two_result->get_total());

        /** @var member[] $user_two_members */
        $user_two_members = $user_two_result->get_items()->all();
        $user_two_member = reset($user_two_members);

        self::assertEquals($user_two->id, $user_two_member->get_user_id());

        // Search for user three.
        $query->set_search_term('user three');
        $user_three_result = loader::get_members($query);

        self::assertEquals(1, $user_three_result->get_total());

        /** @var member[] $user_three_members */
        $user_three_members = $user_three_result->get_items()->all();
        $user_three_member = reset($user_three_members);

        self::assertEquals($user_three->id, $user_three_member->get_user_id());
    }

    /**
     * @return void
     */
    public function test_search_from_members_with_middle_name(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add two users to the workspace.
        $user_two = $generator->create_user([
            'firstname' => 'John',
            'middlename' => 'bolobala',
            'lastname' => 'Doe',
        ]);
        $user_three = $generator->create_user([
            'firstname' => 'Jill',
            'middlename' => 'bob',
            'lastname' => 'Harris',
        ]);

        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);
        member::added_to_workspace($workspace, $user_three->id, false, $user_one->id);

        $query = new query($workspace->get_id());
        $query->set_search_term('bolobala');

        $result = loader::get_members($query);
        // With the default fullname setting we don't expect to get a result back
        self::assertEquals(0, $result->get_total());

        set_config('fullnamedisplay', 'firstname middlename lastname');

        $result = loader::get_members($query);
        self::assertEquals(1, $result->get_total());

        /** @var member[] $members */
        $members = $result->get_items()->all();
        self::assertCount(1, $members);

        $member = reset($members);
        self::assertEquals($user_two->id, $member->get_user_id());
    }

    /**
     * Test that if a user has multiple memberships, but one is disabled, they're still considered active.
     */
    public function test_member_with_multiple_memberships(): void {
        global $CFG, $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user_two = $generator->create_user();

        // Enrol them twice, we're doing this directly to bypass unique checks
        $role = member::get_role_for_members();
        require_once("{$CFG->dirroot}/lib/enrollib.php");
        foreach (['manual', 'self'] as $plugin_name) {
            $plugin = enrol_get_plugin($plugin_name);
            $enrol_instance = $DB->get_record(
                'enrol',
                [
                    'enrol' => $plugin->get_name(),
                    'courseid' => $workspace->get_id(),
                    'status' => ENROL_INSTANCE_ENABLED
                ],
                '*',
                IGNORE_MISSING
            );

            $plugin->enrol_user($enrol_instance, $user_two->id);
        }
        role_assign($role->id, $user_two->id, $workspace->get_context()->id, 'container_workspace');

        // Check we have two enrolments
        $enrolments = user_enrolment::repository()
            ->select('ue.*')
            ->as('ue')
            ->join([enrol::TABLE, 'e'], 'ue.enrolid', 'e.id')
            ->where('e.courseid', $workspace->get_id())
            ->where('ue.userid', $user_two->id)
            ->get();
        self::assertCount(2, $enrolments);

        // Confirm they're an active member
        $member = member::from_user($user_two->id, $workspace->get_id());
        self::assertTrue($member->is_active());

        // Disable one of the enrolments
        $enrolment = $enrolments->current();
        $DB->set_field(user_enrolment::TABLE, 'status', ENROL_USER_SUSPENDED, ['id' => $enrolment->id]);

        // Confirm there's only one active one
        $count = user_enrolment::repository()
            ->select('ue.*')
            ->as('ue')
            ->join([enrol::TABLE, 'e'], 'ue.enrolid', 'e.id')
            ->where('e.courseid', $workspace->get_id())
            ->where('ue.userid', $user_two->id)
            ->where('ue.status', ENROL_USER_ACTIVE)
            ->count();
        self::assertSame(1, $count);

        // Confirm they're still an active member
        $member = member::from_user($user_two->id, $workspace->get_id());
        self::assertTrue($member->is_active());

        // Disable the other enrolment
        $enrolments->next();
        $enrolment = $enrolments->current();
        $DB->set_field(user_enrolment::TABLE, 'status', ENROL_USER_SUSPENDED, ['id' => $enrolment->id]);

        // Confirm they're no longer an active member
        $member = member::from_user($user_two->id, $workspace->get_id());
        self::assertFalse($member->is_active());
    }

    /**
     * Test get members ordered by recently joined when a user has both manual and audience membership
     *
     * @return void
     */
    public function test_get_members_ordered_by_recently_joined(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user([
            'lastname' => 'Adeola',
            'firstname' => 'Ayo',
        ]);
        $user_two = $generator->create_user([
            'lastname' => 'Zimer',
            'firstname' => 'Zuche',
        ]);
        $user_three = $generator->create_user([
            'lastname' => 'Callum',
            'firstname' => 'Chambers',
        ]);
        $cohort = $generator->create_cohort();
        cohort_add_member($cohort->id, $user_one->id);
        cohort_add_member($cohort->id, $user_two->id);
        cohort_add_member($cohort->id, $user_three->id);

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();
        $time = time();

        // User two joins
        member::join_workspace($workspace, $user_two->id);

        // Move user two enrolment date to the near future
        user_enrolment::repository()
            ->where('userid', $user_two->id)
            ->update([
                'timemodified' => $time + 10,
            ]);

        // Audience containing users added
        $enrol_manager = manager::from_workspace($workspace);
        $enrol_manager->enrol_audiences([$cohort->id]);
        $this->executeAdhocTasks();

        $cohort_user_enrolments = user_enrolment::repository()
            ->as('ue')
            ->join([enrol::TABLE, 'e'], 'enrolid', 'id')
            ->select('ue.id')
            ->where('e.enrol', 'cohort')
            ->where('e.courseid', $workspace->id)
            ->get();

        // Move cohort users enrolment dates to the far future
        user_enrolment::repository()
            ->where_in('id', $cohort_user_enrolments->pluck('id'))
            ->update([
                'timemodified' => $time + 100,
            ]);

        $query = new query($workspace->id);
        $query->set_sort(sort::RECENT_JOIN);
        $members_paginator = loader::get_members($query);

        $this->assertEquals(3, $members_paginator->get_total());
        /** @var member[] $members*/
        $members = $members_paginator->get_items()->all();

        // Workspace owner is always the first in the list.
        $this->assertEquals($user_one->id, $members[0]->get_user_id());
        $this->assertEquals($user_three->id, $members[1]->get_user_id());
        $this->assertEquals($user_two->id, $members[2]->get_user_id());
    }
}