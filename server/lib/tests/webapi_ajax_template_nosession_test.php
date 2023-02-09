<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

use \totara_webapi\graphql;
use core\webapi\execution_context;

class core_webapi_ajax_template_nosession_testcase extends advanced_testcase {
    public function test_execute() {
        $this->setUser(null);

        $result = graphql::execute_operation(execution_context::create('ajax', 'core_template_nosession'), ['name' => 'block', 'component' => 'core', 'theme' => 'base']);
        $result = $result->toArray(true);
        $this->assertStringContainsString('The purpose of this template is to render a block and its contents.', $result['data']['template']);

        $result = graphql::execute_operation(execution_context::create('ajax', 'core_template_nosession'), ['name' => 'block', 'component' => 'core', 'theme' => 'xxxbase']);
        $result = $result->toArray(true);
        $this->assertArrayNotHasKey('data', $result);
        $this->assertSame('Variable "$theme" got invalid value "xxxbase"; Expected type param_theme; Invalid parameter value detected', $result['errors'][0]['debugMessage']);

        $result = graphql::execute_operation(execution_context::create('ajax', 'core_template_nosession'), ['name' => 'xxxblock', 'component' => 'core', 'theme' => 'base']);
        $result = $result->toArray(true);
        $this->assertArrayNotHasKey('data', $result);
        $this->assertSame('Coding error detected, it must be fixed by a programmer: Template does not exist', $result['errors'][0]['debugMessage']);
    }
}