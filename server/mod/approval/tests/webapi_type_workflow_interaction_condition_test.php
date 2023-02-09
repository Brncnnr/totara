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
 * @author Angela Kuznetsova <Angela.Kuznetsova@totaralearning.com>
 */

use core\webapi\execution_context;
use core_phpunit\testcase;
use mod_approval\model\workflow\interaction\condition\interaction_condition;
use mod_approval\webapi\resolver\type\workflow_interaction_condition;

require_once __DIR__ . '/fixtures/workflow/sample_interaction_condition.php';

/**
 * @coversDefaultClass mod_approval\webapi\resolver\type\workflow_interaction_condition
 *
 * @group approval_workflow
 */
class mod_approval_webapi_type_workflow_interaction_condition_testcase extends testcase {

    public function test_resolves_only_stage_type() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("source must be an instance of " . interaction_condition::class);
        workflow_interaction_condition::resolve('', new stdClass(), [], $this->createMock(execution_context::class));
    }

    public function test_resolves_fields() {
        $execution_context = $this->createMock(execution_context::class);
        $condition_key_string = 'condition_key';
        $condition_data_string = json_encode(['comparison' => 'exists', 'value' => 'delete']);
        $source = new sample_interaction_condition($condition_key_string, $condition_data_string);

        $key = workflow_interaction_condition::resolve('condition_key', $source, [], $execution_context);
        $data = workflow_interaction_condition::resolve('condition_data', $source, [], $execution_context);

        $this->assertEquals($source->condition_key_field(), $key);
        $this->assertEquals($source->condition_data_field(), $data);
    }
}
