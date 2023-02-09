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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

defined('MOODLE_INTERNAL') || die();

use core\entity\user;
use core\orm\collection;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approval_level;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\override_assignments_test_setup;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\assignment\assignment_approval_level
 */
class mod_approval_assignment_approval_level_testcase extends testcase {

    use override_assignments_test_setup;

    /**
     * Test that get_approvers returns only the approvers explicitly defined for the assignments
     * and does not return any inherited approvers.
     *
     * The inheritance structure:
     * override 0: sub a, approvers at level 1, inherits level 2 from default
     * override 1: sub a prog a, approvers at level 1, inherits level 2 from default
     * override 2: sub a prog b, approvers at level 2, inherits level 1 from override 0
     * override 3: sub b, no approver overrides, inherits both levels from default
     * override 4: audience, approvers at level 1, inherits at level 2 from default
     *
     * @covers ::get_approvers
     */
    public function test_get_approvers(): void {
        $data = $this->create_workflow_with_complex_override_assignments();
        /** @var workflow $workflow */
        $workflow = $data['test_workflow']['workflow'];
        $approval_level_1 = $data['test_workflow']['level1'];
        $approval_level_2 = $data['test_workflow']['level2'];

        $scenarios = [
            [
                $workflow->get_default_assignment(), $approval_level_1, [
                    $data['test_workflow']['approver_default_l1']->id,
                ]
            ],
            [
                $workflow->get_default_assignment(), $approval_level_2, [
                    $data['test_workflow']['approver_default_l2']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][0], $approval_level_1, [
                    $data['test_workflow']['approver_a0_l1']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][0], $approval_level_2, [
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][1], $approval_level_1, [
                    $data['test_workflow']['approver_a1_l1']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][1], $approval_level_2, [
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][2], $approval_level_1, [
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][2], $approval_level_2, [
                    $data['test_workflow']['approver_a2_l2_1']->id,
                    $data['test_workflow']['approver_a2_l2_2']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][3], $approval_level_1, [
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][3], $approval_level_2, [
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][4], $approval_level_1, [
                    $data['test_workflow']['approver_a4_l1']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][4], $approval_level_2, [
                ]
            ],
        ];

        foreach ($scenarios as $scenario) {
            [$assignment, $approval_level, $expected_approver_ids] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $approval_level
            );
            $approvers = $assignment_approval_level->get_approvers();
            self::assertCount(count($expected_approver_ids), $approvers);
            foreach ($approvers as $approver) {
                self::assertContains($approver->id, $expected_approver_ids);
            }
        }
    }

    public function test_approvers_cache(): void {
        list($workflow, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        $assignment = assignment::load_by_entity($override_assignments[0]);
        $level2 = $stages[1]->approval_levels->last();

        // Check that there is only 1 approver.
        $assignment_approval_level = new assignment_approval_level($assignment, $level2);
        $approvers = $assignment_approval_level->get_approvers();
        $this->assertCount(1, $approvers);
        $assignment_approval_level->set_approvers_cache($approvers);

        // Create a new approver
        $new_approver = assignment_approver::create(
            $assignment,
            $level2,
            \mod_approval\model\assignment\approver_type\user::get_code(),
            $this->getDataGenerator()->create_user()->id
        );
        $new_approver->activate();

        // If cache worked, there is still only one approver that this aal knows about.
        $approvers = $assignment_approval_level->get_approvers();
        $this->assertCount(1, $approvers);

        // Create a new assignment_approval_level instance with no cache.
        $assignment_approval_level = new assignment_approval_level($assignment, $level2);
        $approvers = $assignment_approval_level->get_approvers();
        $this->assertCount(2, $approvers);
    }

    /**
     * Test that get_approvers_with_inheritance returns approvers either explicitly defined for the assignments
     * or inherited from an ancestor assignment.
     *
     * The inheritance structure:
     * override 0: sub a, approvers at level 1, inherits level 2 from default
     * override 1: sub a prog a, approvers at level 1, inherits level 2 from default
     * override 2: sub a prog b, approvers at level 2, inherits level 1 from override 0
     * override 3: sub b, no approver overrides, inherits both levels from default
     * override 4: audience, approvers at level 1, inherits at level 2 from default
     * override 5: control sub a prog a, no approver overrides, inherits both levels from default
     */
    public function test_get_approvers_with_inheritance(): void {
        $data = $this->create_workflow_with_complex_override_assignments();

        /** @var workflow $workflow */
        $workflow = $data['test_workflow']['workflow'];
        $approval_level_1 = $data['test_workflow']['level1'];
        $approval_level_2 = $data['test_workflow']['level2'];

        $scenarios = [
            [
                $workflow->get_default_assignment(), $approval_level_1, [
                    $data['test_workflow']['approver_default_l1']->id,
                ]
            ],
            [
                $workflow->get_default_assignment(), $approval_level_2, [
                    $data['test_workflow']['approver_default_l2']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][0], $approval_level_1, [
                    $data['test_workflow']['approver_a0_l1']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][0], $approval_level_2, [
                    $data['test_workflow']['approver_default_l2']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][1], $approval_level_1, [
                    $data['test_workflow']['approver_a1_l1']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][1], $approval_level_2, [
                    $data['test_workflow']['approver_default_l2']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][2], $approval_level_1, [
                    $data['test_workflow']['approver_a0_l1']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][2], $approval_level_2, [
                    $data['test_workflow']['approver_a2_l2_1']->id,
                    $data['test_workflow']['approver_a2_l2_2']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][3], $approval_level_1, [
                    $data['test_workflow']['approver_default_l1']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][3], $approval_level_2, [
                    $data['test_workflow']['approver_default_l2']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][4], $approval_level_1, [
                    $data['test_workflow']['approver_a4_l1']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][4], $approval_level_2, [
                    $data['test_workflow']['approver_default_l2']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][5], $approval_level_1, [
                    $data['test_workflow']['approver_default_l1']->id,
                ]
            ],
            [
                $data['test_workflow']['override_assignments'][5], $approval_level_2, [
                    $data['test_workflow']['approver_default_l2']->id,
                ]
            ],
        ];

        foreach ($scenarios as $scenario) {
            [$assignment, $approval_level, $expected_approver_ids] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $approval_level
            );
            $approvers = $assignment_approval_level->get_approvers_with_inheritance();
            self::assertCount(
                count($expected_approver_ids),
                $approvers,
                'Incorrect approvers count for assignment ' . $assignment->name . ' and level ' . $approval_level->name
            );
            foreach ($approvers as $approver) {
                // Scenarios are defined using ancestor_ids where inheritance is indicated.
                self::assertTrue(
                    in_array(($approver->ancestor_id ?? $approver->id), $expected_approver_ids),
                    'Approver ' . $approver->id . "({$approver->ancestor_id})" .' appears to not be assigned to assignment ' . $assignment->id . ' and level ' . $approval_level->id
                );
            }
        }
    }

    /**
     * Test that get_inherited_from_assignment_approval_level returns the correct ancestor assignment
     * only when required.
     *
     * The inheritance structure:
     * override 0: sub a, approvers at level 1, inherits level 2 from default
     * override 1: sub a prog a, approvers at level 1, inherits level 2 from default
     * override 2: sub a prog b, inherits level 1 from override 0, approvers at level 2
     * override 3: sub b, no approver overrides, inherits both levels from default
     * override 4: audience, approvers at level 1, inherits at level 2 from default
     */
    public function test_get_inherited_from_assignment_approval_level(): void {
        $data = $this->create_workflow_with_complex_override_assignments();

        /** @var workflow $workflow */
        $workflow = $data['test_workflow']['workflow'];
        $approval_level_1 = $data['test_workflow']['level1'];
        $approval_level_2 = $data['test_workflow']['level2'];
        $default_assignment = $workflow->get_default_assignment();

        $scenarios = [
            [$default_assignment, $approval_level_1, null],
            [$default_assignment, $approval_level_2, null],
            [$data['test_workflow']['override_assignments'][0], $approval_level_1, null],
            [$data['test_workflow']['override_assignments'][0], $approval_level_2, $default_assignment],
            [$data['test_workflow']['override_assignments'][1], $approval_level_1, null],
            [$data['test_workflow']['override_assignments'][1], $approval_level_2, $default_assignment],
            [$data['test_workflow']['override_assignments'][2], $approval_level_1, $data['test_workflow']['override_assignments'][0]],
            [$data['test_workflow']['override_assignments'][2], $approval_level_2, null],
            [$data['test_workflow']['override_assignments'][3], $approval_level_1, $default_assignment],
            [$data['test_workflow']['override_assignments'][3], $approval_level_2, $default_assignment],
            [$data['test_workflow']['override_assignments'][4], $approval_level_1, null],
            [$data['test_workflow']['override_assignments'][4], $approval_level_2, $default_assignment],
        ];

        foreach ($scenarios as $scenario) {
            [$assignment, $approval_level, $expected_assignment] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $approval_level
            );
            $result_assignment_approval_level = $assignment_approval_level->get_inherited_from_assignment_approval_level();
            if (is_null($expected_assignment)) {
                self::assertNull($result_assignment_approval_level);
            } else {
                self::assertEquals($expected_assignment->id, $result_assignment_approval_level->get_assignment()->id);
                self::assertEquals($approval_level->id, $result_assignment_approval_level->get_approval_level()->id);
            }
        }
    }

    /**
     * Test that get_inherited_from_assignment_approval_level returns the correct ancestor assignment
     * at all times when the $ignore_approvers flag is set.
     *
     * The inheritance structure (same as previous test):
     * override 0: sub a, approvers at level 1, inherits level 2 from default (without approvers, both from default)
     * override 1: sub a prog a, approvers at level 1, inherits level 2 from default (without approvers, l1 from sub a, l2 from default)
     * override 2: sub a prog b, inherits level 1 from override 0, approvers at level 2 (without approvers, l1 from sub a, l2 from default)
     * override 3: sub b, no approver overrides, inherits both levels from default
     * override 4: audience, approvers at level 1, inherits at level 2 from default (without approvers, both from default)
     */
    public function test_get_ancestor_assignment_approval_level(): void {
        $data = $this->create_workflow_with_complex_override_assignments();

        /** @var workflow $workflow */
        $workflow = $data['test_workflow']['workflow'];
        $approval_level_1 = $data['test_workflow']['level1'];
        $approval_level_2 = $data['test_workflow']['level2'];
        $default_assignment = $workflow->get_default_assignment();

        $scenarios = [
            [$default_assignment, $approval_level_1, null],
            [$default_assignment, $approval_level_2, null],
            [$data['test_workflow']['override_assignments'][0], $approval_level_1, $default_assignment],
            [$data['test_workflow']['override_assignments'][0], $approval_level_2, $default_assignment],
            [$data['test_workflow']['override_assignments'][1], $approval_level_1, $data['test_workflow']['override_assignments'][0]],
            [$data['test_workflow']['override_assignments'][1], $approval_level_2, $default_assignment],
            [$data['test_workflow']['override_assignments'][2], $approval_level_1, $data['test_workflow']['override_assignments'][0]],
            [$data['test_workflow']['override_assignments'][2], $approval_level_2, $default_assignment],
            [$data['test_workflow']['override_assignments'][3], $approval_level_1, $default_assignment],
            [$data['test_workflow']['override_assignments'][3], $approval_level_2, $default_assignment],
            [$data['test_workflow']['override_assignments'][4], $approval_level_1, $default_assignment],
            [$data['test_workflow']['override_assignments'][4], $approval_level_2, $default_assignment],
        ];

        foreach ($scenarios as $scenario) {
            [$assignment, $approval_level, $expected_assignment] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $approval_level
            );
            $result_assignment_approval_level = $assignment_approval_level->get_ancestor_assignment_approval_level();
            if (is_null($expected_assignment)) {
                self::assertNull($result_assignment_approval_level);
            } else {
                self::assertEquals($expected_assignment->id, $result_assignment_approval_level->get_assignment()->id, "Failed for {$assignment->name} {$approval_level->name}");
                self::assertEquals($approval_level->id, $result_assignment_approval_level->get_approval_level()->id);
            }
        }
    }

    /**
     * Test that get_descendant_levels returns the correct levels for an organisation hierarchy.
     *
     * The inheritance structure:
     * override 0: sub a, approvers at level 1, inherits level 2
     * override 1: sub a prog a, approvers at level 1, inherits level 2
     * override 2: sub a prog b, inherits level 1, approvers at level 2
     * override 3: sub b, no approver overrides, inherits both levels
     *
     * @covers ::get_descendants
     */
    public function test_get_descendant_levels(): void {
        $data = $this->create_workflow_with_complex_override_assignments();

        /** @var workflow $workflow */
        $workflow = $data['test_workflow']['workflow'];
        $approval_level_1 = $data['test_workflow']['level1'];
        $approval_level_2 = $data['test_workflow']['level2'];

        $scenarios = [
            [$workflow->get_default_assignment(), $approval_level_1, [
                $data['test_workflow']['override_assignments'][3]->id,
            ]],
            [$workflow->get_default_assignment(), $approval_level_2, [
                $data['test_workflow']['override_assignments'][0]->id,
                $data['test_workflow']['override_assignments'][1]->id,
                $data['test_workflow']['override_assignments'][3]->id,
            ]],
            [$data['test_workflow']['override_assignments'][0], $approval_level_1, [
                $data['test_workflow']['override_assignments'][2]->id,
            ]],
            [$data['test_workflow']['override_assignments'][0], $approval_level_2, [
                $data['test_workflow']['override_assignments'][1]->id,
            ]],
            [$data['test_workflow']['override_assignments'][1], $approval_level_1, null],
            [$data['test_workflow']['override_assignments'][1], $approval_level_2, null],
            [$data['test_workflow']['override_assignments'][2], $approval_level_1, null],
            [$data['test_workflow']['override_assignments'][2], $approval_level_2, null],
            [$data['test_workflow']['override_assignments'][3], $approval_level_1, null],
            [$data['test_workflow']['override_assignments'][3], $approval_level_2, null],
        ];

        foreach ($scenarios as $scenario) {
            [$assignment, $approval_level, $expected] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $approval_level
            );
            $descendants = $assignment_approval_level->get_descendants();
            if (is_null($expected)) {
                self::assertCount(0, $descendants, "Failed for {$assignment->name}, {$approval_level->name}");
            } else {
                $assignment_ids = [];
                foreach ($descendants as $assignment_approval_level) {
                    $assignment_ids[] = $assignment_approval_level->get_assignment()->id;
                    self::assertEquals($approval_level->id, $assignment_approval_level->get_approval_level()->id);
                }
            }
        }
    }

    /**
     * Test that get_descendant_levels returns the correct levels for a position hierarchy.
     *
     * @covers ::get_descendants
     */
    public function test_get_descendant_levels_position(): void {
        $this->setAdminUser();

        // Generate a workflow with override assignments.
        list($workflow_entity, $test_framework, $assignment_entity) = $this->create_workflow_and_assignment('Testing', false);
        $test_workflow = workflow::load_by_entity($workflow_entity);

        // Replace the framework with positions.
        $test_framework = $this->generate_pos_hierarchy();

        // Change the default assignment.
        $assignment_entity->assignment_type = assignment_type\position::get_code();
        $assignment_entity->assignment_identifier = $test_framework->division->id;
        $assignment_entity->save();
        $test_assignment = assignment::load_by_entity($assignment_entity);

        // Create overrides for all of those positions.
        // Create three override assignments
        $sub_divisions = [
            $test_framework->division->position_a,
            $test_framework->division->position_a->grade_a,
            $test_framework->division->position_a->grade_a->region_a,
            $test_framework->division->position_a->grade_a->region_b,
            $test_framework->division->position_a->grade_b,
            $test_framework->division->position_a->grade_b->region_a,
            $test_framework->division->position_a->grade_b->region_b,
            $test_framework->division->position_b,
            $test_framework->division->position_b->grade_a,
            $test_framework->division->position_b->grade_a->region_a,
            $test_framework->division->position_b->grade_a->region_b,
            $test_framework->division->position_b->grade_b,
            $test_framework->division->position_b->grade_b->region_a,
            $test_framework->division->position_b->grade_b->region_b,
        ];
        $test_overrides = new collection();
        foreach ($sub_divisions as $pos) {
            $assignment_go = new assignment_generator_object($test_workflow->course_id, assignment_type\position::get_code(), $pos->id);
            /** @var \hierarchy_position\entity\position $pos */
            $assignment_go->is_default = false;
            $assignment_go->status = status::ACTIVE;
            $entity = $this->generator()->create_assignment($assignment_go);
            $test_overrides->append(assignment::load_by_entity($entity));
        }

        // Create an approver, and find stage1 and level1
        $test_approver = new user($this->getDataGenerator()->create_user());
        $test_stage1 = $test_workflow->latest_version->stages->first();
        $test_stage2 = $test_workflow->latest_version->get_next_stage($test_stage1->id);
        $test_level1 = $test_stage2->approval_levels->first();

        /**
         * framework->division                                - other1
         * framework->division->position_a                    - approver
         * framework->division->position_a->grade_a           - other2
         * framework->division->position_a->grade_a->region_a - other2 (inherited)
         * framework->division->position_a->grade_a->region_b - approver
         * framework->division->position_a->grade_b           - approver (inherited)
         * framework->division->position_a->grade_b->region_a - approver (inherited)
         * framework->division->position_a->grade_b->region_b - approver (inherited)
         * framework->division->position_b                    - other1 (inherited)
         * framework->division->position_b->grade_a           - approver
         * framework->division->position_b->grade_a->region_a - approver (inherited)
         * framework->division->position_b->grade_a->region_b - other2
         * framework->division->position_b->grade_b           - other1 (inherited)
         * framework->division->position_b->grade_b->region_a - other1 (inherited)
         * framework->division->position_b->grade_b->region_b - other3
         */

        $approver_assignment_levels = [
            $test_framework->division->position_a->id,
            $test_framework->division->position_a->grade_a->region_b->id,
            $test_framework->division->position_b->grade_a->id,
        ];
        foreach ($approver_assignment_levels as $assignment_id) {
            assignment_approver::create(
                $test_overrides->find('assignment_identifier', $assignment_id),
                $test_level1,
                user_approver_type::TYPE_IDENTIFIER,
                $test_approver->id
            );
        }

        $other1 = new user($this->getDataGenerator()->create_user());
        assignment_approver::create(
            $test_assignment,
            $test_level1,
            user_approver_type::TYPE_IDENTIFIER,
            $other1->id
        );

        $other2 = new user($this->getDataGenerator()->create_user());
        $other2_assignment_levels = [
            $test_framework->division->position_a->grade_a->id,
            $test_framework->division->position_b->grade_a->region_b->id,
        ];
        foreach ($other2_assignment_levels as $assignment_id) {
            assignment_approver::create(
                $test_overrides->find('assignment_identifier', $assignment_id),
                $test_level1,
                user_approver_type::TYPE_IDENTIFIER,
                $other2->id
            );
        }

        $other3 = new user($this->getDataGenerator()->create_user());
        $other3_assignment_levels = [
            $test_framework->division->position_b->grade_b->region_b->id,
        ];
        foreach ($other3_assignment_levels as $assignment_id) {
            assignment_approver::create(
                $test_overrides->find('assignment_identifier', $assignment_id),
                $test_level1,
                user_approver_type::TYPE_IDENTIFIER,
                $other3->id
            );
        }

        /**
         * Test get_descendant_levels()
         */
        $scenarios = [
            [$test_assignment, [
                $test_framework->division->position_b->id,
                $test_framework->division->position_b->grade_b->id,
                $test_framework->division->position_b->grade_b->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->id), [
                $test_framework->division->position_a->grade_b->id,
                $test_framework->division->position_a->grade_b->region_a->id,
                $test_framework->division->position_a->grade_b->region_b->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->id), [
                $test_framework->division->position_a->grade_a->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->region_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->id), [
                $test_framework->division->position_a->grade_b->region_a->id,
                $test_framework->division->position_a->grade_b->region_b->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->region_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->id), [
                $test_framework->division->position_b->grade_b->id,
                $test_framework->division->position_b->grade_b->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->id), [
                $test_framework->division->position_b->grade_a->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->region_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->id), [
                $test_framework->division->position_b->grade_b->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->region_b->id), []],
        ];

        foreach ($scenarios as $scenario) {
            [$assignment, $expected] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $test_level1
            );
            $descendants = $assignment_approval_level->get_descendants();
            if (is_null($expected)) {
                self::assertCount(0, $descendants, "Failed for {$assignment->name}");
            } else {
                self::assertCount(count($expected), $descendants, "Failed for {$assignment->name}");
                $assignment_ids = [];
                foreach ($descendants as $assignment_approval_level) {
                    $assignment_ids[] = $assignment_approval_level->get_assignment()->id;
                    self::assertEquals($test_level1->id, $assignment_approval_level->get_approval_level()->id);
                }
            }
        }
    }

    /**
     * @covers ::get_descendants
     */
    public function test_get_descendant_levels_cohort(): void {
        $this->setAdminUser();

        // Generate a workflow with override assignments.
        list($workflow_entity, $test_framework, $assignment_entity) = $this->create_workflow_and_assignment('Testing', false);
        $test_workflow = workflow::load_by_entity($workflow_entity);

        // Replace the framework with some audiences.
        $generator = $this->getDataGenerator();
        $test_framework = new stdClass();
        $test_framework->leaders = $generator->create_cohort(['name' => 'Leaders']);
        $test_framework->leaders->region_a = $generator->create_cohort(['name' => 'Leaders - Region A']);
        $test_framework->leaders->region_b = $generator->create_cohort(['name' => 'Leaders - Region B']);
        $test_framework->leaders->region_c = $generator->create_cohort(['name' => 'Leaders - Region C']);

        // Change the default assignment.
        $assignment_entity->assignment_type = assignment_type\cohort::get_code();
        $assignment_entity->assignment_identifier = $test_framework->leaders->id;
        $assignment_entity->save();
        $test_assignment = assignment::load_by_entity($assignment_entity);

        // Create overrides for all of those positions.
        // Create three override assignments
        $sub_audiences = [
            $test_framework->leaders->region_a,
            $test_framework->leaders->region_b,
            $test_framework->leaders->region_c,
        ];
        $test_overrides = new collection();
        foreach ($sub_audiences as $cohort) {
            $assignment_go = new assignment_generator_object($test_workflow->course_id, assignment_type\cohort::get_code(), $cohort->id);
            $assignment_go->is_default = false;
            $assignment_go->status = status::ACTIVE;
            $entity = $this->generator()->create_assignment($assignment_go);
            $test_overrides->append(assignment::load_by_entity($entity));
        }

        // Create an approver, and find stage1 and level1
        $test_approver = new user($this->getDataGenerator()->create_user());
        $test_stage1 = $test_workflow->latest_version->stages->first();
        $test_stage2 = $test_workflow->latest_version->get_next_stage($test_stage1->id);
        $test_level1 = $test_stage2->approval_levels->first();

        /**
         * framework->leaders            - approver
         * framework->leaders->region_a  - other
         * framework->leaders->region_b  - approver (inherited)
         * framework->leaders->region_c  - approver (inherited)
         */

        // Assign approver to default assignment.
        assignment_approver::create(
            $test_assignment,
            $test_level1,
            user_approver_type::TYPE_IDENTIFIER,
            $test_approver->id
        );

        // Assign other approver to region_a override.
        $other = new user($this->getDataGenerator()->create_user());
        assignment_approver::create(
            $test_overrides->find('assignment_identifier', $test_framework->leaders->region_a->id),
            $test_level1,
            user_approver_type::TYPE_IDENTIFIER,
            $other->id
        );

        /**
         * Test get_descendant_levels()
         */
        $scenarios = [
            [$test_assignment, [
                $test_framework->leaders->region_b->id,
                $test_framework->leaders->region_c->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->leaders->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->leaders->region_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->leaders->region_c->id), []],
        ];

        foreach ($scenarios as $scenario) {
            [$assignment, $expected] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $test_level1
            );
            $descendants = $assignment_approval_level->get_descendants();
            if (is_null($expected)) {
                self::assertCount(0, $descendants, "Failed for {$assignment->name}");
            } else {
                $assignment_ids = [];
                foreach ($descendants as $assignment_approval_level) {
                    $assignment_ids[] = $assignment_approval_level->get_assignment()->id;
                    self::assertEquals($test_level1->id, $assignment_approval_level->get_approval_level()->id);
                }
            }
        }
    }

    /**
     * Reusable setup for activemode tests.
     */
    private function activemode_test_setup(): array {
        $this->setAdminUser();

        // Generate a workflow with override assignments.
        list($workflow_entity, $test_framework, $assignment_entity) = $this->create_workflow_and_assignment('Testing', false);
        $test_workflow = workflow::load_by_entity($workflow_entity);
        $test_stage1 = $test_workflow->latest_version->stages->first();
        $test_stage2 = $test_workflow->latest_version->get_next_stage($test_stage1->id);
        $test_level1 = $test_stage2->approval_levels->first();

        // Replace the framework with positions.
        $test_framework = $this->generate_pos_hierarchy();

        // Change the default assignment.
        $assignment_entity->assignment_type = assignment_type\position::get_code();
        $assignment_entity->assignment_identifier = $test_framework->division->id;
        $assignment_entity->save();
        $default_assignment = assignment::load_by_entity($assignment_entity);

        // Create overrides for all of those positions.
        // Create three override assignments
        $sub_divisions = [
            [$test_framework->division->position_a, status::ACTIVE],
            [$test_framework->division->position_a->grade_a, status::ACTIVE],
            [$test_framework->division->position_a->grade_a->region_a, status::ACTIVE],
            [$test_framework->division->position_a->grade_a->region_b, status::ARCHIVED],
            [$test_framework->division->position_a->grade_b, status::ACTIVE],
            [$test_framework->division->position_a->grade_b->region_a, status::ACTIVE],
            [$test_framework->division->position_a->grade_b->region_b, status::ARCHIVED],
            [$test_framework->division->position_b, status::ACTIVE],
            [$test_framework->division->position_b->grade_a, status::ACTIVE],
            [$test_framework->division->position_b->grade_a->region_a, status::ACTIVE],
            [$test_framework->division->position_b->grade_a->region_b, status::ARCHIVED],
            [$test_framework->division->position_b->grade_b, status::ACTIVE],
            [$test_framework->division->position_b->grade_b->region_a, status::ACTIVE],
            [$test_framework->division->position_b->grade_b->region_b, status::ARCHIVED],
        ];
        $override_assignments = new collection();
        foreach ($sub_divisions as [$pos, $status]) {
            // For a published workflow, override assignments can only be ACTIVE or ARCHIVED.
            $assignment = assignment::create($test_workflow->course_id, assignment_type\position::get_code(), $pos->id);
            if ($status === status::ARCHIVED) {
                $assignment->archive();
            }
            $override_assignments->append($assignment);
        }

        /**
         * Set up assignment overrides so they look like this with activemode on:
         *
         * framework->division (active)                                  - other1
         * framework->division->position_a (active)                      - approver
         * framework->division->position_a->grade_a->region_a (active)   - approver (inherited)
         * framework->division->position_a->grade_a->region_b (active)   - approver
         * framework->division->position_a->grade_b (active)             - approver (inherited)
         * framework->division->position_a->grade_b->region_a (active)   - approver (inherited)
         * framework->division->position_b->grade_a->region_a (active)   - other1 (inherited)
         * framework->division->position_b->grade_b (active)             - other3
         * framework->division->position_b->grade_a (active)             - approver
         * framework->division->position_b->grade_b->region_a (active)   - other3 (inherited)
         *
         * And they look like this with activemode off:
         *
         * framework->division (active)                                  - other1
         * framework->division->position_a (active)                      - approver
         * framework->division->position_a->grade_a (active)              - other2
         * framework->division->position_a->grade_a->region_a (active)   - other2 (inherited)
         * framework->division->position_a->grade_a->region_b (archived) - approver
         * framework->division->position_a->grade_b (active)             - approver (inherited)
         * framework->division->position_a->grade_b->region_a (active)   - approver (inherited)
         * framework->division->position_a->grade_b->region_b (archived) - approver (inherited)
         * framework->division->position_b (active)                       - other2
         * framework->division->position_b->grade_a (active)              - approver
         * framework->division->position_b->grade_a->region_a (active)   - approver (inherited)
         * framework->division->position_b->grade_a->region_b (archived) - approver (inherited)
         * framework->division->position_b->grade_b (active)             - other3
         * framework->division->position_b->grade_b->region_a (active)   - other3 (inherited)
         * framework->division->position_b->grade_b->region_b (archived) - other3 (inherited)
         */
        // Set 'test_approver' user as approver for the following override assignments.
        $test_approver = new user($this->getDataGenerator()->create_user());
        $approver_assignment_levels = [
            $test_framework->division->position_a->id,
            $test_framework->division->position_a->grade_a->region_b->id,
            $test_framework->division->position_b->grade_a->id,
        ];

        foreach ($approver_assignment_levels as $assignment_id) {
            assignment_approver::create(
                $override_assignments->find('assignment_identifier', $assignment_id),
                $test_level1,
                user_approver_type::TYPE_IDENTIFIER,
                $test_approver->id
            );
        }

        // Set 'other1' user as approver for default assignment.
        $other1 = new user($this->getDataGenerator()->create_user());
        assignment_approver::create(
            $default_assignment,
            $test_level1,
            user_approver_type::TYPE_IDENTIFIER,
            $other1->id
        );

        // Set 'other2' user as approver for following override assignments.
        $other2 = new user($this->getDataGenerator()->create_user());
        $other2_assignment_levels = [
            $test_framework->division->position_a->grade_a->id,
            $test_framework->division->position_b->id,
        ];

        foreach ($other2_assignment_levels as $assignment_id) {
            assignment_approver::create(
                $override_assignments->find('assignment_identifier', $assignment_id),
                $test_level1,
                user_approver_type::TYPE_IDENTIFIER,
                $other2->id
            );
        }

        // Set 'other3' user as approver for following override assignments.
        $other3 = new user($this->getDataGenerator()->create_user());
        $other3_assignment_levels = [
            $test_framework->division->position_b->grade_b->id,
        ];

        foreach ($other3_assignment_levels as $assignment_id) {
            assignment_approver::create(
                $override_assignments->find('assignment_identifier', $assignment_id),
                $test_level1,
                user_approver_type::TYPE_IDENTIFIER,
                $other3->id
            );
        }

        return [$default_assignment, $test_framework, $override_assignments, $test_level1];
    }

    /**
     * @covers ::set_activemode
     * @covers ::get_activemode
     */
    public function test_set_activemode(): void {
        list($test_assignment, $test_framework, $test_overrides, $test_level1) = $this->activemode_test_setup();

        $assignment_approval_level = new assignment_approval_level(
            $test_assignment,
            $test_level1
        );
        $this->assertFalse($assignment_approval_level->get_activemode());
        $assignment_approval_level->set_activemode(true);
        $this->assertTrue($assignment_approval_level->get_activemode());
        $assignment_approval_level->set_activemode(false);
        $this->assertFalse($assignment_approval_level->get_activemode());
    }

    /**
     * Test get_descendants() with activemode on
     *
     * @covers ::get_descendants
     */
    public function test_get_descendants_activemode(): void {
        list($test_assignment, $test_framework, $test_overrides, $test_level1) = $this->activemode_test_setup();

        $activemode_scenarios = [
            [$test_assignment, [
                $test_framework->division->position_b->grade_a->region_a->id
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->id), [
                $test_framework->division->position_a->grade_a->region_a->id,
                $test_framework->division->position_a->grade_b->id,
                $test_framework->division->position_a->grade_b->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->id), [
                $test_framework->division->position_a->grade_a->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->region_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->id), [
                $test_framework->division->position_a->grade_b->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->region_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->id), [
                $test_framework->division->position_b->grade_a->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->id), [
                $test_framework->division->position_b->grade_a->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->region_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->id), [
                $test_framework->division->position_b->grade_b->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->region_b->id), []],
        ];

        foreach ($activemode_scenarios as $scenario) {
            [$assignment, $expected] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $test_level1
            );
            $assignment_approval_level->set_activemode(true);
            $descendants = $assignment_approval_level->get_descendants();
            if (is_null($expected)) {
                self::assertCount(0, $descendants, "Failed for {$assignment->name}");
            } else {
                $assignment_ids = [];
                foreach ($descendants as $assignment_approval_level) {
                    $assignment_ids[] = $assignment_approval_level->get_assignment()->id;
                    self::assertEquals($test_level1->id, $assignment_approval_level->get_approval_level()->id);
                }
            }
        }
    }

    /**
     * Test get_descendants() with activemode off
     *
     * @covers ::get_descendants
     */
    public function test_get_descendants_no_activemode(): void {
        list($test_assignment, $test_framework, $test_overrides, $test_level1) = $this->activemode_test_setup();

        $noactivemode_scenarios = [
            [$test_assignment, []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->id), [
                $test_framework->division->position_a->grade_b->id,
                $test_framework->division->position_a->grade_b->region_a->id,
                $test_framework->division->position_a->grade_b->region_b->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->id), [
                $test_framework->division->position_a->grade_a->region_a->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->region_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->id), [
                $test_framework->division->position_a->grade_b->region_a->id,
                $test_framework->division->position_a->grade_b->region_b->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->region_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->id), [
                $test_framework->division->position_b->grade_a->region_a->id,
                $test_framework->division->position_b->grade_a->region_b->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->region_b->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->id), [
                $test_framework->division->position_b->grade_b->region_a->id,
                $test_framework->division->position_b->grade_b->region_b->id,
            ]],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->region_a->id), []],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->region_b->id), []],
        ];

        foreach ($noactivemode_scenarios as $scenario) {
            [$assignment, $expected] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $test_level1
            );
            $descendants = $assignment_approval_level->get_descendants();
            if (is_null($expected)) {
                self::assertCount(0, $descendants, "Failed for {$assignment->name}");
            } else {
                $assignment_ids = [];
                foreach ($descendants as $assignment_approval_level) {
                    $assignment_ids[] = $assignment_approval_level->get_assignment()->id;
                    self::assertEquals($test_level1->id, $assignment_approval_level->get_approval_level()->id);
                }
            }
        }
    }

    /**
     * Test get_inherited_from_assignment_approval_level() with activemode on
     *
     * @covers ::get_inherited_from_assignment_approval_level
     */
    public function test_get_inherited_from_assignment_approval_level_activemode(): void {
        list($test_assignment, $test_framework, $test_overrides, $test_level1) = $this->activemode_test_setup();

        $activemode_scenarios = [
            [$test_assignment, null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->region_a->id), $test_framework->division->position_a->grade_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->region_b->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->id), $test_framework->division->position_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->region_a->id), $test_framework->division->position_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->region_b->id), $test_framework->division->position_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->region_a->id), $test_framework->division->position_b->grade_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->region_b->id), $test_framework->division->position_b->grade_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->region_a->id), $test_framework->division->position_b->grade_b->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->region_b->id), $test_framework->division->position_b->grade_b->id],
        ];

        foreach ($activemode_scenarios as $scenario) {
            [$assignment, $expected_identifier] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $test_level1
            );
            $assignment_approval_level->set_activemode(true);
            $result_assignment_approval_level = $assignment_approval_level->get_inherited_from_assignment_approval_level();
            if (is_null($expected_identifier)) {
                self::assertNull($result_assignment_approval_level);
            } else {
                self::assertEquals($expected_identifier, $result_assignment_approval_level->get_assignment()->assignment_identifier);
                self::assertEquals($test_level1->id, $result_assignment_approval_level->get_approval_level()->id);
            }
        }
    }

    /**
     * Test get_inherited_from_assignment_approval_level() with activemode off
     *
     * @covers ::get_inherited_from_assignment_approval_level
     */
    public function test_get_inherited_from_assignment_approval_level_no_activemode(): void {
        list($test_assignment, $test_framework, $test_overrides, $test_level1) = $this->activemode_test_setup();

        $noactivemode_scenarios = [
            [$test_assignment, null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->region_a->id), $test_framework->division->position_a->grade_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_a->region_b->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->id), $test_framework->division->position_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->region_a->id), $test_framework->division->position_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_a->grade_b->region_b->id), $test_framework->division->position_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->region_a->id), $test_framework->division->position_b->grade_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_a->region_b->id), $test_framework->division->position_b->grade_a->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->id), null],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->region_a->id), $test_framework->division->position_b->grade_b->id],
            [$test_overrides->find('assignment_identifier', $test_framework->division->position_b->grade_b->region_b->id), $test_framework->division->position_b->grade_b->id],
        ];

        foreach ($noactivemode_scenarios as $scenario) {
            [$assignment, $expected_identifier] = $scenario;
            if (get_class($assignment) !== assignment::class) {
                $assignment = assignment::load_by_entity($assignment);
            }
            $assignment_approval_level = new assignment_approval_level(
                $assignment,
                $test_level1
            );
            $result_assignment_approval_level = $assignment_approval_level->get_inherited_from_assignment_approval_level();
            if (is_null($expected_identifier)) {
                self::assertNull($result_assignment_approval_level);
            } else {
                self::assertEquals($expected_identifier, $result_assignment_approval_level->get_assignment()->assignment_identifier);
                self::assertEquals($test_level1->id, $result_assignment_approval_level->get_approval_level()->id);
            }
        }
    }
}