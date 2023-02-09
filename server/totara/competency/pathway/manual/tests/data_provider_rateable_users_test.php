<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

use core\entity\user;
use core\orm\query\builder;
use pathway_manual\data_providers\rateable_users;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use totara_competency\expand_task;
use totara_competency\models\assignment;
use totara_competency\user_groups;
use totara_job\job_assignment;
use totara_hierarchy\entity\competency;

require_once(__DIR__ . '/pathway_manual_base_testcase.php');

/**
 * @group totara_competency
 */
class pathway_manual_data_provider_rateable_users_testcase extends pathway_manual_base_testcase {

    /**
     * @var \core\testing\generator
     */
    private $user_generator;

    protected function tearDown(): void {
        parent::tearDown();
        $this->user_generator = null;
    }

    public function test_get_single_rateable_user_with_single_competency() {
        $this->setUser($this->user2->id);

        $this->assertEmpty(rateable_users::for_role(manager::class)->get());

        // User 2 must be the manager of User 1 in order to see them.
        $manager_ja = job_assignment::create_default($this->user2->id);
        job_assignment::create(['userid' => $this->user1->id, 'managerjaid' => $manager_ja->id, 'idnumber' => 1]);

        // There must be a manual pathway for the competency, and it must be active.
        $this->generator->create_manual($this->competency1, [manager::class]);

        // The user must be assigned to the competency, and the assignment must be active.
        $assignment = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_single($assignment->id);

        $rateable_users = rateable_users::for_role(manager::class)->get();

        $this->assertCount(1, $rateable_users);

        $rateable_user = $rateable_users[0];
        $this->assertEquals(1, $rateable_user->get_competency_count());

        $this->assertInstanceOf(user::class, $rateable_user->get_user());
        $this->assertEquals($this->user1->id, $rateable_user->get_user()->id);

        // There isn't a rating yet
        $this->assertNull($rateable_user->get_latest_rating());

        $expected_latest_rating = $this->generator->create_manual_rating(
            $this->competency1, $this->user1, $this->user2, manager::class
        );

        $this->assertEquals($expected_latest_rating->id, $rateable_user->get_latest_rating()->id);
    }

    /**
     * This test can be useful for seeing how performance scales across different users, roles, assignments, pathways etc.
     */
    public function test_get_multiple_rateable_users() {
        // Increase this number to see how the query scales when there are many users in the system, but don't commit a big number
        $test_user_count = 3;

        $this->user_generator = $this->getDataGenerator();

        $this->generator->create_manual($this->competency1, [manager::class]);
        $this->create_rateable_users($this->user1, $this->competency1, $test_user_count);

        $this->generator->create_manual($this->competency2, [manager::class]);
        $this->create_rateable_users($this->user2, $this->competency2, $test_user_count);

        (new expand_task(builder::get_db()))->expand_all();

        $this->setUser($this->user1->id);
        $user1_rateable_users = rateable_users::for_role(manager::class)->get();

        $this->setUser($this->user2->id);
        $user2_rateable_users = rateable_users::for_role(manager::class)->get();

        $this->assertCount($test_user_count, $user1_rateable_users);
        $this->assertCount($test_user_count, $user2_rateable_users);
    }

    public function test_get_active_manual_pathways_only() {
        $manager_ja = job_assignment::create_default($this->user1->id);
        job_assignment::create([
            'userid' => $this->user2->id,
            'managerjaid' => $manager_ja->id,
            'idnumber' => '1',
        ]);

        $assignment = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user2->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_single($assignment->id);

        $pathway = $this->generator->create_manual($this->competency1, [manager::class]);

        $this->setUser($this->user1->id);

        $this->assertCount(1, rateable_users::for_role(manager::class)->get());

        $pathway->delete();

        $this->assertCount(0, rateable_users::for_role(manager::class)->get());

        $this->generator->create_learning_plan_pathway($this->competency1);

        $this->assertCount(0, rateable_users::for_role(manager::class)->get());

        $this->generator->create_manual($this->competency1, [manager::class]);

        $this->assertCount(1, rateable_users::for_role(manager::class)->get());
    }

    public function test_get_active_assignments_only() {
        $manager_ja = job_assignment::create_default($this->user1->id);
        job_assignment::create([
            'userid' => $this->user2->id,
            'managerjaid' => $manager_ja->id,
            'idnumber' => '1',
        ]);
        $this->generator->create_manual($this->competency1, [manager::class]);
        $this->setUser($this->user1->id);

        // Not assigned to the competency
        $this->assertCount(0, rateable_users::for_role(manager::class)->get());

        $assignment = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user2->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_single($assignment->id);

        // Now assigned to the competency
        $this->assertCount(1, rateable_users::for_role(manager::class)->get());

        $assignment = assignment::load_by_id($assignment->id);
        $assignment->archive();

        // We don't want to see archived assignments
        $this->assertCount(0, rateable_users::for_role(manager::class)->get());
    }

