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
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass mod_approval\webapi\resolver\type\workflow
 *
 * @group approval_workflow
 */
class mod_approval_webapi_type_workflow_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_workflow';
    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Expected workflow model");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        [$workflow] = $this->create_workflow_and_assignment();
        $workflow = workflow_model::load_by_entity($workflow);

        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $workflow, [], $workflow->get_context());
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        [$workflow]  = $this->create_workflow_and_assignment();
        $workflow = workflow_model::load_by_entity($workflow);

        $testcases = [
            'id' => [null, $workflow->id],
            'id_number' => [null, $workflow->id_number],
            'name' => [format::FORMAT_PLAIN, 'Simple Request Workflow'],
            'description' => [format::FORMAT_PLAIN, null],
            'workflow_type' => [null, $workflow->workflow_type],
            'latest_version' => [null, $workflow->latest_version],
            'default_assignment' => [null, $workflow->default_assignment],
            'created' => [date_format::FORMAT_TIMESTAMP, $workflow->created],
            'updated' => [date_format::FORMAT_TIMESTAMP, $workflow->updated],
        ];

        foreach ($testcases as $field => $testcase) {
            [$format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = $this->resolve_graphql_type(self::TYPE, $field, $workflow, $args, $workflow->get_context());
            $expected instanceof stdClass
                ? $this->assertInstanceOf(get_class($expected), $value, "[$field] wrong value")
                : $this->assertEquals($expected, $value, "[$field] wrong value");
        }
    }
}