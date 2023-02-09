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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 * @category test
 */

use core\webapi\execution_context;
use totara_webapi\endpoint_type\dev;

/**
 * @group core_webapi
 */
class core_webapi_execution_context_testcase extends advanced_testcase {

    public function test_set_relevant_context_does_not_allow_system(): void {
        $execution_context = execution_context::create('ajax');

        $context = context_system::instance();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Do not use the system context, provide an specific context or do not set a context.');

        $execution_context->set_relevant_context($context);
    }

    public function test_set_relevant_context(): void {
        $execution_context = execution_context::create('ajax');

        $this->assertFalse($execution_context->has_relevant_context());

        $context = context_course::instance(SITEID);

        $execution_context->set_relevant_context($context);

        $this->assertTrue($execution_context->has_relevant_context());
        $this->assertEquals($context, $execution_context->get_relevant_context());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context can only be set once per execution');

        $execution_context->set_relevant_context($context);
    }

    public function test_get_relevant_context() {
        $execution_context = execution_context::create('dev', 'myoperation');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Context has not been provided for this execution');

        $execution_context->get_relevant_context();
    }

    public function test_execution_context() {
        $execution_context = execution_context::create('dev', 'myoperation');

        $this->assertInstanceOf(dev::class, $execution_context->get_endpoint_type());
        $this->assertEquals('myoperation', $execution_context->get_operationname());

        $execution_context->set_operationname('newoperation');
        $this->assertEquals('newoperation', $execution_context->get_operationname());
    }
}
