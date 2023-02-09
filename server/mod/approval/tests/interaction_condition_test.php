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

use core_phpunit\testcase;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\interaction\condition\interaction_condition;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\interaction\condition\interaction_condition
 */
class mod_approval_interaction_condition_testcase extends testcase {

    /**
     * @covers ::condition_key_field
     * @covers ::condition_data_field
     */
    public function test_create_condition(): void {
        $data = json_encode((object)['comparison' => 'equals', 'value' => 'quux']);

        $instance = new interaction_condition('foo', $data);
        $this->assertEquals('foo', $instance->condition_key_field());
        $this->assertEquals($data, $instance->condition_data_field());
    }

    /**
     * @covers ::is_met
     * @covers ::comparison_equals
     */
    public function test_is_met_equals(): void {
        $data = json_encode((object)['comparison' => 'equals', 'value' => 'quux']);
        $instance = new interaction_condition('foo', $data);

        // Happy path.
        $form_data = form_data::from_json('{"foo":"quux"}');
        $this->assertTrue($instance->is_met($form_data));

        // Not equals.
        $form_data = form_data::from_json('{"foo":"bar"}');
        $this->assertFalse($instance->is_met($form_data));

        // Empty string value.
        $form_data = form_data::from_json('{"foo":""}');
        $this->assertFalse($instance->is_met($form_data));

        // Null value.
        $form_data = form_data::from_json('{"foo":null}');
        $this->assertFalse($instance->is_met($form_data));

        // Field key not present.
        $form_data = form_data::from_json('{"ipsum":"quux"}');
        $this->assertFalse($instance->is_met($form_data));
    }

    /**
     * @covers ::is_met
     * @covers ::comparison_equals
     */
    public function test_is_met_equals_no_value(): void {
        $data = json_encode((object)['comparison' => 'equals']);
        $instance = new interaction_condition('foo', $data);

        // Happy path.
        $form_data = form_data::from_json('{"foo":""}');
        $this->assertTrue($instance->is_met($form_data));

        // Null value.
        $form_data = form_data::from_json('{"foo":null}');
        $this->assertFalse($instance->is_met($form_data));

        // Different value.
        $form_data = form_data::from_json('{"foo":"quux"}');
        $this->assertFalse($instance->is_met($form_data));
    }

    /**
     * @covers ::is_met
     * @covers ::comparison_exists
     */
    public function test_is_met_exists(): void {
        $data = json_encode((object)['comparison' => 'exists']);
        $instance = new interaction_condition('foo', $data);

        // Happy path.
        $form_data = form_data::from_json('{"foo":"quux"}');
        $this->assertTrue($instance->is_met($form_data));

        // Still fine.
        $form_data = form_data::from_json('{"foo":"bar"}');
        $this->assertTrue($instance->is_met($form_data));

        // Empty string value is also fine.
        $form_data = form_data::from_json('{"foo":""}');
        $this->assertTrue($instance->is_met($form_data));

        // Null value.
        $form_data = form_data::from_json('{"foo":null}');
        $this->assertFalse($instance->is_met($form_data));

        // Field key not present.
        $form_data = form_data::from_json('{"ipsum":"quux"}');
        $this->assertFalse($instance->is_met($form_data));
    }

    /**
     * @covers ::is_met
     * @covers ::comparison_not_exists
     */
    public function test_is_met_not_exists(): void {
        $data = json_encode((object)['comparison' => 'not_exists']);
        $instance = new interaction_condition('foo', $data);

        // Happy path.
        $form_data = form_data::from_json('{"bar":"quux"}');
        $this->assertTrue($instance->is_met($form_data));

        // Still fine.
        $form_data = form_data::from_json('{"foo":null}');
        $this->assertTrue($instance->is_met($form_data));

        // Empty string value is a value, exists.
        $form_data = form_data::from_json('{"foo":""}');
        $this->assertFalse($instance->is_met($form_data));

        // Some other value.
        $form_data = form_data::from_json('{"foo":"quux"}');
        $this->assertFalse($instance->is_met($form_data));
    }
}