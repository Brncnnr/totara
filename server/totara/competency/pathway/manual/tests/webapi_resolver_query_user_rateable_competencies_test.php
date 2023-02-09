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
use pathway_manual\models\framework_group;
use pathway_manual\models\rateable_competency;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use pathway_manual\webapi\resolver\query\user_rateable_competencies;
use pathway_manual\webapi\resolver\type\rateable_competency as rateable_competency_type;
use pathway_manual\webapi\resolver\type\user_competencies as user_competencies_type;
use totara_competency\expand_task;
use totara_competency\user_groups;
use totara_job\job_assignment;

require_once(__DIR__ . '/pathway_manual_base_testcase.php');

/**
 * @group totara_competency
 */
class pathway_manual_webapi_resolver_query_user_rateable_competencies_testcase extends pathway_manual_base_testcase {

    /**
     * Assign user to competency.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->generator->create_manual($this->competency1, [self_role::class]);

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

    /**
     * @return execution_context
     */
    private function execution_context(): execution_context {
        return execution_context::create('dev', null);
    }

    /**
     * Make sure correct capabilities are enforced when querying for themselves.
     */
    public function test_capability_self() {
        $this->setUser($this->user1->id);
        user_rateable_competencies::resolve(
            ['user_id' => $this->user1->id, 'role' => self_role::class],
            $this->execution_context()
        );

        $role = builder::table('role')->where('shortname', 'user')->one()->id;
        unassign_capability('totara/competency:rate_own_competencies', $role);

        $this->expectException(required_capability_exception::class);
        user_rateable_competencies::resolve(
            ['user_id' => $this->user1->id, 'role' => self_role::class],
            $this->execution_context()
        );
    }

    /**
     * Make sure correct capabilities are enforced when querying for another user as a manager.
     */
    public function test_capability_manager() {
        $this->generator->create_manual($this->competency1, [manager::class]);

        $manager_ja = job_assignment::create_default($this->user2->id);
        job_assignment::create(['userid' => $this->user1->id, 'managerjaid' => $manager_ja->id, 'idnumber' => 1]);

        $this->setUser($this->user2->id);

        user_rateable_competencies::resolve(
            ['user_id' => $this->user1->id, 'role' => manager::class],
            $this->execution_context()
        );

        $manager_role = builder::table('role')->where('shortname', 'staffmanager')->one()->id;
        $self_role = builder::table('role')->where('shortname', 'user')->one()->id;
        unassign_capability('totara/competency:rate_other_competencies', $manager_role);
        unassign_capability('totara/competency:rate_own_competencies', $self_role);

        $this->expectException(required_capability_exception::class);
        user_rateable_competencies::resolve(
            ['user_id' => $this->user1->id, 'role' => self_role::class],
            $this->execution_context()
        );
    }

    public function test_appraiser_can_resolve() {
        $this->generator->create_manual($this->competency1, [appraiser::class]);

        $appraiser_ja = job_assignment::create_default($this->user1->id, ['appraiserid' => $this->user2->id]);
        $this->setUser($this->user2->id);

        user_rateable_competencies::resolve(
            ['user_id' => $this->user1->id, 'role' => appraiser::class],
            $this->execution_context()
        );

        job_assignment::delete($appraiser_ja);

        $this->expectException(moodle_exception::class);
        user_rateable_competencies::resolve(
            ['user_id' => $this->user1->id, 'role' => appraiser::class],
            $this->execution_context()
        );
    }

    /**
     * Sanity check to make sure the count can be resolved.
     */
    public function test_resolve_count_field() {
        $this->setUser($this->user1->id);

        $query = user_rateable_competencies::resolve(
            ['user_id' => $this->user1->id, 'role' => self_role::class],
            $this->execution_context()
        );
        $this->assertEquals(1, user_competencies_type::resolve('count', $query, [], $this->execution_context()));
    }

    /**
     * Sanity check to make sure the user can be resolved.
     */
    public function test_resolve_user_field() {
        $this->setUser($this->user1->id);

        $query = user_rateable_competencies::resolve(
            ['user_id' => $this->user1->id, 'role' => self_role::class],
            $this->execution_context()
        );
        $this->assertEquals($this->user1->id, user_competencies_type::resolve('user', $query, [], $this->execution_context())->id);
    }

