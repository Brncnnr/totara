<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

use core\date_format;
use core\format;
use core_phpunit\testcase;
use mod_approval\model\application\action\approve;
use mod_approval\model\workflow\interaction\transition\next;
use mod_approval\model\workflow\workflow_stage_interaction;
use mod_approval\model\workflow\workflow_stage_interaction_transition;
use mod_approval\model\workflow\workflow as workflow_model;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass mod_approval\webapi\resolver\type\workflow_stage_interaction_transition
 *
 * @group approval_workflow
 */
class mod_approval_webapi_type_workflow_stage_interaction_transition_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_workflow_stage_interaction_transition';
    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Wrong object is passed");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        [$workflow]  = $this->create_workflow_and_assignment();
        $workflow = workflow_model::load_by_entity($workflow);
        $stage = $workflow->latest_version->stages->first();
        $mock_stage_interaction = workflow_stage_interaction::create($stage, new approve());
        $mock_next = $this->createMock(next::class);
        $mock_stage_interaction_transition = workflow_stage_interaction_transition::create($mock_stage_interaction, null, $mock_next);

        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $mock_stage_interaction_transition, [], $workflow->get_context());
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        [$workflow]  = $this->create_workflow_and_assignment();
        $workflow = workflow_model::load_by_entity($workflow);
        $stage = $workflow->latest_version->stages->first();
        $interaction = $stage->interactions->first();
        $transition = $interaction->default_transition;

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $transition, [], $workflow->get_context());
        $this->assertEquals($transition->id, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'priority', $transition, ['format' => format::FORMAT_PLAIN], $workflow->get_context());
        $this->assertEquals('1', $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'transition', $transition, ['format' => format::FORMAT_PLAIN], $workflow->get_context());
        $this->assertEquals('NEXT', $value);
    }
}