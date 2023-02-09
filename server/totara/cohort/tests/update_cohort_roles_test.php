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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test updating totara cohort roles task
 *
 * @group totara_cohort
 */
class update_cohort_roles_test extends advanced_testcase {

    private $cohort1 = null;
    private $cohort2 = null;
    private $category1 = null;
    private $cohort1_users = [];
    private $cohort2_users = [];
    private $cohort_generator = null;

    /**
     * Setup
     * Create 2 cohorts with two members each one. The course are assigned in the sys context and we are assigning all
     * "assignable" roles for each of them.
     *
     * @return void
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        $this->setAdminUser();

        // Set totara_cohort generator.
        $this->cohort_generator = \totara_cohort\testing\generator::instance();

        // Create users.
        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2']);
        $user3 = $this->getDataGenerator()->create_user(['username' => 'user3']);
        $user4 = $this->getDataGenerator()->create_user(['username' => 'user4']);

        // Create a category
        $this->category1 = $this->getDataGenerator()->create_category();

        // Get contexts to assign cohorts.
        $syscontext = context_system::instance();

        // Create cohorts.
        $this->cohort1 = $this->cohort_generator->create_cohort(
            [
                'cohorttype' => cohort::TYPE_STATIC,
                'contextid' => $syscontext->id,
            ]
        );
        $this->cohort2 = $this->cohort_generator->create_cohort(
            [
                'cohorttype' => cohort::TYPE_STATIC,
                'contextid' => $syscontext->id,
            ]
        );

        // Assign users to cohorts.
        $this->cohort_generator->cohort_assign_users($this->cohort1->id, [$user1->id, $user2->id]);
        $this->cohort_generator->cohort_assign_users($this->cohort2->id, [$user3->id, $user4->id]);

        $this->cohort1_users = [$user1, $user2];
        $this->cohort2_users = [$user3, $user4];

        // Get list of assignable roles.
        $assignableroles = get_assignable_roles($syscontext, ROLENAME_BOTH, false);
        $this->assertNotEmpty($assignableroles, 'There are no roles to assign in the system context');

        // Assign cohort roles for cohort 1.
        // Make an array of key => values (roles => context) needed to process the assignment.
        foreach ($assignableroles as $key => $value) {
            $roles[$key] = $syscontext->id;
        }

        // Assign roles to the cohort and verify it was successful.
        $this->assertTrue(totara_cohort_process_assigned_roles($this->cohort1->id, $roles));

        // First validation: Roles were assigned in the cohort_role table.
        $this->assertEquals(array_keys($assignableroles), array_keys(totara_get_cohort_roles($this->cohort1->id)));

        // Second validation: Roles were assigned in the role_assignments table.
        $countmembers = count(totara_get_members_cohort($this->cohort1->id));
        foreach ($assignableroles as $key => $value) {
            $this->assertEquals($countmembers, $DB->count_records('role_assignments',
                [
                    'itemid'    => $this->cohort1->id,
                    'contextid' => $syscontext->id,
                    'roleid'    => $key
                ]
            ));
        }

        // Assign cohort roles for cohort 2. Basic validation as it should be the working as above.
        $this->assertTrue(totara_cohort_process_assigned_roles($this->cohort2->id, $roles));
        $this->assertEquals(array_keys($assignableroles), array_keys(totara_get_cohort_roles($this->cohort2->id)));
        $countmembers = count(totara_get_members_cohort($this->cohort2->id));
        foreach ($assignableroles as $key => $value) {
            $this->assertEquals($countmembers, $DB->count_records('role_assignments',
                [
                    'itemid'    => $this->cohort2->id,
                    'contextid' => $syscontext->id,
                    'roleid'    => $key
                ]
            ));
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->cohort1 = null;
        $this->cohort2 = null;
        $this->category1 = null;
        $this->cohort1_users = null;
        $this->cohort2_users = null;
        $this->cohort_generator = null;

        parent::tearDown();
    }

    /**
     * Test nothing is changed is the cohort's context has not changed.
     *
     * @return void
     */
    public function test_update_cohort_when_context_change(): void {
        global $DB;
        $syscontext = context_system::instance();
        $catcontext = context_coursecat::instance($this->category1->id);

        // Update cohort1 context.
        $this->cohort1->contextid = $catcontext->id;
        cohort_update_cohort($this->cohort1);

        // Execute adhoc tasks.
        $sink = $this->redirectMessages();
        $this->executeAdhocTasks();
        $sink->clear();
        $sink->close();

        // Check Cohort1 roles changed according to the new cohort context.
        $assignableroles = get_assignable_roles($catcontext, ROLENAME_BOTH, false);

        // First validation: Roles were assigned in the cohort_role table.
        $this->assertEquals(array_keys($assignableroles), array_keys(totara_get_cohort_roles($this->cohort1->id)));

        // Second validation: Roles were assigned in the role_assignments table.
        $countmembers = count(totara_get_members_cohort($this->cohort1->id));
        foreach ($assignableroles as $key => $value) {
            $this->assertEquals($countmembers, $DB->count_records('role_assignments',
                [
                    'itemid'    => $this->cohort1->id,
                    'contextid' => $catcontext->id,
                    'roleid'    => $key
                ]
            ));
        }

        // Check Cohort 2 is intact.
        $assignablerolessys = get_assignable_roles($syscontext, ROLENAME_BOTH, false);
        $this->assertEquals(array_keys($assignablerolessys), array_keys(totara_get_cohort_roles($this->cohort2->id)));
        $countmembers = count(totara_get_members_cohort($this->cohort2->id));
        foreach ($assignablerolessys as $key => $value) {
            $this->assertEquals($countmembers, $DB->count_records('role_assignments',
                [
                    'itemid'    => $this->cohort2->id,
                    'contextid' => $syscontext->id,
                    'roleid'    => $key
                ]
            ));
        }
    }

