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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\entity\form\form as form_entity;
use mod_approval\entity\form\form_version as form_version_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\plugininfo\approvalform;
use mod_approval\testing\workflow_generator_object;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\form\form
 */
class mod_approval_form_model_testcase extends testcase {
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
     * @covers ::get_versions
     */
    public function test_creation_succeeds(): void {
        $time = time();
        $form = form::create('simple', 'kia ora');
        $this->assertInstanceOf(form::class, $form);
        $this->assertNotEmpty($form->id);
        $this->assertEquals('simple', $form->plugin_name);
        $this->assertEquals('kia ora', $form->title);
        $this->assertTrue($form->active);
        $this->assertGreaterThanOrEqual($time, $form->created);
        $this->assertLessThanOrEqual($form->updated, $form->created);
        $this->assertCount(1, $form->versions);
        $form = form::create('simple', '0');
        $this->assertInstanceOf(form::class, $form);
        $this->assertNotEmpty($form->id);
        $this->assertEquals('simple', $form->plugin_name);
        $this->assertEquals('0', $form->title);
        $this->assertCount(1, $form->versions);
    }

    /**
     * @covers ::create
     */
    public function test_creation_fails_on_disabled_plugin(): void {
        approvalform::disable_plugin('simple');
        try {
            form::create('simple', 'kia ora');
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString("The form plugin 'simple' is unavailable", $ex->getMessage());
        }
    }

    public function data_creation_fails_on_invalid_parameter(): array {
        return [
            'empty title' => ['simple', '', 'title cannot be empty'],
            'empty plugin name' => ['', 'kia ora', "'' form plugin not found"],
            'bogus plugin name' => ['he_who_must_not_exist', 'kia ora', "'he_who_must_not_exist' form plugin not found"],
        ];
    }

    /**
     * @param string $plugin_name
     * @param string $title
     * @param string $exception
     * @dataProvider data_creation_fails_on_invalid_parameter
     * @covers ::create
     */
    public function test_creation_fails_on_invalid_parameter(string $plugin_name, string $title, string $exception): void {
        try {
            form::create($plugin_name, $title);
            $this->fail('exception expected');
        } catch (Throwable $ex) {
            $this->assertStringContainsString($exception, $ex->getMessage());
        }
    }

    /**
     * @covers ::activate
     * @covers ::deactivate
     */
    public function test_toggle(): void {
        $form = form::create('simple', 'kia ora');
        $form_version_entity = new form_version_entity($form->latest_version->id);
        $form_version_entity->status = status::DRAFT;
        $form_version_entity->save();
        $this->assertTrue($form->active);
        $form->deactivate();
        $form = form::load_by_id($form->id);
        $this->assertFalse($form->active);
        $form->activate();
        $form = form::load_by_id($form->id);
        $this->assertTrue($form->active);
    }

    /**
     * @covers ::can_deactivate
     */
    public function test_toggle_with_form_version_dependency(): void {
        $form = form::create('simple', 'kia ora');
        $form->latest_version->activate();
        $this->assertTrue($form->active);
        try {
            $form->deactivate();
            $this->fail("No model exception thrown");
        } catch (model_exception $e) {
            $this->assertEquals('Cannot deactivate object with active dependencies', $e->debuginfo);
        }
        $form_version_entity = new form_version_entity($form->latest_version->id);
        $form_version_entity->status = status::DRAFT;
        $form_version_entity->save();
        $form->deactivate();
        $this->assertFalse($form->active);
    }

    /**
     * @covers ::can_deactivate
     */
    public function test_toggle_with_workflow_dependency(): void {
        $workflow_type = $this->generator()->create_workflow_type('Testing');
        $form = form::create('simple', 'kia ora');
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form->latest_version->id);
        // Set workflow_version status to draft so we can deactivate workflow
        $workflow_go->status = status::DRAFT;
        $workflow_version_entity = $this->generator()->create_workflow_and_version($workflow_go);
        $workflow = workflow::load_by_entity($workflow_version_entity->workflow);
        $form_version_entity = new form_version_entity($form->latest_version->id);
        $form_version_entity->status = status::DRAFT;
        $form_version_entity->save();
        $this->assertTrue($form->active);
        try {
            $form->deactivate();
            $this->fail("No model exception thrown");
        } catch (model_exception $e) {
            $this->assertEquals('Cannot deactivate object with active dependencies', $e->debuginfo);
        }
        $workflow->deactivate();
        $form->deactivate();
        $this->assertFalse($form->active);
    }

    /**
     * @covers ::refresh
     */
    public function test_refresh(): void {
        $form = form::create('simple', 'kia ora');
        $this->assertEquals('kia ora', $form->title);
        builder::table(form_entity::TABLE)->update(['title' => 'kia kaha']);
        $form->refresh();
        $this->assertEquals('kia kaha', $form->title);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        $form = form::create('simple', 'kia ora');
        $form_version_entity = new form_version_entity($form->latest_version->id);
        $form_version_entity->status = status::DRAFT;
        $form_version_entity->save();
        $this->assertNotEmpty($form->id);
        $form->delete();
        $this->assertEmpty($form->id);

        $form = form::create('simple', 'kia ora');
        $this->assertNotEmpty($form->id);
        // Delete fails due to active form_version.
        try {
            $form->delete();
            $this->fail("No model exception thrown");
        } catch (model_exception $e) {
            $this->assertEquals('Only draft objects can be deleted', $e->debuginfo);
        }

        // Try delete with force option.
        $form->delete(true);
        $this->assertEmpty($form->id);
    }

    /**
     * @covers ::get_versions
     */
    public function test_get_versions(): void {
        $form1 = form::create('simple', 'kia ora');
        $form2 = form::create('simple', 'tena koutou');
        form_version::create($form1, '1', '[]');
        form_version::create($form1, '2', '[]');
        form_version::create($form2, '3', '[]');
        // NOTE: form_version::create should probably append itself to form::versions
        $form1->refresh();
        $this->assertCount(3, $form1->versions);
    }
}
