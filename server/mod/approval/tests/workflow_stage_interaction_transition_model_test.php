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
use mod_approval\entity\workflow\workflow_stage_interaction_transition as workflow_stage_interaction_transition_entity;
use mod_approval\model\application\action\approve;
use mod_approval\model\workflow\interaction\condition\interaction_condition;
use mod_approval\model\workflow\interaction\transition\next;
use mod_approval\model\workflow\interaction\transition\stage;
use mod_approval\model\workflow\workflow_version;
use mod_approval\model\workflow\workflow_stage_interaction;
use mod_approval\model\workflow\workflow_stage_interaction_transition;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\workflow_stage_interaction_transition
 */
class mod_approval_workflow_stage_interaction_transition_model_testcase extends testcase {

    use approval_workflow_test_setup;

    /**
     * @covers ::create
     * @covers ::get_workflow_stage_interaction
     * @covers ::get_transition
     */
    public function test_create_no_condition(): void {
        $interaction = $this->generate_test_interaction();

        $transition = new next();

        $time = time();
        $transition = workflow_stage_interaction_transition::create($interaction, null, $transition);
        $this->assertNotEmpty($transition->id);
        $this->assertEquals($interaction->id, $transition->workflow_stage_interaction->id);
        $this->assertInstanceOf(next::class, $transition->transition);
        $this->assertGreaterThanOrEqual($time, $transition->created);
        $this->assertLessThanOrEqual($transition->updated, $transition->created);
    }

    /**
     * @covers ::create
     * @covers ::get_condition
     * @covers ::get_condition_key_field
     * @covers ::get_condition_data_field
     */
    public function test_create_with_condition(): void {
        $interaction = $this->generate_test_interaction();

        $condition = $this->get_test_condition();
        $transition = new next();

        $transition = workflow_stage_interaction_transition::create($interaction, $condition, $transition);
        $this->assertNotEmpty($transition->id);
        $this->assertInstanceOf(interaction_condition::class, $transition->condition);
        $this->assertEquals('foo', $transition->get_condition_key_field());
        $this->assertEquals('{"comparison":"equals","value":"bar"}', $transition->get_condition_data_field());
    }

    /**
     * @covers ::set_transition
     * @covers ::get_transition_field
     */
    public function test_set_transition(): void {
        $interaction = $this->generate_test_interaction();

        $condition = $this->get_test_condition();
        $transition = new next();

        $transition = workflow_stage_interaction_transition::create($interaction, $condition, $transition);
        $this->assertEquals('NEXT', $transition->get_transition_field());

        $new_transition = new stage($interaction->workflow_stage_id);
        $transition->set_transition($new_transition);
        $transition->refresh(true);
        $this->assertInstanceOf(stage::class, $transition->transition);
        $this->assertEquals($interaction->workflow_stage->id, $transition->get_transition_field());
    }

    /**
     * @covers ::set_priority
     */
    public function test_set_priority(): void {
        $interaction = $this->generate_test_interaction();

        $condition = $this->get_test_condition();
        $transition = new next();

        $transition = workflow_stage_interaction_transition::create($interaction, $condition, $transition);
        $this->assertEquals(1, $transition->priority);

        $transition->set_priority(42);
        $transition->refresh(true);
        $this->assertEquals(42, $transition->priority);
    }

    /**
     * @covers ::refresh
     */
    public function test_refresh(): void {
        $interaction = $this->generate_test_interaction();

        $condition = $this->get_test_condition();
        $transition = new next();

        $transition = workflow_stage_interaction_transition::create($interaction, $condition, $transition);
        $this->assertNotEmpty($transition->id);
        $this->assertEquals('foo', $transition->get_condition_key_field());
        builder::table(workflow_stage_interaction_transition_entity::TABLE)->update(['condition_key' => 'quux']);
        $this->assertEquals('foo', $transition->get_condition_key_field());
        $transition->refresh();
        $this->assertEquals('quux', $transition->get_condition_key_field());
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        $interaction = $this->generate_test_interaction();

        $condition = $this->get_test_condition();
        $transition = new next();

        $transition1 = workflow_stage_interaction_transition::create($interaction, null, $transition);
        $transition2 = workflow_stage_interaction_transition::create($interaction, $condition, $transition);
        $this->assertNotEmpty($transition1->id);
        $this->assertNotEmpty($transition2->id);
        $transition2->delete();
        $this->assertEmpty($transition2->id);
        $this->assertFalse(builder::table(workflow_stage_interaction_transition_entity::TABLE)->where('id', $transition2->id)->exists());
        $this->assertTrue(builder::table(workflow_stage_interaction_transition_entity::TABLE)->where('id', $transition1->id)->exists());
    }

    private function generate_test_interaction(): workflow_stage_interaction {
        // Create an unpublished workflow.
        [$workflow, $framework, $assignment] = $this->create_workflow_and_assignment('Draft', false, false);
        $workflow_version = workflow_version::load_by_entity($workflow->versions->first());
        $workflow_stage = $workflow_version->stages->first();
        /* @var \mod_approval\model\workflow\workflow_stage $workflow_stage */
        return $workflow_stage->add_interaction(new approve());
    }

    private function get_test_condition(): interaction_condition {
        return new interaction_condition('foo', '{"comparison":"equals","value":"bar"}');
    }

}
