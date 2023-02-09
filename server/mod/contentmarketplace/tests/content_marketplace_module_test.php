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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_contentmarketplace
 */

use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\workflow\mod_contentmarketplace\create_marketplace_activity\linkedin;
use core_phpunit\testcase;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_core\hook\mod_add;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_add_module_testcase extends testcase {

    /**
     * Confirm that specific roles have the capability to add content marketplace module.
     *
     * @return void
     */
    public function test_capability() {
        global $DB;

        $gen = $this->getDataGenerator();

        // Create course.
        $course = $gen->create_course();

        // Setup plugin.
        $this->setup_marketplace_enabled();

        // Create an editing teacher and enrol in course.
        $teacher = $gen->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $gen->enrol_user($teacher->id, $course->id, $teacherrole->id);

        // Set current user to teacher.
        $this->setUser($teacher);

        // Confirm that the editing teacher can add the module.
        $modules = container_course\course_helper::get_all_modules();
        $mod_info = get_module_metadata($course, $modules);
        $this->assertArrayHasKey('contentmarketplace', $mod_info);

        // Create normal user.
        $user = $gen->create_user();

        // Set current user.
        $this->setUser($user);

        // Confirm that the user can NOT add the module.
        $modules = container_course\course_helper::get_all_modules();
        $mod_info = get_module_metadata($course, $modules);
        $this->assertArrayNotHasKey('contentmarketplace', $mod_info);
    }

    /**
     * Module availability scenarios.
     * @return array[]
     */
    public function contentmarketplace_module_availability_data_provider(): array {
        return [
            [
                'available' => true,
                'setup' => function (self $testcase) {
                    $testcase->enable_plugin();
                    $testcase->enable_workflow();
                    $testcase->set_config('foo', 'bar');
                    $testcase->task_completed(true);
                },
            ],
            [
                'available' => false,
                'setup' => function (self $testcase) {
                    $testcase->disable_plugin();
                    $testcase->enable_workflow();
                    $testcase->set_config('foo', 'bar');
                    $testcase->task_completed(true);
                },
            ],
            [
                'available' => false,
                'setup' => function (self $testcase) {
                    $testcase->enable_plugin();
                    $testcase->disable_workflow();
                    $testcase->set_config('foo', 'bar');
                    $testcase->task_completed(true);
                },
            ],
            [
                'available' => false,
                'setup' => function (self $testcase) {
                    $testcase->enable_plugin();
                    $testcase->enable_workflow();
                    $testcase->set_config('', '');
                    $testcase->task_completed(true);
                },
            ],
            [
                'available' => false,
                'setup' => function (self $testcase) {
                    $testcase->enable_plugin();
                    $testcase->enable_workflow();
                    $testcase->set_config('foo', 'bar');
                    $testcase->task_completed(false);
                },
            ]
        ];
    }

    /**
     * Test that content marketplace module is available when conditions are met.
     *
     * @dataProvider contentmarketplace_module_availability_data_provider
     *
     * @param bool $available
     *
     * @return void
     */
    public function test_contentmarketplace_module_availability(bool $available, Closure $setup) {
        $this->setAdminUser();

        $setup($this);

        if ($available) {
            // Check that content marketplace is part of modules.
            $modules = container_course\course_helper::get_all_modules();
            $this->assertArrayHasKey('contentmarketplace', $modules);
        } else {
            // Check that content marketplace is NOT part of modules.
            $modules = container_course\course_helper::get_all_modules();
            $this->assertArrayNotHasKey('contentmarketplace', $modules);
        }
    }

    /**
     * Test that adding a module redirects to the workflow.
     *
     * @return void
     */
    public function test_add_module_watcher_redirects() {
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        // Setup plugin.
        $this->setup_marketplace_enabled();

        // Execute the mod_add hook.
        $hook = new mod_add($course, 'contentmarketplace', 0);
        $hook->execute();

        // Confirm that we tried to redirect.
        $this->assertDebuggingCalled(
            "Exception encountered in hook watcher 'mod_contentmarketplace\watcher\mod_add_watcher::redirect_to_workflow': "
            . "Unsupported redirect detected, script execution terminated"
        );
    }

    /**
     * Test can_add_moduleinfo throwing an exception if user does not have access to add module.
     *
     * @return void
     */
    public function test_add_module_watcher_no_access() {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        // Setup plugin.
        $this->setup_marketplace_enabled();

        // Execute the mod_add hook.
        $hook = new mod_add($course, 'contentmarketplace', 0);
        $hook->execute();

        $this->assertDebuggingCalled(
            "Exception encountered in hook watcher 'mod_contentmarketplace\watcher\mod_add_watcher::redirect_to_workflow': "
            . "Sorry, but you do not currently have permissions to do that (Manage activities)"
        );
    }

    /**
     * Test that when a workflow is not enabled and the content marketplace module is being added to a course
     * we should be getting an exception.
     *
     * @return void
     */
    public function test_no_workflow_exception() {
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        // Setup plugin.
        $this->setup_marketplace_disabled();

        // Execute the mod_add hook.
        $hook = new mod_add($course, 'contentmarketplace', 0);
        $hook->execute();

        $this->assertDebuggingCalled(
            "Exception encountered in hook watcher 'mod_contentmarketplace\watcher\mod_add_watcher::redirect_to_workflow': "
            . "Coding error detected, it must be fixed by a programmer: No workflows available for request"
        );
    }

    /**
     * Enable all settings for tests.
     *
     * @return void
     */
    private function setup_marketplace_enabled(): void {
        $this->enable_plugin();
        $this->enable_workflow();
        $this->set_config('foo', 'bar');
        $this->task_completed(true);
    }

    /**
     * Disable all settings needed for tests.
     *
     * @return void
     */
    private function setup_marketplace_disabled(): void {
        $this->disable_plugin();
        $this->disable_workflow();
        $this->set_config('', '');
        $this->task_completed(false);
    }

    /**
     * @return void
     */
    private function enable_plugin(): void {
        contentmarketplace::plugin('linkedin')->enable();
    }

    /**
     * @return void
     */
    private function disable_plugin(): void {
        contentmarketplace::plugin('linkedin')->disable();
    }

    /**
     * @return void
     */
    private function enable_workflow(): void {
        linkedin::instance()->enable();
    }

    /**
     * @return void
     */
    private function disable_workflow(): void {
        linkedin::instance()->disable();
    }

    /**
     * @param string $client_id
     * @param string $client_secret
     *
     * @return void
     */
    private function set_config(string $client_id, string $client_secret): void {
        set_config('client_id', $client_id, 'contentmarketplace_linkedin');
        set_config('client_secret', $client_secret, 'contentmarketplace_linkedin');
    }

    /**
     * @param bool $value
     *
     * @return void
     */
    private function task_completed(bool $value): void {
        config::save_completed_initial_sync_learning_asset($value);
    }

}