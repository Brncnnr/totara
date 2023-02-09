<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\resource\helper;
use totara_engage\resource\input\definition;

class totara_engage_sanitize_resource_data_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_sanitize_data(): void {
        $definitions = [
            definition::from_parameters('id', ['required-on-add' => true]),
            definition::from_parameters('value', ['required-on-add' => true])
        ];

        $data = [
            'id' => 15,
            'value' => "hello world"
        ];

        $cleaned = helper::sanitize_instance_data($definitions, $data);
        $this->assertEquals($cleaned, $data);
    }

    /**
     * @return void
     */
    public function test_sanitize_with_default_data(): void {
        $definitions = [
            definition::from_parameters('id', ['default' => 20]),
            definition::from_parameters('value', ['required-on-add' => true])
        ];

        $data = [
            'value' => 'hello world'
        ];

        $cleaned = helper::sanitize_instance_data($definitions, $data);
        $this->assertEquals(
            [
                'id' => 20,
                'value' => 'hello world'
            ],
            $cleaned
        );
    }

    /**
     * @return void
     */
    public function test_sanitize_data_failed(): void {
        $definitions = [
            definition::from_parameters('id', ['required-on-add' => true]),
            definition::from_parameters('value', ['required-on-add' => true])
        ];

        $data = ['value' => "Hello world"];

        $this->expectException(coding_exception::class);
        helper::sanitize_instance_data($definitions, $data);
    }

    /**
     * @return void
     */
    public function test_sanitize_data_with_alias(): void {
        $definitions = [
            definition::from_parameters(
                'id',
                [
                    'required-on-add' => true,
                    'alias' => 'xid'
                ]
            ),

            definition::from_parameters('value', ['default' => 'hello world'])
        ];

        $data = ['xid' => 15];
        $cleaned = helper::sanitize_instance_data($definitions, $data);

        $this->assertEquals(
            [
                'id' => 15,
                'value' => "hello world"
            ],
            $cleaned
        );
    }

    /**
     * @return void
     */
    public function test_run_validator(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/engage/tests/fixtures/test_validator.php");

        $validator = new test_validator('hello world');

        $definitions = [
            definition::from_parameters(
                'name',
                [
                    'required-on-add' => true,
                    'validators' => [$validator]
                ]
            )
        ];

        $data = ['name' => "hello world"];
        $cleaned = helper::sanitize_instance_data($definitions, $data);

        $this->assertTrue($validator->is_run());
        $this->assertEquals(['name' => "hello world"], $cleaned);
    }
    
    /**
     * @return void
     */
    public function test_key_and_alias_appearing_in_same_data(): void {
        $definitions = [
            definition::from_parameters(
                'name',
                [
                    'required-on-add' => true,
                    'alias' => 'xx_name'
                ]
            )
        ];

        $data = [
            'xx_name' => 'Bolobala',
            'name' => 'Balabolo'
        ];

        $cleaned = helper::sanitize_instance_data($definitions, $data);
        $this->assertDebuggingCalled();

        // The alias will be removed.
        $this->assertEquals(['name' => 'Balabolo'], $cleaned);
    }

    /**
     * @return void
     */
    public function test_sanitize_data_on_update(): void {
        $definitions = [
            definition::from_parameters(
                'name',
                [
                    'required-on-add' => true,
                    'required-on-update' => false
                ]
            ),
            definition::from_parameters(
                'content',
                [
                    'required-on-update' => false,
                    'required-on-add' => true
                ]
            )
        ];

        $data = [];
        $cleaned = helper::sanitize_instance_data($definitions, $data, helper::SANITIZE_ON_UPDATE);

        $this->assertEquals($data, $cleaned);

        // it might not throw any error on update, but will doo on the adding, because the definition tell the helper
        // to be expecting the key in $data.
        $this->expectException(\coding_exception::class);
        helper::sanitize_instance_data($definitions, $data, helper::SANITIZE_ON_ADD);
    }
}