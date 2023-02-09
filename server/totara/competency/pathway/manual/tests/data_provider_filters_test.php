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
use pathway_manual\data_providers\user_rateable_competencies;
use pathway_manual\models\rateable_competency;
use pathway_manual\models\roles\self_role;
use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_competency\models\assignment_reason;
use totara_competency\user_groups;
use totara_hierarchy\entity\competency_type;
use totara_job\job_assignment;

require_once(__DIR__ . '/pathway_manual_base_testcase.php');

/**
 * @group totara_competency
 */
class pathway_manual_data_provider_filters_testcase extends pathway_manual_base_testcase {

    /**
     * Make sure that if there are multiple reasons that a user is assigned to the competency they are listed in alphabetical order.
     */
    public function test_get_assignment_reason_filter_options() {
        [$user_assignments, $positions, $organisations] = $this->create_assignment_data();

        foreach ([$this->user1, $this->user2] as $user) {
            $this->setUser($user->id);

            $assignments = $user_assignments[$user->id];

            /** @var assignment_reason[] $assignment_reasons */
            $assignment_reasons = $this->get_filter_options($user)['assignment_reason'];

            $this->assertCount(3, $assignment_reasons);

            $this->assertEquals('Admin User (Admin)', $assignment_reasons[0]->reason);
            $this->assert_has_assignments([$assignments[4]], $assignment_reasons[0]->assignments);

            $this->assertStringContainsString($organisations[$user->id]->fullname, $assignment_reasons[1]->reason);
            $this->assert_has_assignments([$assignments[2], $assignments[3]], $assignment_reasons[1]->assignments);

            $this->assertStringContainsString($positions[$user->id]->fullname, $assignment_reasons[2]->reason);
            $this->assert_has_assignments([$assignments[0], $assignments[1]], $assignment_reasons[2]->assignments);
        }
    }

    /**
     * Make sure that filtering by assignment reason filters applies filters based upon multiple assignment IDs.
     */
    public function test_filter_by_assignment_reason() {
        [$user_assignments] = $this->create_assignment_data();

        foreach ([$this->user1, $this->user2] as $user) {
            $this->setUser($user->id);

            foreach ($user_assignments[$user->id] as $assignment) {
                $competencies = user_rateable_competencies::for_user_and_role($user, self_role::class)
                    ->add_filters(['assignment_reason' => [$assignment->id]])
                    ->get_competencies();

                $this->assertEquals($assignment->competency_id, reset($competencies)->get_entity()->id);
            }
        }
    }

    /**
     * Make sure that if there are multiple types among the rateable competencies, we get a list of them in alphabetical order.
     */
    public function test_get_competency_type_filter_options() {
        $this->create_basic_data();

        $this->setUser($this->user1->id);

        $this->assertEmpty($this->get_filter_options($this->user1)['competency_type']);

        $type_b = $this->generator->create_type(['fullname' => 'Type B']);
        $this->competency2->typeid = $type_b->id;
        $this->competency2->save();

        $type_a = $this->generator->create_type(['fullname' => 'Type A']);
        $this->competency1->typeid = $type_a->id;
        $this->competency1->save();

        /** @var competency_type[] $competency_types */
        $competency_types = $this->get_filter_options($this->user1)['competency_type'];

        // The types must be returned in alphabetical order.
        $this->assertCount(2, $competency_types);
        $this->assertEquals($type_a->id, $competency_types[0]->id);
        $this->assertEquals($type_b->id, $competency_types[1]->id);
    }

    /**
     * Make sure that filtering by competency type filters by competency (you guessed it!) type.
     */
    public function test_filter_by_competency_type() {
        $this->create_basic_data();

        $type_a = $this->generator->create_type(['fullname' => 'Type A']);
        $this->competency1->typeid = $type_a->id;
        $this->competency1->save();

        $type_b = $this->generator->create_type(['fullname' => 'Type B']);
        $this->competency2->typeid = $type_b->id;
        $this->competency2->save();

        $this->setUser($this->user1->id);

        $type_a_competencies = user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['competency_type' => $type_a->id])
            ->get_competencies();