    public function test_rateable_competencies_count() {
        $manager_ja = job_assignment::create_default($this->user1->id);
        job_assignment::create([
            'userid' => $this->user2->id,
            'managerjaid' => $manager_ja->id,
            'idnumber' => '1',
        ]);
        $this->setUser($this->user1->id);

        // There are no pathways and no competencies assigned
        $this->assertCount(0, rateable_users::for_role(manager::class)->get());

        $this->generator->create_learning_plan_pathway($this->competency1);

        // There is a pathway now but it is for learning plans, not for manual ratings
        $this->assertCount(0, rateable_users::for_role(manager::class)->get());

        $this->generator->create_manual($this->competency1, [appraiser::class]);

        // There is a manual pathway now but it is for an appraiser, not manager
        $this->assertCount(0, rateable_users::for_role(manager::class)->get());

        $this->generator->create_manual($this->competency1, [manager::class]);

        // There is the correct pathway now, but it still isn't assigned yet
        $this->assertCount(0, rateable_users::for_role(manager::class)->get());

        $assignment = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user2->id,
            'competency_id' => $this->competency2->id,
        ]);
        (new expand_task(builder::get_db()))->expand_single($assignment->id);

        // The assignment is for a different competency
        $this->assertCount(0, rateable_users::for_role(manager::class)->get());

        $assignment = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user2->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_single($assignment->id);

        // There is one competency assigned with a manual pathway
        $rateable_users = rateable_users::for_role(manager::class)->get();
        $this->assertCount(1, $rateable_users);
        $this->assertEquals(1, $rateable_users[0]->get_competency_count());

        // Create an irrelevant pathway for the second competency
        $this->generator->create_manual($this->competency2, [appraiser::class]);

        $rateable_users = rateable_users::for_role(manager::class)->get();
        $this->assertCount(1, $rateable_users);
        $this->assertEquals(1, $rateable_users[0]->get_competency_count());

        // Create a relevant pathway for the second competency (this means the second competency should now be included)
        $this->generator->create_manual($this->competency2, [manager::class]);

        $rateable_users = rateable_users::for_role(manager::class)->get();
        $this->assertCount(1, $rateable_users);
        $this->assertEquals(2, $rateable_users[0]->get_competency_count());
    }

    public function test_filter_and_order_by_users_full_name() {
        $this->user_generator = $this->getDataGenerator();
        $this->generator->create_manual($this->competency1, [manager::class]);
        $users = $this->create_rateable_users($this->user1, $this->competency1);
        (new expand_task(builder::get_db()))->expand_all();

        $user1 = $users[0]
            ->set_attribute('firstname', 'John')
            ->set_attribute('lastname', 'Smith')
            ->save();
        $user2 = $users[1]
            ->set_attribute('firstname', 'Jane')
            ->set_attribute('lastname', 'Smith')
            ->save();

        $this->setUser($this->user1->id);

        $this->assertEmpty(rateable_users::for_role(manager::class)
            ->add_filters(['user_full_name' => 'Blah Blah Blah'])
            ->get()
        );

        $jane_results = rateable_users::for_role(manager::class)
            ->add_filters(['user_full_name' => 'Jane'])
            ->get();
        $this->assertCount(1, $jane_results);
        $this->assertEquals($user2->id, $jane_results[0]->get_user()->id);

        // Results are ordered alphabetically by full name
        $both_results = rateable_users::for_role(manager::class)
            ->add_filters(['user_full_name' => 'Smith'])
            ->get();
        $this->assertCount(2, $both_results);
        $this->assertEquals($user2->id, $both_results[0]->get_user()->id);
        $this->assertEquals($user1->id, $both_results[1]->get_user()->id);
    }

    /**
     * Create users with the same manager and assigned to the same competency.
     *
     * @param user $manager
     * @param competency $competency
     * @param int $user_count Custom number of users to create. Defaults to 2.
     * @return user[]
     */
    private function create_rateable_users(user $manager, competency $competency, int $user_count = 2): array {
        $manager_ja = job_assignment::create_default($manager->id);

        $users = [];
        for ($i = 0; $i < $user_count; $i++) {
            $user = $this->user_generator->create_user();

            job_assignment::create([
                'userid' => $user->id,
                'managerjaid' => $manager_ja->id,
                'idnumber' => $manager->id . '_' . $user->id,
            ]);

            $assignment = $this->generator->assignment_generator()->create_assignment([
                'user_group_type' => user_groups::USER,
                'user_group_id' => $user->id,
                'competency_id' => $competency->id,
            ]);

            $users[] = new user($user);
        }

        return $users;
    }

}
