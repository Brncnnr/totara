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

use mod_approval\controllers\workflow\types\toggle;
use container_approval\approval as container;
use core_phpunit\testcase;
use core\orm\query\builder;
use mod_approval\interactor\category_interactor;
use mod_approval\model\workflow\workflow_type;
use mod_approval\model\workflow\workflow_version;
use mod_approval\model\status;
use mod_approval\testing\generator;
use mod_approval\testing\workflow_generator_object;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\controllers\workflow\types\toggle
 */
class mod_approval_controller_workflow_type_toggle_testcase extends testcase {

    /**
     * Gets the workflow generator instance
     *
     * @return generator
     */
    protected function generator(): generator {
        return generator::instance();
    }

    public function test_context(): void {
        [$workflow, $workflow_type] = $this->generate_data();
        $this->setAdminUser();
        $_POST['id'] = $workflow_type->id;
        self::assertSame(container::get_default_category_context(), (new toggle())->get_context());
    }

    public function test_invalid_param(): void {
        [$workflow, $workflow_type] = $this->generate_data();
        $this->setAdminUser();
        $_POST['id'] = 9999999;
        $this->expectException(moodle_exception::class);
        (new toggle())->process();
    }

    public function test_access_no_capability() {
        $generator = $this->getDataGenerator();
        [$workflow, $workflow_type] = $this->generate_data();
        $user = $generator->create_user();
        $this->setUser($user);
        $_POST['id'] = $workflow_type->id;
        $this->expectException(moodle_exception::class);
        (new toggle())->process();
    }

    public function test_access_can_manage_workflows() {
        $generator = $this->getDataGenerator();
        [$workflow, $workflow_type] = $this->generate_data();
        // Assign site manager role in the system context.
        $site_manager = $generator->create_user();
        $site_manager_role = builder::table('role')->where('shortname', 'manager')->one();
        role_assign($site_manager_role->id, $site_manager->id, context_system::instance());

        $interactor = new category_interactor(container::get_default_category_context(), $site_manager->id);
        $this->assertTrue($interactor->can_manage_workflows());

        unassign_capability('mod/approval:manage_workflows', $site_manager_role->id);
        $this->assertFalse($interactor->can_manage_workflows());
        $this->setUser($site_manager);
        $_POST['id'] = $workflow_type->id;
        $this->expectException(moodle_exception::class);
        (new toggle())->process();
    }

    public function test_workflow_type_active() {
        [$workflow, $workflow_type] = $this->generate_data();
        $this->setAdminUser();
        $_POST['id'] = $workflow_type->id;
        try {
            (new toggle())->process(); // will deactivate it
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/mod/approval/workflow/types/index.php', $ex->link);
            $workflow_type = workflow_type::load_by_id($workflow_type->id);
            $this->assertFalse($workflow_type->active);
        }
    }

    public function test_workflow_type_deactive() {
        [$workflow, $workflow_type] = $this->generate_data();
        $this->setAdminUser();
        $workflow_type->deactivate(true);
        $_POST['id'] = $workflow_type->id;
        try {
            (new toggle())->process(); //should activate it
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/mod/approval/workflow/types/index.php', $ex->link);
            $workflow_type = workflow_type::load_by_id($workflow_type->id);
            $this->assertTrue($workflow_type->active);

        }
    }

    private function generate_data(): array {
        $workflow_type = workflow_type::create('a new workflow type');
        $form_version = $this->generator()->create_form_and_version();
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_version->form_id, $form_version->id);
        $workflow_version_entity = $this->generator()->create_workflow_and_version($workflow_go);
        $workflow_version = workflow_version::load_by_entity($workflow_version_entity);
        $workflow = $workflow_version->workflow;
        $workflow_version_entity->status = status::DRAFT;
        $workflow_version_entity->save();
        return [$workflow, $workflow_type];
    }
}