        $this->assertCount(1, $type_a_competencies);
        $this->assertEquals($this->competency1->id, $type_a_competencies[0]->get_entity()->id);

        $type_b_competencies = user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['competency_type' => $type_b->id])
            ->get_competencies();

        $this->assertCount(1, $type_b_competencies);
        $this->assertEquals($this->competency2->id, $type_b_competencies[0]->get_entity()->id);
    }

    /**
     * Make sure that if there are competencies that the current user has both rated and hasn't rated, then the filter is enabled.
     */
    public function test_get_rating_history_filter_options() {
        $this->create_basic_data();

        $this->setUser($this->user1->id);

        // Both competencies have no ratings, so there would only be a single filter option, so we have it disabled - i.e. false
        $this->assertFalse($this->get_filter_options($this->user1)['rating_history']);

        $this->generator->create_manual_rating(
            $this->competency1, $this->user1, $this->user1, self_role::class, $this->scale1->values->first()
        );

        // One competency has a rating, but the other doesn't, so the filter should be enabled.
        $this->assertTrue($this->get_filter_options($this->user1)['rating_history']);

        $this->generator->create_manual_rating(
            $this->competency2, $this->user1, $this->user1, self_role::class, $this->scale1->values->first()
        );

        // Both competencies have a rating, and there are no competencies without a rating, so disable the filter again.
        $this->assertFalse($this->get_filter_options($this->user1)['rating_history']);
    }

    /**
     * Make sure that the rating history filter option filters by whether a competency has/hasn't been rated by the current user.
     */
    public function test_filter_by_rating_history() {
        $this->create_basic_data();

        $this->setUser($this->user1->id);

        // Both competencies don't have ratings, so filtering by no ratings will return both of them
        /** @var rateable_competency[] $competencies_without_ratings */
        $competencies_without_ratings = user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['rating_history' => false])
            ->get_competencies();

        $this->assertCount(2, $competencies_without_ratings);
        $this->assertEquals($this->competency1->id, $competencies_without_ratings[0]->get_entity()->id);
        $this->assertEquals($this->competency2->id, $competencies_without_ratings[1]->get_entity()->id);

        // And filtering by competencies with ratings will return nothing.
        $this->assertEmpty(user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['rating_history' => true])
            ->get_competencies()
        );

        $this->generator->create_manual_rating(
            $this->competency1, $this->user1, $this->user1, self_role::class, $this->scale1->values->first()
        );

        // One competency has a rating, the other one doesn't.
        /** @var rateable_competency[] $competencies_with_ratings */
        $competencies_with_ratings = user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['rating_history' => true])
            ->get_competencies();
        $this->assertCount(1, $competencies_with_ratings);
        $this->assertEquals($this->competency1->id, $competencies_with_ratings[0]->get_entity()->id);

        $competencies_without_ratings = user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['rating_history' => false])
            ->get_competencies();
        $this->assertCount(1, $competencies_without_ratings);
        $this->assertEquals($this->competency2->id, $competencies_without_ratings[0]->get_entity()->id);

        $this->generator->create_manual_rating(
            $this->competency2, $this->user1, $this->user1, self_role::class, $this->scale1->values->first()
        );

        // Both competencies have ratings now.
        $competencies_with_ratings = user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['rating_history' => true])
            ->get_competencies();

        $this->assertCount(2, $competencies_with_ratings);
        $this->assertEquals($this->competency1->id, $competencies_with_ratings[0]->get_entity()->id);
        $this->assertEquals($this->competency2->id, $competencies_with_ratings[1]->get_entity()->id);

        $this->assertEmpty(user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['rating_history' => false])
            ->get_competencies()
        );
    }

    /**
     * Create self manual pathways and assign them to a user for 2 competencies.
     */
    private function create_basic_data() {
        $this->generator->create_manual($this->competency1);
        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);

        $this->generator->create_manual($this->competency2);
        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency2->id,
        ]);

        (new expand_task(builder::get_db()))->expand_all();
    }

    /**
     * Create a bunch of assignments for testing the assignment reason filter.
     *
     * @return array
     */
    private function create_assignment_data() {
        $assignment_generator = $this->generator->assignment_generator();

        $competencies = [];
        for ($i = 0; $i < 4; $i++) {
            $competencies[] = $comp = $this->generator->create_competency();
            $this->generator->create_manual($comp, [self_role::class]);
        }

        $positions = [];
        $positions[$this->user1->id] = $assignment_generator->create_position();
        $positions[$this->user2->id] = $assignment_generator->create_position();

        $user_assignments = [];

        $user_assignments[$this->user1->id][] = $assignment_generator->create_assignment([
            'user_group_type' => user_groups::POSITION,
            'user_group_id' => $positions[$this->user1->id]->id,
            'competency_id' => $competencies[0]->id,
        ]);
        $user_assignments[$this->user1->id][] = $assignment_generator->create_assignment([
            'user_group_type' => user_groups::POSITION,
            'user_group_id' => $positions[$this->user1->id]->id,
            'competency_id' => $competencies[1]->id,
        ]);
        $user_assignments[$this->user2->id][] = $assignment_generator->create_assignment([
            'user_group_type' => user_groups::POSITION,
            'user_group_id' => $positions[$this->user2->id]->id,
            'competency_id' => $competencies[2]->id,
        ]);
        $user_assignments[$this->user2->id][] = $assignment_generator->create_assignment([
            'user_group_type' => user_groups::POSITION,
            'user_group_id' => $positions[$this->user2->id]->id,
            'competency_id' => $competencies[3]->id,
        ]);

        $organisations = [];
        $organisations[$this->user1->id] = $assignment_generator->create_organisation();
        $organisations[$this->user2->id] = $assignment_generator->create_organisation();

        $user_assignments[$this->user1->id][] = $assignment_generator->create_assignment([
            'user_group_type' => user_groups::ORGANISATION,
            'user_group_id' => $organisations[$this->user1->id]->id,
            'competency_id' => $competencies[0]->id,
        ]);
        $user_assignments[$this->user1->id][] = $assignment_generator->create_assignment([
            'user_group_type' => user_groups::ORGANISATION,
            'user_group_id' => $organisations[$this->user1->id]->id,
            'competency_id' => $competencies[1]->id,
        ]);
        $user_assignments[$this->user2->id][] = $assignment_generator->create_assignment([
            'user_group_type' => user_groups::ORGANISATION,
            'user_group_id' => $organisations[$this->user2->id]->id,
            'competency_id' => $competencies[2]->id,
        ]);
        $user_assignments[$this->user2->id][] = $assignment_generator->create_assignment([
            'user_group_type' => user_groups::ORGANISATION,
            'user_group_id' => $organisations[$this->user2->id]->id,
            'competency_id' => $competencies[3]->id,
        ]);

        job_assignment::create([
            'userid' => $this->user1->id,
            'positionid' => $positions[$this->user1->id]->id,
            'organisationid' => $organisations[$this->user1->id]->id,
            'idnumber' => 'ja1',
        ]);
        job_assignment::create([
            'userid' => $this->user2->id,
            'positionid' => $positions[$this->user2->id]->id,
            'organisationid' => $organisations[$this->user2->id]->id,
            'idnumber' => 'ja1',
        ]);

        // Create some direct assignments
        $user_assignments[$this->user1->id][] = $assignment_generator->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        $this->generator->create_manual($this->competency1);
        $user_assignments[$this->user2->id][] = $assignment_generator->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user2->id,
            'competency_id' => $this->competency2->id,
        ]);
        $this->generator->create_manual($this->competency2);

        (new expand_task(builder::get_db()))->expand_all();

        return [$user_assignments, $positions, $organisations];
    }

    /**
     * Make sure the two specified arrays contain the same assignments in the expected order.
     *
     * @param assignment[] $expected
     * @param assignment[] $actual
     */
    private function assert_has_assignments(array $expected, array $actual) {
        for ($i = 0; $i < count($expected); $i++) {
            $this->assertEquals($expected[$i]->id, $actual[$i]->id);
        }
    }

    /**
     * Get the filter options.
     *
     * @param user $user
     * @return array
     */
    private function get_filter_options(user $user): array {
        return user_rateable_competencies::for_user_and_role($user, self_role::class)
            ->get_with_filter_options()
            ->get_filter_options();
    }

}
