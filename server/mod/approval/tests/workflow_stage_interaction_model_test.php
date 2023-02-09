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

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\entity\workflow\workflow_stage_interaction as workflow_stage_interaction_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\workflow\workflow_version;
use mod_approval\model\workflow\workflow_stage_interaction;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\workflow_stage_interaction
 */
class mod_approval_workflow_stage_interaction_model_testcase extends testcase {

    use approval_workflow_test_setup;

    /**
     * @covers ::create
     */
    public function test_create(): void {
        /* @var \mod_approval\entity\workflow\workflow $workflow */
        [$workflow, $framework, $assignment] = $this->create_workflow_and_assignment();
        $workflow_version = workflow_version::load_by_entity($workflow->versions->first());
        $workflow_stage = $workflow_version->stages->first();

        $time = time();
        $interaction = workflow_stage_interaction::create($workflow_stage, new approve());
        $this->assertNotEmpty($interaction->id);
        $this->assertEquals($workflow_stage->id, $interaction->workflow_stage->id);
        $this->assertEquals(approve::get_code(), $interaction->action_code);
        $this->assertGreaterThanOrEqual($time, $interaction->created);
        $this->assertLessThanOrEqual($interaction->updated, $interaction->created);
    }

    /**
     * @covers ::create
     */
    public function test_create_with_inactive_workflow_stage(): void {
        [$workflow, $framework, $assignment] = $this->create_workflow_and_assignment();
        $workflow_version = workflow_version::load_by_entity($workflow->versions->first());
        $workflow_stage = $workflow_version->stages->first();
        $workflow_stage->deactivate();
        try {
            $interaction = workflow_stage_interaction::create($workflow_stage, new approve());
            $this->fail('model_exception expected');
        } catch (model_exception $e) {
            $this->assertEquals('Workflow stage must be active', $e->debuginfo);
        }
    }

    /**
     * @covers ::refresh
     */
    public function test_refresh(): void {
        [$workflow, $framework, $assignment] = $this->create_workflow_and_assignment();
        $workflow_version = workflow_version::load_by_entity($workflow->versions->first());
        $workflow_stage = $workflow_version->stages->first();

        $interaction = workflow_stage_interaction::create($workflow_stage, new approve());
        $this->assertNotEmpty($interaction->id);
        $this->assertEquals(approve::get_code(), $interaction->action_code);
        builder::table(workflow_stage_interaction_entity::TABLE)->update(['action_code' => reject::get_code()]);
        $this->assertEquals(approve::get_code(), $interaction->action_code);
        $interaction->refresh();
        $this->assertEquals(reject::get_code(), $interaction->action_code);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        [$workflow, $framework, $assignment] = $this->create_workflow_and_assignment();
        $workflow_version = workflow_version::load_by_entity($workflow->versions->first());
        $workflow_stage = $workflow_version->stages->first();

        $interaction1 = workflow_stage_interaction::create($workflow_stage, new approve());
        $interaction2 = workflow_stage_interaction::create($workflow_stage, new approve());
        $this->assertNotEmpty($interaction1->id);
        $this->assertNotEmpty($interaction2->id);
        $interaction2->delete();
        $this->assertEmpty($interaction2->id);
        $this->assertFalse(builder::table(workflow_stage_interaction_entity::TABLE)->where('id', $interaction2->id)->exists());
        $this->assertTrue(builder::table(workflow_stage_interaction_entity::TABLE)->where('id', $interaction1->id)->exists());
    }
}