    /**
     * Sanity check to make sure the scale group and its children can be resolved.
     */
    public function test_resolve_scale_field() {
        $this->setUser($this->user1->id);

        $query = user_rateable_competencies::resolve(
            ['user_id' => $this->user1->id, 'role' => self_role::class],
            $this->execution_context()
        );

        $expected_rateable_competencies = [new rateable_competency($this->competency1, $this->user1)];
        $expected_framework_group = framework_group::build_from_competencies($expected_rateable_competencies)[0];

        /** @var framework_group[] $returned_framework_groups */
        $returned_framework_groups = user_competencies_type::resolve('framework_groups', $query, [], $this->execution_context());
        $this->assertCount(1, $returned_framework_groups);
        $returned_framework_group = $returned_framework_groups[0];

        $this->assertCount(1, $returned_framework_group->get_competencies());
        $this->assertEquals(
            $expected_rateable_competencies[0]->get_entity()->id,
            $returned_framework_group->get_competencies()[0]->get_entity()->id
        );

        $this->assertEquals($expected_framework_group->values, $returned_framework_group->values);

        /** @var rateable_competency[] $returned_competencies */
        $returned_competencies = $returned_framework_group->competencies;
        $this->assertCount(1, $returned_competencies);
        $returned_competency = $returned_competencies[0];

        rateable_competency_type::resolve('competency', $returned_competency, [], $this->execution_context());
        rateable_competency_type::resolve('latest_rating', $returned_competency, [], $this->execution_context());
    }

    /**
     * Check last_rating field is resolved according to role.
     */
    public function test_resolve_last_rating_field() {
        $this->setUser($this->user1->id);

        $managerja = job_assignment::create_default($this->user2->id);
        job_assignment::create_default($this->user1->id, ['managerjaid' => $managerja->id]);

        $rateable_competency_role_self = new rateable_competency($this->competency1, $this->user1, new self_role());
        $rateable_competency_role_manager = new rateable_competency($this->competency1, $this->user1, new manager());

        // No rating exists.
        $last_rating = rateable_competency_type::resolve(
            'latest_rating',
            $rateable_competency_role_self,
            [],
            $this->execution_context()
        );
        $this->assertNull($last_rating);

        // Rating for self.
        $rating_self = $this->generator->create_manual_rating(
            $this->competency1,
            $this->user1,
            $this->user1,
            self_role::class
        );
        // Rating made by manager.
        $rating_manager = $this->generator->create_manual_rating(
            $this->competency1,
            $this->user1,
            $this->user2,
            manager::class
        );

        $last_rating = rateable_competency_type::resolve(
            'latest_rating',
            $rateable_competency_role_self,
            [],
            $this->execution_context()
        );
        $this->assertEquals($rating_self, $last_rating);

        $last_rating = rateable_competency_type::resolve(
            'latest_rating',
            $rateable_competency_role_manager,
            [],
            $this->execution_context()
        );
        $this->assertEquals($rating_manager, $last_rating);
    }

    /**
     * Make sure that specifying filters changes the query result.
     */
    public function test_resolve_with_filters() {
        $this->generator->create_manual($this->competency1);
        $assignment_1 = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);

        $this->generator->create_manual($this->competency2);
        $assignment_2 = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency2->id,
        ]);

        (new expand_task(builder::get_db()))->expand_all();

        $this->setUser($this->user1->id);

        // Make sure only the first competency assigned is returned, and no filter options are fetched.
        $assignment_1_query = user_rateable_competencies::resolve([
            'user_id' => $this->user1->id,
            'role' => self_role::class,
            'filters' => [
                'assignment_reason' => [$assignment_1->id],
            ]
        ], $this->execution_context());
        $this->assertEquals(1, $assignment_1_query->get_count());
        $this->assertEquals(
            $this->competency1->id,
            $assignment_1_query->get_framework_groups()[0]->get_competencies()[0]->get_entity()->id
        );
        $this->assertNull($assignment_1_query->get_filter_options());

        // When no filters are specified, then filter options should also be fetched.
        // There shouldn't be anything returned though because there is only one assignment reason to filter by.
        $query_without_filters = user_rateable_competencies::resolve([
            'user_id' => $this->user1->id,
            'role' => self_role::class,
            'filters' => null,
        ], $this->execution_context());
        $this->assertNull($query_without_filters->get_filter_options()['assignment_reason']);
        $this->assertEmpty(user_competencies_type::resolve(
            'filters',
            $query_without_filters,
            [],
            $this->execution_context()
        )['assignment_reason']);

        $org = $this->generator->assignment_generator()->create_organisation_and_add_members($this->user1->id);
        $assignment_3 = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::ORGANISATION,
            'user_group_id' => $org->id,
            'competency_id' => $this->competency2->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();

        // There are multiple assignment reasons to filter by now.
        $query_without_filters = user_rateable_competencies::resolve([
            'user_id' => $this->user1->id,
            'role' => self_role::class,
            'filters' => null,
        ], $this->execution_context());
        $this->assertNotNull($query_without_filters->get_filter_options()['assignment_reason']);
        $this->assertNotEmpty(user_competencies_type::resolve(
            'filters',
            $query_without_filters,
            [],
            $this->execution_context()
        )['assignment_reason']);
    }

}
