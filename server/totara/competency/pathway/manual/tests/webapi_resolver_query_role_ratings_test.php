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
 * @subpackage test
 */

use core\orm\query\builder;
use core\webapi\execution_context;
use pathway_manual\models\role_rating;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use pathway_manual\webapi\resolver\query\role_ratings;
use pathway_manual\webapi\resolver\type\role;
use pathway_manual\webapi\resolver\type\role_rating as role_rating_type;
use totara_competency\expand_task;
use totara_competency\models\assignment;

require_once(__DIR__ . '/pathway_manual_base_testcase.php');

/**
 * Tests the query to fetch all roles and their latest ratings for a given user and competency
 *
 * @group totara_competency
 */
class pathway_manual_webapi_resolver_query_role_ratings_testcase extends pathway_manual_base_testcase {

    private $scalevalue1;

    private $scalevalue2;

    private $user1_assignment;

    protected function setUp(): void {
        global $DB;
        parent::setUp();

        $scale = $this->competency1->scale;
        $values = $scale->sorted_values_high_to_low;
        $this->scalevalue1 = $values->first();
        $values->next();
        $this->scalevalue2 = $values->current();

        $this->generator->create_manual($this->competency1);

        $this->user1_assignment = $this->generator->assignment_generator()->create_user_assignment($this->competency1->id, $this->user1->id);
        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        $role = builder::table('role')->where('shortname', 'user')->value('id');
        $this->setUser($this->user2->id);
        assign_capability('totara/competency:view_other_profile', CAP_ALLOW, $role, context_user::instance($this->user1->id));
        $this->setUser($this->user1->id);
        assign_capability('totara/competency:view_own_profile', CAP_ALLOW, $role, context_user::instance($this->user1->id));

        $this->setAdminUser();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->scalevalue1 = null;
        $this->scalevalue2 = null;
        $this->user1_assignment = null;
    }

    /**
     * @return role_rating[]
     */
    private function resolve(): array {
        return role_ratings::resolve(
            ['user_id' => $this->user1->id, 'assignment_id' => $this->user1_assignment->id],
            execution_context::create('dev', null)
        );
    }

    /**
     * Make sure the user has the right permissions to view their ratings
     */
    public function test_self_capability() {
        $role = builder::table('role')->where('shortname', 'user')->value('id');

        $this->setUser($this->user1->id);
        $this->assertNotNull($this->resolve());

        unassign_capability('totara/competency:view_own_profile', $role);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('View own competency profile');

        $this->resolve();
    }

    /**
     * Make sure the query cannot be accessed by another user who isn't the users manager
     */
    public function test_manager_capability() {
        $role = builder::table('role')->where('shortname', 'user')->value('id');

        $this->setUser($this->user2->id);
        $this->assertNotNull($this->resolve());

        unassign_capability('totara/competency:view_other_profile', $role);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('View profile of other users');

        $this->resolve();
    }

    /**
     * Make sure that the latest rating is always returned
     */
    public function test_latest_rating_is_returned() {
        $this->generator->create_manual_rating(
            $this->competency1,
            $this->user1->id,
            $this->user1->id,
            self_role::class,
            $this->scalevalue1->id,
            'Rating One'
        );

        $this->setUser($this->user1->id);

        /** @var \pathway_manual\entity\rating $rating */
        $rating = $this->resolve()[0]->get_latest_rating();
        $this->assertEquals('Rating One', $rating->comment);
        $this->assertEquals($this->scalevalue1->id, $rating->scale_value_id);

        $this->waitForSecond();
        $this->waitForSecond();

        $this->generator->create_manual_rating(
            $this->competency1,
            $this->user1->id,
            $this->user1->id,
            self_role::class,
            $this->scalevalue2->id,
            'Rating Two'
        );

        $rating = $this->resolve()[0]->get_latest_rating();
        $this->assertEquals('Rating Two', $rating->comment);
        $this->assertEquals($this->scalevalue2->id, $rating->scale_value_id);
    }

    /**
     * Make sure we can get the ratings even if the assignment isn't active
     */
    public function test_load_from_inactive_assignment() {
        global $DB;

        $this->assertCount(3, $this->resolve()); // load it normally

        $assignment = assignment::load_by_id($this->user1_assignment->id);
        $assignment->archive();
        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        $this->assertCount(3, $this->resolve()); // load after archiving
    }

    /**
     * Make sure we can resolve the role from the type.
     */
    public function test_get_role() {
        $role_rating = $this->resolve()[0];

        /** @var self_role $role */
        $role = role_rating_type::resolve('role', $role_rating, [], execution_context::create('dev', null));

        $expected_return_values = [
            'name' => self_role::get_name(),
            'display_name' => self_role::get_display_name(),
            'display_order' => self_role::get_display_order(),
            'has_role' => false,
        ];

        foreach ($expected_return_values as $field => $expected_value) {
            $this->assertEquals($expected_value, role::resolve($field, $role, [], execution_context::create('dev', null)));
        }
    }

    public function test_role_display_name() {
        $role_ratings = $this->resolve();
        $self_rating = $role_ratings[0];
        $manager_rating = $role_ratings[1];

        // When we are looking at ratings for our own competency, then the role name should be "Your rating" (if self)
        $this->setUser($this->user1->id);
        $role_display_name = role_rating_type::resolve(
            'role_display_name', $self_rating, [], execution_context::create('dev', null)
        );
        $this->assertEquals('Your rating', $role_display_name);

        // When we are looking at ratings for someone else's competency, then the role name should be their full name (if self)
        $this->setAdminUser();
        $role_display_name = role_rating_type::resolve(
            'role_display_name', $self_rating, [], execution_context::create('dev', null)
        );
        $this->assertEquals($this->user1->fullname, $role_display_name);

        // When we are looking at ratings for someone else's competency, then we should see the name of the role if it isn't self
        $role_display_name = role_rating_type::resolve(
            'role_display_name', $manager_rating, [], execution_context::create('dev', null)
        );
        $this->assertEquals(manager::get_display_name(), $role_display_name);
    }

}
