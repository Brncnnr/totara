<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use mod_approval\model\application\application_state;
use mod_approval\model\workflow\interaction\transition\reset;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\interaction\transition\reset
 */
class mod_approval_transition_reset_testcase extends testcase {

    use approval_workflow_test_setup;

    /**
     * @covers ::get_enum
     */
    public function test_enum(): void {
        $this->assertEquals('RESET', reset::get_enum());
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        [$workflow, $framework, $assignment] = $this->create_workflow_and_assignment('Testing', false, false);
        $workflow_model = workflow::load_by_entity($workflow);
        $stages = $workflow_model->latest_version->stages;
        $stages->rewind();
        $stage1 = $stages->current();
        $stages->next();
        $stage2 = $stages->current();
        $stages->next();
        $stage3 = $stages->current();

        $transition = new reset();
        $this->assertInstanceOf(application_state::class, $transition->resolve($stage2->state_manager->get_initial_state()));
        // Resolve to get the state, then check that it has the correct stage id.
        $this->assertEquals($stage1->id, $transition->resolve($stage1->state_manager->get_initial_state())->get_stage_id());
        $this->assertEquals($stage2->id, $transition->resolve($stage2->state_manager->get_initial_state())->get_stage_id());
        $this->assertEquals($stage3->id, $transition->resolve($stage3->state_manager->get_initial_state())->get_stage_id());
    }

    /**
     * @covers ::transition_field
     */
    public function test_to_field_method(): void {
        $transition = new reset();
        $this->assertEquals('RESET', $transition->transition_field());
    }

    /**
     * @covers ::get_sort_order
     */
    public function test_get_sort_order(): void {
        $transition = new reset();
        $this->assertEquals(30, $transition->get_sort_order());
    }

    /**
     * @covers ::get_options
     */
    public function test_get_options(): void {
        [$workflow, $framework, $assignment] = $this->create_workflow_and_assignment('Testing', false, false);
        $workflow_model = workflow::load_by_entity($workflow);
        $stages = $workflow_model->latest_version->stages;
        $stages->rewind();
        $stages->next();
        $stage2 = $stages->current();
        $transition = new reset();

        $this->assertEquals($transition::get_enum(), $transition->get_options($stage2)[0]->get_value());
    }
}