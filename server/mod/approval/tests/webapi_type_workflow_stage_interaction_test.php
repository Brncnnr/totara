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
use mod_approval\model\application\action\submit;
use mod_approval\model\workflow\workflow_stage_interaction;
use mod_approval\model\workflow\workflow as workflow_model;
use mod_approval\model\workflow\workflow_stage_interaction_transition;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass mod_approval\webapi\resolver\type\workflow_stage_interaction
 *
 * @group approval_workflow
 */
class mod_approval_webapi_type_workflow_stage_interaction_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_workflow_stage_interaction';
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
        $stage_interaction = workflow_stage_interaction::create($stage, new approve());

        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $stage_interaction, [], $workflow->get_context());
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        [$workflow]  = $this->create_workflow_and_assignment();
        $workflow = workflow_model::load_by_entity($workflow);
        $stage = $workflow->latest_version->stages->first();
        /** @var \mod_approval\model\workflow\workflow_stage $stage */
        $stage_interaction = $stage->interactions->first();

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $stage_interaction, [], $workflow->get_context());
        $this->assertEquals($stage_interaction->id, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'workflow_stage', $stage_interaction, ['format' => format::FORMAT_PLAIN], $workflow->get_context());
        $this->assertEquals($stage->id, $value->id);
        $value = $this->resolve_graphql_type(self::TYPE, 'created', $stage_interaction, ['format' => date_format::FORMAT_TIMESTAMP], $workflow->get_context());
        $this->assertEquals($stage_interaction->created, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'updated', $stage_interaction, ['format' => date_format::FORMAT_TIMESTAMP], $workflow->get_context());
        $this->assertEquals($stage_interaction->updated, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'action_code', $stage_interaction, [], $workflow->get_context());
        $this->assertEquals(submit::get_enum(), $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'conditional_transitions', $stage_interaction, [], $workflow->get_context());
        $this->assertEmpty($value);
        $value = $this->resolve_graphql_type(self::TYPE, 'default_transition', $stage_interaction, [], $workflow->get_context());
        $this->assertInstanceOf(workflow_stage_interaction_transition::class, $value);
    }
}