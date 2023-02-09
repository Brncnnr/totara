<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 * @category test
 */


use totara_competency\admin_setting_continuous_tracking;
use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_competency\user_groups;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_user_group_deleted_testcase extends advanced_testcase {

    public function test_it_deletes_user_assignments_tracking_enabled() {
        [
            'comps' => $comps,
            'users' => $users,
            'assignments' => $assignments,
        ] = $this->prepare_user_assignments();

        $task = new expand_task($this->db());
        $task->expand_single($assignments[0]->id);
        $task->expand_single($assignments[1]->id);


        $this->assertCount(2, $comps);
        $this->assertCount(2, $users);
        $this->assertCount(2, $assignments);

        set_config('continuous_tracking', admin_setting_continuous_tracking::ENABLED, 'totara_competency');

        // Removing a user
        delete_user($users[0]);

        $this->assertEmpty($this->db()->get_record('totara_competency_assignments', [
            'id' => $assignments[0]->id,
        ]));

        $this->assertEquals(1, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[1]->id
        ]));

        $this->assertEmpty($this->db()->get_record('totara_competency_assignments', [
            'user_group_id' => $users[0]->id,
            'competency_id' => $comps[0]->id,
        ]));
    }

    public function test_it_deletes_user_assignments_tracking_disabled() {
        [
            'comps' => $comps,
            'users' => $users,
            'assignments' => $assignments,
        ] = $this->prepare_user_assignments();

        $task = new expand_task($this->db());
        $task->expand_single($assignments[0]->id);
        $task->expand_single($assignments[1]->id);


        $this->assertCount(2, $comps);
        $this->assertCount(2, $users);
        $this->assertCount(2, $assignments);

        set_config('continuous_tracking', admin_setting_continuous_tracking::DISABLED, 'totara_competency');

        // Removing a user
        delete_user($users[0]);

        $this->assertEmpty($this->db()->get_record('totara_competency_assignments', [
            'id' => $assignments[0]->id,
        ]));

        $this->assertEquals(1, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[1]->id
        ]));

        $this->assertEmpty($this->db()->get_record('totara_competency_assignments', [
            'user_group_id' => $users[0]->id,
            'competency_id' => $comps[0]->id,
        ]));
    }

    public function test_it_deletes_cohort_assignments_tracking_enabled() {
        [
            'comps' => $comps,
            'users' => $users,
            'cohorts' => $cohorts,
            'assignments' => $assignments,
        ] = $this->prepare_cohort_assignments();

        $task = new expand_task($this->db());
        $task->expand_single($assignments[0]->id);
        $task->expand_single($assignments[1]->id);


        $this->assertCount(2, $comps);
        $this->assertCount(2, $users);
        $this->assertCount(2, $cohorts);
        $this->assertCount(2, $assignments);

        set_config('continuous_tracking', admin_setting_continuous_tracking::ENABLED, 'totara_competency');

        // Removing a user
        cohort_delete_cohort($cohorts[0]);

        $this->assertEquals(2, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[0]->id
        ]));

        $this->assertEquals(1, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[1]->id
        ]));

        $this->assertIsObject($this->db()->get_record('totara_competency_assignments', [
            'user_group_id' => $users[0]->id,
            'user_group_type' => user_groups::USER,
            'competency_id' => $comps[0]->id,
            'type' => assignment::TYPE_SYSTEM,
        ]));
    }

    public function test_it_deletes_position_assignments_tracking_disabled() {
        [
            'comps' => $comps,
            'users' => $users,
            'positions' => $positions,
            'assignments' => $assignments,
        ] = $this->prepare_position_assignments();

        $task = new expand_task($this->db());
        $task->expand_single($assignments[0]->id);
        $task->expand_single($assignments[1]->id);


        $this->assertCount(2, $comps);
        $this->assertCount(2, $users);
        $this->assertCount(2, $positions);
        $this->assertCount(2, $assignments);


        set_config('continuous_tracking', admin_setting_continuous_tracking::DISABLED, 'totara_competency');

        // Removing a position
        $hierarchy = new \position();

        $hierarchy->delete_hierarchy_item($positions[0]->id);

        $this->assertEquals(2, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[0]->id
        ]));

        $this->assertEquals(1, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[1]->id
        ]));

        $this->assertEmpty($this->db()->get_record('totara_competency_assignments', [
            'user_group_id' => $users[0]->id,
            'competency_id' => $comps[0]->id,
        ]));
    }

    public function test_it_deletes_position_assignments_tracking_enabled() {
        [
            'comps' => $comps,
            'users' => $users,
            'positions' => $positions,
            'assignments' => $assignments,
        ] = $this->prepare_position_assignments();

        $task = new expand_task($this->db());
        $task->expand_single($assignments[0]->id);
        $task->expand_single($assignments[1]->id);


        $this->assertCount(2, $comps);
        $this->assertCount(2, $users);
        $this->assertCount(2, $positions);
        $this->assertCount(2, $assignments);


        set_config('continuous_tracking', admin_setting_continuous_tracking::ENABLED, 'totara_competency');

        // Removing a position
        $hierarchy = new \position();

        $hierarchy->delete_hierarchy_item($positions[0]->id);

        $this->assertEquals(2, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[0]->id
        ]));

        $this->assertEquals(1, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[1]->id
        ]));

        $this->assertIsObject($this->db()->get_record('totara_competency_assignments', [
            'user_group_id' => $users[0]->id,
            'user_group_type' => user_groups::USER,
            'competency_id' => $comps[0]->id,
            'type' => assignment::TYPE_SYSTEM,
        ]));
    }

    public function test_it_deletes_organisation_assignments_tracking_enabled() {
        [
            'comps' => $comps,
            'users' => $users,
            'organisations' => $organisations,
            'assignments' => $assignments,
        ] = $this->prepare_organisation_assignments();

        $task = new expand_task($this->db());
        $task->expand_single($assignments[0]->id);
        $task->expand_single($assignments[1]->id);

        $this->assertCount(2, $comps);
        $this->assertCount(2, $users);
        $this->assertCount(2, $organisations);
        $this->assertCount(2, $assignments);

        set_config('continuous_tracking', admin_setting_continuous_tracking::ENABLED, 'totara_competency');

        // Removing a position
        $hierarchy = new \organisation();

        $hierarchy->delete_hierarchy_item($organisations[0]->id);

        $this->assertEquals(2, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[0]->id
        ]));

        $this->assertEquals(1, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[1]->id
        ]));

        $this->assertIsObject($this->db()->get_record('totara_competency_assignments', [
            'user_group_id' => $users[0]->id,
            'user_group_type' => user_groups::USER,
            'competency_id' => $comps[0]->id,
            'type' => assignment::TYPE_SYSTEM,
        ]));
    }

    public function test_it_deletes_organisation_assignments_tracking_disabled() {
        [
            'comps' => $comps,
            'users' => $users,
            'organisations' => $organisations,
            'assignments' => $assignments,
        ] = $this->prepare_organisation_assignments();

        $task = new expand_task($this->db());
        $task->expand_single($assignments[0]->id);
        $task->expand_single($assignments[1]->id);

        $this->assertCount(2, $comps);
        $this->assertCount(2, $users);
        $this->assertCount(2, $organisations);
        $this->assertCount(2, $assignments);

        set_config('continuous_tracking', admin_setting_continuous_tracking::DISABLED, 'totara_competency');

        // Removing a position
        $hierarchy = new \organisation();

        $hierarchy->delete_hierarchy_item($organisations[0]->id);

        $this->assertEquals(2, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[0]->id
        ]));

        $this->assertEquals(1, $this->db()->get_field('totara_competency_assignments', 'status', [
            'id' => $assignments[1]->id
        ]));

        $this->assertEmpty($this->db()->get_record('totara_competency_assignments', [
            'user_group_id' => $users[0]->id,
            'competency_id' => $comps[0]->id,
        ]));
    }

    public function prepare_cohort_assignments() {
        ['comps' => $comps, 'users' => $users] = $this->prepare_users_and_competencies();

        // We need to create a user group
        $cohorts = [
            $this->generator()->assignment_generator()->create_cohort_and_add_members($users[0]),
            $this->generator()->assignment_generator()->create_cohort_and_add_members($users[1]),
        ];

        // We need to create an assignment
        $assignments = [
            $this->generator()->assignment_generator()->create_cohort_assignment($comps[0]->id, $cohorts[0]->id, ['status' => 1]),
            $this->generator()->assignment_generator()->create_cohort_assignment($comps[0]->id, $cohorts[1]->id, ['status' => 1]),
        ];

        return [
            'comps' => $comps,
            'users' => $users,
            'cohorts' => $cohorts,
            'assignments' => $assignments,
        ];
    }

    public function prepare_position_assignments() {
        ['comps' => $comps, 'users' => $users] = $this->prepare_users_and_competencies();

        // We need to create a user group
        $positions = [
            $this->generator()->assignment_generator()->create_position_and_add_members($users[0]),
            $this->generator()->assignment_generator()->create_position_and_add_members($users[1]),
        ];

        // We need to create an assignment
        $assignments = [
            $this->generator()->assignment_generator()->create_position_assignment($comps[0]->id, $positions[0]->id, ['status' => 1]),
            $this->generator()->assignment_generator()->create_position_assignment($comps[0]->id, $positions[1]->id, ['status' => 1]),
        ];

        return [
            'comps' => $comps,
            'users' => $users,
            'positions' => $positions,
            'assignments' => $assignments,
        ];
    }

    public function prepare_organisation_assignments() {
        ['comps' => $comps, 'users' => $users] = $this->prepare_users_and_competencies();

        // We need to create a user group
        $organisations = [
            $this->generator()->assignment_generator()->create_organisation_and_add_members($users[0]),
            $this->generator()->assignment_generator()->create_organisation_and_add_members($users[1]),
        ];

        // We need to create an assignment
        $assignments = [
            $this->generator()->assignment_generator()->create_organisation_assignment($comps[0]->id, $organisations[0]->id, ['status' => 1]),
            $this->generator()->assignment_generator()->create_organisation_assignment($comps[0]->id, $organisations[1]->id, ['status' => 1]),
        ];

        return [
            'comps' => $comps,
            'users' => $users,
            'organisations' => $organisations,
            'assignments' => $assignments,
        ];
    }

    public function prepare_user_assignments() {
        ['comps' => $comps, 'users' => $users] = $this->prepare_users_and_competencies();

        // We need to create an assignment
        $assignments = [
            $this->generator()->assignment_generator()->create_user_assignment($comps[0]->id, $users[0]->id, ['status' => 1]),
            $this->generator()->assignment_generator()->create_user_assignment($comps[0]->id, $users[1]->id, ['status' => 1]),
        ];

        return [
            'comps' => $comps,
            'users' => $users,
            'assignments' => $assignments,
        ];
    }

    protected function prepare_users_and_competencies() {
        // We need to create a competency.
        $comps = $this->generate_competencies();
        // We need to create a user
        $users = [
            $this->generator()->assignment_generator()->create_user(),
            $this->generator()->assignment_generator()->create_user(),
        ];

        return [
            'comps' => $comps,
            'users' => $users,
        ];
    }

    /**
     * Create a few competencies with knows names to test search
     */
    protected function generate_competencies() {
        $comps = [];

        $fw = $this->generator()->hierarchy_generator()->create_comp_frame([]);
        $type = $this->generator()->hierarchy_generator()->create_comp_type(['idnumber' => 'type1']);

        $comps[] = $comp_one = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'acc',
            'fullname' => 'Accounting',
            'description' => 'Counting profits',
            'idnumber' => 'accc',
            'typeid' => $type,
        ]);

        $comps[] = $comp_two = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
            'typeid' => $type,
            'parentid' => $comp_one->id,
        ]);

        return $comps;
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_competency');
    }

    /**
     * Database reference
     *
     * @return \moodle_database
     */
    protected function db() {
        return $GLOBALS['DB'];
    }
}