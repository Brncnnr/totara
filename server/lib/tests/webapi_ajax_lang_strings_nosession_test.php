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

class core_webapi_ajax_lang_strings_nosession_testcase extends advanced_testcase {

    public function test_execute() {
        $this->setUser(null);

        $result = graphql::execute_operation(
            execution_context::create('ajax', 'core_lang_strings_nosession'),
            ['lang' => 'en', 'ids' => ['edit,core']]
        );
        $result = $result->toArray(true);
        $expected = [
            'lang_strings' => [
                ['lang' => 'en', 'identifier' => 'edit', 'component' => 'core', 'string' => 'Edit'],
            ]
        ];
        $this->assertArrayHasKey('data', $result);
        $this->assertSame($expected, $result['data']);

        $result = graphql::execute_operation(
            execution_context::create('ajax', 'core_lang_strings_nosession'),
            ['lang' => 'en', 'ids' => [' edit , core ', 'delete , moodle']]
        );
        $result = $result->toArray(true);
        $expected = [
            'lang_strings' => [
                ['lang' => 'en', 'identifier' => 'edit', 'component' => 'core', 'string' => 'Edit'],
                ['lang' => 'en', 'identifier' => 'delete', 'component' => 'moodle', 'string' => 'Delete'],
            ]
        ];
        $this->assertArrayHasKey('data', $result);
        $this->assertSame($expected, $result['data']);
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'core_lang_strings_nosession'),
            ['lang' => 'xx', 'ids' => ['edit,core']]
        );
        $result = $result->toArray(true);
        $this->assertArrayNotHasKey('data', $result);
        $this->assertSame(
            'Variable "$lang" got invalid value "xx"; Expected type param_lang; Invalid parameter value detected',
            $result['errors'][0]['debugMessage']
        );

        $this->assertDebuggingNotCalled();
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'core_lang_strings_nosession'),
            ['lang' => 'en', 'ids' => ['xxedit,core']]
        );
        $result = $result->toArray(true);
        $expected = [
            'lang_strings' => [
                ['lang' => 'en', 'identifier' => 'xxedit', 'component' => 'core', 'string' => '[[xxedit]]'],
            ]
        ];
        $this->assertArrayHasKey('data', $result);
        $this->assertSame($expected, $result['data']);
        $this->assertDebuggingCalled();

        $result = graphql::execute_operation(
            execution_context::create('ajax', 'core_lang_strings_nosession'),
            ['lang' => '', 'ids' => ['edit,core']]
        );
        $result = $result->toArray(true);
        $this->assertArrayNotHasKey('data', $result);
        $this->assertSame('Argument "lang" of non-null type "param_lang!" must not be null.', $result['errors'][0]['message']);

        $result = graphql::execute_operation(
            execution_context::create('ajax', 'core_lang_strings_nosession'),
            ['ids' => ['edit,core']]
        );
        $result = $result->toArray(true);
        $this->assertArrayNotHasKey('data', $result);
        $this->assertSame('Variable "$lang" of required type "param_lang!" was not provided.', $result['errors'][0]['message']);
    }

}