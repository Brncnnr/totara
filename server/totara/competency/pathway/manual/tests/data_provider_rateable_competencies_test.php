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
use pathway_manual\data_providers\user_rateable_competencies;
use pathway_manual\models\rateable_competency;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use totara_competency\expand_task;
use totara_competency\models\assignment;
use totara_competency\user_groups;
use totara_hierarchy\entity\competency;

require_once(__DIR__ . '/pathway_manual_base_testcase.php');

/**
 * @group totara_competency
 */
class pathway_manual_data_provider_user_rateable_competencies_testcase extends pathway_manual_base_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->setUser($this->user1->id);
    }

    /**
     * Test that only competencies that the user has an active assignment for are returned.
     */
    public function test_filter_by_user_id() {
        $this->generator->create_manual($this->competency1, [self_role::class]);
        $this->generator->create_manual($this->competency2, [self_role::class]);

        $this->assertEmpty(user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->get_competencies());

        $assignment1 = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();

        $this->assert_has_competencies(
            user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->get_competencies(),
            [$this->competency1]
        );

        $assignment2 = $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency2->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();

        $this->assert_has_competencies(
            user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->get_competencies(),
            [$this->competency1, $this->competency2]
        );

        // Make sure specifying the filter multiple times doesn't break anything.
        $this->assertCount(2, user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['user_id' => $this->user2->id])
            ->add_filters(['user_id' => $this->user1->id])
            ->get_competencies()
        );

        assignment::load_by_id($assignment1->id)->archive();
        assignment::load_by_id($assignment2->id)->archive();

        // No active assignments left, just archived ones.
        $this->assertEmpty(user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->get_competencies());

        // Only returns competencies assigned to this user, not other users.
        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user2->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();

        $this->assertEmpty(user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->get_competencies());
    }

    /**
     * Test that the returned competencies can be filtered by what role(s) can rate them.
     */
    public function test_filter_by_role() {
        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency2->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();

        $this->assertEmpty(user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['roles' => [self_role::get_name(), manager::get_name(), appraiser::get_name()]])
            ->get_competencies()
        );

        // Check that competencies with a single pathway and role are filtered properly.
        $this->generator->create_manual($this->competency1, [self_role::class]);
        $this->generator->create_manual($this->competency2, [manager::class]);
        $this->assert_has_competencies(user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['roles' => [self_role::get_name()]])
            ->get_competencies(),
            [$this->competency1]
        );
        $this->assert_has_competencies(user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['roles' => [manager::get_name()]])
            ->get_competencies(),
            [$this->competency2]
        );

        // Check that competencies with multiple pathways but with single roles are filtered properly.
        $this->generator->create_manual($this->competency1, [appraiser::class]);
        $this->generator->create_manual($this->competency2, [appraiser::class]);
        $this->assert_has_competencies(user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['roles' => [appraiser::get_name()]])
            ->get_competencies(),
            [$this->competency1, $this->competency2]
        );

        // Check that competencies with multiple pathways with multiple roles are filtered properly.
        $this->generator->create_manual($this->competency1, [self_role::class, appraiser::class]);
        $this->generator->create_manual($this->competency2, [manager::class, appraiser::class]);
        $this->assert_has_competencies(user_rateable_competencies::for_user_and_role($this->user1, self_role::class)
            ->add_filters(['roles' => [self_role::get_name(), appraiser::get_name()]])
            ->get_competencies(),
            [$this->competency1, $this->competency2]
        );
    }

    /**
     * Test that count() accurately (you guessed it!) counts the competencies.
     */
    public function test_count() {
        $this->assertEquals(0, user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->count());

        $this->generator->create_manual($this->competency1, [self_role::class]);
        $this->assertEquals(0, user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->count());

        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency1->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();

        $this->assertEquals(1, user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->count());

        $this->generator->assignment_generator()->create_assignment([
            'user_group_type' => user_groups::USER,
            'user_group_id' => $this->user1->id,
            'competency_id' => $this->competency2->id,
        ]);
        (new expand_task(builder::get_db()))->expand_all();

        $this->assertEquals(1, user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->count());
        $this->generator->create_manual($this->competency2, [self_role::class]);

        $this->assertEquals(2, user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->count());
    }

    /**
     * Test that when get() is called, the competencies are grouped into frameworks.
     */
    public function test_get_framework_groups() {
        $fw1 = $this->generator->create_framework($this->scale1, null, null, ['sortorder' => 2000]);
        $fw2 = $this->generator->create_framework($this->scale2, null, null, ['sortorder' => 1000]);
        $competencies = [
            $this->generator->create_competency('1', $fw1),
            $this->generator->create_competency('2', $fw1),
            $this->generator->create_competency('3', $fw2),
            $this->generator->create_competency('4', $fw2),
        ];

        foreach ($competencies as $competency) {
            $this->generator->create_manual($competency, [self_role::class]);
            $this->generator->assignment_generator()->create_assignment([
                'user_group_type' => user_groups::USER,
                'user_group_id' => $this->user1->id,
                'competency_id' => $competency->id,
            ]);
        }
        (new expand_task(builder::get_db()))->expand_all();
        $this->assert_has_competencies(
            user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->get_competencies(),
            $competencies
        );

        $result = user_rateable_competencies::for_user_and_role($this->user1, self_role::class)->get();

        $this->assertEquals($this->user1, $result->get_user_for());

        $this->assertCount(2, $result->get_framework_groups());

        // First framework created but should be second in list because of it's sort order.
        $framework_group1 = $result->get_framework_groups()[1];
        $this->assertEquals($this->scale1->values, $framework_group1->get_values());
        $this->assertEquals($fw1->id, $framework_group1->get_framework()->id);
        $this->assert_has_competencies($framework_group1->get_competencies(), [$competencies[0], $competencies[1]]);

        // Second framework created but should be first in list because of it's sort order.
        $framework_group2 = $result->get_framework_groups()[0];
        $this->assertEquals($this->scale2->values, $framework_group2->get_values());
        $this->assertEquals($fw2->id, $framework_group2->get_framework()->id);
        $this->assert_has_competencies($framework_group2->get_competencies(), [$competencies[2], $competencies[3]]);
    }

    public function get_by_role_data_provider() {
        $self = self_role::get_name();
        $man = manager::get_name();
        $app = appraiser::get_name();
        return [
            [
                [self_role::class], // pathway roles competency1
                [manager::class], // pathway roles competency2
                [$self], // role filters
                [$self => [1], $man => [], $app => []],
            ],
            [
                [self_role::class, manager::class, appraiser::class],
                [self_role::class, manager::class],
                [$self, $man, $app],
                [$self => [1, 2], $man => [1, 2], $app => [1]],
            ],
            [
                [],
                [],
                [$self, $man, $app],
                [$self => [], $app => [], $man => []],
            ],
        ];
    }

    /**
     * Make sure the provider has the same competency data as expected.
     *
     * @param rateable_competency[]|competency[] $actual_competencies
     * @param competency[] $expected_competencies
     */
    private function assert_has_competencies(array $actual_competencies, array $expected_competencies) {
        $this->assertCount(count($expected_competencies), $actual_competencies);

        for ($i = 0; $i < count($expected_competencies); $i++) {
            $expected_data = $expected_competencies[$i]->to_array();
            $actual_entity = $actual_competencies[$i] instanceof competency
                ? $actual_competencies[$i]
                : $actual_competencies[$i]->get_entity();
            $actual_data = $actual_entity->to_array();

            foreach ($expected_data as $attribute => $value) {
                $this->assertEquals($value, $actual_data[$attribute]);
            }
        }
    }

}
