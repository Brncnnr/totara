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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use mod_approval\controllers\form\delete;
use container_approval\approval as container;
use core_phpunit\testcase;
use core\orm\query\builder;
use mod_approval\interactor\category_interactor;
use mod_approval\model\form\form;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow_type;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\generator;
use mod_approval\testing\workflow_generator_object;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\controllers\form\delete
 */
class mod_approval_controller_form_delete_testcase extends testcase {

    /**
     * Gets the workflow generator instance
     *
     * @return generator
     */
    protected function generator(): generator {
        return generator::instance();
    }

    /**
     * Generate a form.
     *
     * @return form
     */
    private function generate_data(): form {
        $form_version = $this->generator()->create_form_and_version();
        return form::load_by_entity($form_version->form);
    }

    public function test_context(): void {
        $form = $this->generate_data();
        $this->setAdminUser();
        $_POST['id'] = $form->id;
        self::assertSame(container::get_default_category_context(), (new delete())->get_context());
    }

    public function test_invalid_param(): void {
        $form = $this->generate_data();
        $this->setAdminUser();
        $_POST['id'] = 9999999;
        $this->expectException(moodle_exception::class);
        (new delete())->process();
    }

    public function test_access_no_capability() {
        $generator = $this->getDataGenerator();
        $form = $this->generate_data();
        $user = $generator->create_user();
        $this->setUser($user);
        $_POST['id'] = $form->id;
        $this->expectException(moodle_exception::class);
        (new delete())->process();
    }

    public function test_access_can_manage_workflows() {
        $form = $this->generate_data();

        $user = new user($this->getDataGenerator()->create_user()->id);
        $this->setUser($user);

        // Check when the user has manage workflows capability.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability('mod/approval:manage_workflows', CAP_ALLOW, $user_role->id, container::get_default_category_context(), true);

        $_POST['id'] = $form->id;
        $interactor = new category_interactor(container::get_default_category_context(), $user->id);
        $this->assertTrue($interactor->can_manage_workflows());
        try {
            (new delete())->action();
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/mod/approval/form/index.php', $ex->link);
        }

        // Unassign the capability
        unassign_capability('mod/approval:manage_workflows', $user_role->id);
        $this->assertFalse($interactor->can_manage_workflows());
        $this->expectException(moodle_exception::class);
        (new delete())->action();
    }

    public function test_delete_with_form_in_use() {
        $workflow_type = workflow_type::create('a new workflow type');
        $form_version = $this->generator()->create_form_and_version();
        $form = form::load_by_entity($form_version->form);
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_version->form_id, $form_version->id);
        $workflow_version_entity = $this->generator()->create_workflow_and_version($workflow_go);
        $workflow_version = workflow_version::load_by_entity($workflow_version_entity);
        $workflow = $workflow_version->workflow;
        $workflow_version_entity->status = status::DRAFT;
        $workflow_version_entity->save();
        $this->setAdminUser();
        $_POST['id'] = $form->id;
        try {
            (new delete())->process();
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/mod/approval/form/index.php', $ex->link);
            $form_remains = form::load_by_id($form->id);
            $this->assertTrue($form_remains->active);
        }
    }

    public function test_delete_modal() {
        $form = $this->generate_data();
        $this->setAdminUser();
        $_POST['id'] = $form->id;
        ob_start();
        (new delete())->process();
        $output = ob_get_clean();
        $this->assertStringContainsString('Are you sure you want to delete', $output);
    }

    public function test_delete() {
        $form = $this->generate_data();
        $this->setAdminUser();
        $_POST['id'] = $form->id;
        $_POST['confirm'] = true;
        $_GET['sesskey'] = sesskey();
        try {
            (new delete())->process();
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/mod/approval/form/index.php', $ex->link);
            try {
                $form_remains = form::load_by_id($form->id);
                $this->fail('Expected record not found exception from deleted form.');
            } catch (\core\orm\query\exceptions\record_not_found_exception $rnf) {
                // All good.
            }
        }
    }
}