    /**
     * Test updating the cohort by changing its context.
     *
     * Change one of the cohort's context (cohort1) to be in category 1 so the task get triggered and the roles
     * get reassigned as appropriated
     *
     * @return void
     */
    public function test_update_cohort_without_context_change(): void {
        global $DB;
        $syscontext = context_system::instance();

        // Check role assignments before modifying cohort.
        $this->assertNotEmpty($DB->count_records('role_assignments',
            [
                'itemid'    => $this->cohort1->id,
                'contextid' => $syscontext->id
            ]
        ), 'Not expected!. Empty role_assignments for cohort');

        // Update cohort1 context.
        $this->cohort1->name = 'Modified cohort 1';
        cohort_update_cohort($this->cohort1);

        // Execute adhoc tasks.
        $sink = $this->redirectMessages();
        $this->executeAdhocTasks();
        $sink->clear();
        $sink->close();

        // Check Cohort1 roles did not change.
        $assignableroles = get_assignable_roles($syscontext, ROLENAME_BOTH, false);
        $this->assertEquals(array_keys($assignableroles), array_keys(totara_get_cohort_roles($this->cohort1->id)));
        $countmembers = count(totara_get_members_cohort($this->cohort1->id));
        foreach ($assignableroles as $key => $value) {
            $this->assertEquals($countmembers, $DB->count_records('role_assignments',
                [
                    'itemid'    => $this->cohort1->id,
                    'contextid' => $syscontext->id,
                    'roleid'    => $key
                ]
            ));
        }

        // Check Cohort 2 is intact.
        $this->assertEquals(array_keys($assignableroles), array_keys(totara_get_cohort_roles($this->cohort2->id)));
        $countmembers = count(totara_get_members_cohort($this->cohort2->id));
        foreach ($assignableroles as $key => $value) {
            $this->assertEquals($countmembers, $DB->count_records('role_assignments',
                [
                    'itemid'    => $this->cohort2->id,
                    'contextid' => $syscontext->id,
                    'roleid'    => $key
                ]
            ));
        }
    }

    /**
     * Test updating the current assigned roles and then the cohort's context.
     *
     * Change one of the cohort's context (cohort1) to be in category 1 so the task get triggered and the roles
     * get reassigned as appropriated BUT first update the cohort roles to have only course creator role so we are sure
     * it is assigned only the ones that are currently assigned.
     *
     * @return void
     */
    public function test_update_cohort_when_context_change_and_only_one_assigned_role(): void {
        global $DB;
        $syscontext = context_system::instance();
        $catcontext = context_coursecat::instance($this->category1->id);
        $assignable_roles_sys = get_assignable_roles($syscontext, ROLENAME_BOTH, false);

        // Change cohort role assignments to have only "Course creator" role.
        // We know that role is set in sys and category context by default.
        $course_creator = array_search('Course Creator', $assignable_roles_sys);
        $roles[$course_creator] = $syscontext->id;
        $this->assertTrue(totara_cohort_process_assigned_roles($this->cohort1->id, $roles));

        // Update cohort1 context.
        $this->cohort1->contextid = $catcontext->id;
        cohort_update_cohort($this->cohort1);

        // Execute adhoc tasks.
        $sink = $this->redirectMessages();
        $this->executeAdhocTasks();
        $sink->clear();
        $sink->close();

        // Unset Course creator role from the category assignable roles.
        $assignable_roles = get_assignable_roles($catcontext, ROLENAME_BOTH, false);

        // Check Cohort1 roles changed according to the new cohort context.
        // First validation: Roles were assigned in the cohort_role table.
        $this->assertEquals(array_keys($roles), array_keys(totara_get_cohort_roles($this->cohort1->id)));

        // Second validation: Roles were assigned in the role_assignments table.
        $countmembers = count(totara_get_members_cohort($this->cohort1->id));
        foreach ($roles as $key => $value) {
            $this->assertEquals($countmembers, $DB->count_records('role_assignments',
                [
                    'itemid'    => $this->cohort1->id,
                    'contextid' => $catcontext->id,
                    'roleid'    => $key
                ]
            ));
        }

        // Third validation: Roles not selected in the cohort are not assigned.
        foreach ($assignable_roles as $key => $value) {
            if (!isset($roles[$key])) {
                $this->assertEquals(0, $DB->count_records('role_assignments',
                    [
                        'itemid'    => $this->cohort1->id,
                        'contextid' => $catcontext->id,
                        'roleid'    => $key
                    ]
                ));
            }
        }

        // Check Cohort 2 is intact.
        $this->assertEquals(array_keys($assignable_roles_sys), array_keys(totara_get_cohort_roles($this->cohort2->id)));
        $countmembers = count(totara_get_members_cohort($this->cohort2->id));
        foreach ($assignable_roles_sys as $key => $value) {
            $this->assertEquals($countmembers, $DB->count_records('role_assignments',
                [
                    'itemid'    => $this->cohort2->id,
                    'contextid' => $syscontext->id,
                    'roleid'    => $key
                ]
            ));
        }
    }
}