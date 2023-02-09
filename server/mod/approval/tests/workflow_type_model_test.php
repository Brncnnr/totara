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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

use core\orm\query\builder;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow_type;
use mod_approval\entity\workflow\workflow_type as workflow_type_entity;
use mod_approval\model\workflow\workflow_version;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\workflow_type
 */
class mod_approval_workflow_type_model_testcase extends \core_phpunit\testcase {

    /**
     * Gets the generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * @covers ::create
     */
    public function test_create(): void {
        $time = time();
        $workflow_type = workflow_type::create('a new workflow type', 'workflow type description');
        $this->assertInstanceOf(workflow_type::class, $workflow_type);
        $this->assertNotEmpty($workflow_type->id);
        $this->assertEquals('a new workflow type', $workflow_type->name);
        $this->assertEquals('workflow type description', $workflow_type->description);
        $this->assertTrue($workflow_type->active);
        $this->assertGreaterThanOrEqual($time, $workflow_type->created);

        // Test unique of a workflow type name
        try {
            $workflow_type = workflow_type::create('a new workflow type', 'workflow type description 2');
            $this->fail("No dml_write_exception thrown");
        } catch (\dml_write_exception $e) {
        }
    }

    /**
     * @covers ::activate
     * @covers ::deactivate
     */
    public function test_toggle(): void {
        $workflow_type = workflow_type::create('a new workflow type');
        $this->assertTrue($workflow_type->active);
        $workflow_type->deactivate();
        $workflow_type = workflow_type::load_by_id($workflow_type->id);
        $this->assertFalse($workflow_type->active);
        $workflow_type->activate();
        $workflow_type = workflow_type::load_by_id($workflow_type->id);
        $this->assertTrue($workflow_type->active);
    }

    /**
     * @covers ::can_deactivate
     */
    public function test_toggle_with_dependencies(): void {
        $workflow_type = workflow_type::create('a new workflow type');
        $form_version = $this->generator()->create_form_and_version();
        $workflow_go = new \mod_approval\testing\workflow_generator_object($workflow_type->id, $form_version->form_id, $form_version->id);
        $workflow_version_entity = $this->generator()->create_workflow_and_version($workflow_go);
        $workflow_version = workflow_version::load_by_entity($workflow_version_entity);
        $workflow = $workflow_version->workflow;
        $this->assertTrue($workflow_type->active);
        try {
            $workflow_type->deactivate();
            $this->fail("No model exception thrown");
        } catch (\mod_approval\exception\model_exception $e) {
            $this->assertEquals('Cannot deactivate object with active dependencies', $e->debuginfo);
        }
        $workflow_version_entity->status = status::DRAFT;
        $workflow_version_entity->save();
        $workflow->deactivate();
        $workflow_type->deactivate();
        $this->assertFalse($workflow_type->active);
    }

    /**
     * @covers ::refresh
     */
    public function test_refresh(): void {
        $workflow_type = workflow_type::create('a new workflow type', 'workflow type description');
        $this->assertEquals('a new workflow type', $workflow_type->name);
        $this->assertEquals('workflow type description', $workflow_type->description);
        builder::table(workflow_type_entity::TABLE)->update(
            [
                'name' => 'the updated workflow type',
                'description' => 'the updated workflow type description',
            ]
        );
        $workflow_type->refresh();
        $this->assertEquals('the updated workflow type', $workflow_type->name);
        $this->assertEquals('the updated workflow type description', $workflow_type->description);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        $workflow_type = workflow_type::create('a new workflow type');
        $this->assertNotEmpty($workflow_type->id);
        $workflow_type->delete();
        $this->assertEmpty($workflow_type->id);
    }
}
