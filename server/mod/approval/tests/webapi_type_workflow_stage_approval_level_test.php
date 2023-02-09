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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_approval
 */

use core\date_format;
use core\format;
use core_phpunit\testcase;
use mod_approval\model\workflow\workflow as workflow_model;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass mod_approval\webapi\resolver\type\workflow_stage_approval_level
 *
 * @group approval_workflow
 */
class mod_approval_webapi_type_workflow_stage_approval_level_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_workflow_stage_approval_level';

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Expected workflow stage approval level model");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        $approval_level = $this->createMock(workflow_stage_approval_level::class);
        $ec = $this->createMock(context::class);

        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $approval_level, [], $ec);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        [$workflow]  = $this->create_workflow_and_assignment();
        $workflow = workflow_model::load_by_entity($workflow);
        $stage_1 =  $workflow->latest_version->get_stages()->first();

        /** @var workflow_stage $workflow_stage*/
        $workflow_stage = $workflow->latest_version->get_next_stage($stage_1->id);

        /** @var workflow_stage_approval_level $approval_level*/
        $approval_level =  $workflow_stage->approval_levels->first();

        $testcases = [
            'id' => [null, $approval_level->id],
            'name' => [format::FORMAT_PLAIN, 'Level 1'],
            'workflow_stage' => [null, $approval_level->workflow_stage],
            'ordinal_number' => [null, 1],
            'active' => [null, true],
            'created' => [date_format::FORMAT_TIMESTAMP, $approval_level->created],
            'updated' => [date_format::FORMAT_TIMESTAMP, $approval_level->updated],
        ];

        foreach ($testcases as $field => $testcase) {
            [$format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = $this->resolve_graphql_type(self::TYPE, $field, $approval_level, $args, $workflow->get_context());
            $expected instanceof stdClass
                ? $this->assertInstanceOf(get_class($expected), $value, "[$field] wrong value")
                : $this->assertEquals($expected, $value, "[$field] wrong value");
        }
    }
}