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

use core\orm\query\builder;
use core\webapi\execution_context;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\webapi\resolver\query\rateable_users;
use pathway_manual\webapi\resolver\type\rateable_user;
use totara_competency\expand_task;
use totara_competency\user_groups;
use totara_job\job_assignment;

require_once(__DIR__ . '/pathway_manual_base_testcase.php');

/**
 * @group totara_competency
 */
class pathway_manual_webapi_resolver_query_rateable_users_testcase extends pathway_manual_base_testcase {

    /**
     * @var stdClass
     */
    private $manager_user;

    /**
     * @var stdClass
     */
    private $appraiser_user;

    /**
     * Assign user to competency.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->manager_user = $this->getDataGenerator()->create_user();
        $this->appraiser_user = $this->getDataGenerator()->create_user();

        // The order that users are returned in is in order of their name, so set it here to be consistent.
        $this->user1->set_attribute('firstname', 'A')->save();
        $this->user2->set_attribute('firstname', 'B')->save();

        $this->generator->create_manual($this->competency1, [manager::class]);
        $this->generator->create_manual($this->competency1, [appraiser::class]);

        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user2->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->manager_user = null;
        $this->appraiser_user = null;
    }

    /**
     * @return execution_context
     */
    private function execution_context(): execution_context {
        return execution_context::create('dev', null);
    }

    private function create_manager_job_assignments() {
        $manager_ja = job_assignment::create_default($this->manager_user->id);
        job_assignment::create(['userid' => $this->user1->id, 'managerjaid' => $manager_ja->id, 'idnumber' => 1]);
        job_assignment::create(['userid' => $this->user2->id, 'managerjaid' => $manager_ja->id, 'idnumber' => 2]);
    }

    /**
     * Create appraiser job assignments
     *
     * @return array
     * @throws coding_exception
     */
    private function create_appraiser_job_assignments() {
        $appraiser_ja1 = job_assignment::create_default($this->user1->id, ['appraiserid' => $this->appraiser_user->id]);
        $appraiser_ja2 = job_assignment::create_default($this->user2->id, ['appraiserid' => $this->appraiser_user->id]);

        return [$appraiser_ja1, $appraiser_ja2];
    }

    /**
     * Make sure correct capabilities are enforced when querying for another user as a manager.
     */
    public function test_capability_manager() {
        $this->setUser($this->manager_user);

        $this->create_manager_job_assignments();
        $users_can_view = rateable_users::resolve(['role' => manager::class], $this->execution_context());

        // We are allowed to rate 2 users, so they should be returned.
        $this->assertCount(2, $users_can_view);

        $role = builder::table('role')->where('shortname', 'staffmanager')->one()->id;
        unassign_capability('totara/competency:rate_other_competencies', $role);

        $no_users = rateable_users::resolve(['role' => manager::class], $this->execution_context());

        // We no longer have the capability to rate the users, so they shouldn't show up.
        $this->assertEmpty($no_users);
    }

    public function test_appraiser_can_resolve() {
        $this->setUser($this->appraiser_user);

        [$appraiser_ja1, $appraiser_ja2] = $this->create_appraiser_job_assignments();
        $users_can_view = rateable_users::resolve(['role' => appraiser::class], $this->execution_context());

        $this->assertCount(2, $users_can_view);

        job_assignment::delete($appraiser_ja1);
        job_assignment::delete($appraiser_ja2);

        $no_users = rateable_users::resolve(['role' => appraiser::class], $this->execution_context());

        // We no longer have the capability to rate the users, so they shouldn't show up.
        $this->assertEmpty($no_users);
    }

    /**
     * Sanity check to make sure the count can be resolved.
     */
    public function test_resolve_competency_count_field() {
        $this->setUser($this->manager_user->id);

        $this->create_manager_job_assignments();
        $result = rateable_users::resolve(['role' => manager::class], $this->execution_context());

        $this->assertEquals(1, rateable_user::resolve('competency_count', $result[0], [], $this->execution_context()));
    }

    /**
     * Sanity check to make sure the user can be resolved.
     */
    public function test_resolve_user_field() {
        $this->setUser($this->manager_user->id);

        $this->create_manager_job_assignments();
        $result = rateable_users::resolve(['role' => manager::class], $this->execution_context());

        $this->assertEquals(
            $this->user1->id,
            rateable_user::resolve('user', $result[0], [], $this->execution_context())->id
        );
    }

    /**
     * Sanity check to make sure the latest rating field can be resolved.
     */
    public function test_latest_rating_field() {
        $this->setUser($this->manager_user->id);

        $this->create_manager_job_assignments();
        $result = rateable_users::resolve(['role' => manager::class], $this->execution_context());

        $rating = $this->generator->create_manual_rating($this->competency1, $this->user1, $this->manager_user, manager::class);

        $this->assertEquals(
            $rating->id,
            rateable_user::resolve('latest_rating', $result[0], [], $this->execution_context())->id
        );
    }

}
