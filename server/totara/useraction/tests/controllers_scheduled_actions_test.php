<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Simon Chester <simon.chester@totara.com>
 * @package totara_useraction
 */

use core_phpunit\testcase;
use totara_useraction\controllers\add_scheduled_action;
use totara_useraction\controllers\edit_scheduled_action;
use totara_useraction\controllers\history;
use totara_useraction\controllers\scheduled_actions;

/**
 * @group totara_useraction
 */
class totara_useraction_controllers_scheduled_actions_testcase extends testcase {
    /**
     * @var admin_root|null
     */
    protected ?admin_root $admin_menu;

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
    }

    /**
     * Data provider for the test_controller method.
     *
     * @return array[]
     */
    public function controllers_provider(): array {
        return [
            // Class, With Rule
            [scheduled_actions::class, false],
            [add_scheduled_action::class, false],
            [edit_scheduled_action::class, true],
            [history::class, true],
            [history::class, false],
        ];
    }

    /**
     * @return array
     */
    public function controllers_with_rules(): array {
        return [
            // Class
            [edit_scheduled_action::class],
            [history::class],
        ];
    }

    /**
     * Test the controllers all load with context & capability checks.
     *
     * @param string $controller_class
     * @param bool $with_rule
     * @return void
     * @dataProvider controllers_provider
     */
    public function test_controller(string $controller_class, bool $with_rule): void {
        if ($with_rule) {
            /** @var \totara_useraction\testing\generator $generator */
            $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');
            $rule = $generator->create_scheduled_rule();
            $_POST['id'] = $rule->get_id();
            $_GET['rule_id'] = $rule->get_id();
            $_GET['id'] = $rule->get_id();
        }
        $controller = new $controller_class();

        // Confirm the context is as expected
        $this->setAdminUser();
        self::assertSame(context_system::instance(), $controller->get_context());

        // No exceptions thrown
        $this->setAdminUser();
        ob_start();
        $controller->process();
        ob_end_clean();

        // Expect the exception
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->expectException(moodle_exception::class);
        $controller->process();
    }

    /**
     * Test the controllers all fail to load with an invalid ID
     *
     * @param string $controller_class
     * @return void
     * @dataProvider controllers_with_rules
     */
    public function test_controller_with_invalid_id(string $controller_class): void {
        $controller = new $controller_class();

        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');
        $rule = $generator->create_scheduled_rule();

        // Set the /wrong/ rule id
        $_POST['id'] = '-1';
        $_GET['rule_id'] = '-1';

        // Expect the exception
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->expectException(moodle_exception::class);
        $controller->process();
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        $this->setAdminUser();
        $this->admin_menu = admin_get_root(true, false);

        if (!$this->admin_menu->locate('totara_useraction_scheduled_actions')) {
            $this->admin_menu->add(
                'root',
                new admin_category(
                    'test_admin_category',
                    'test admin category'
                )
            );
            $this->admin_menu->add(
                'test_admin_category',
                new admin_externalpage(
                    'totara_useraction_scheduled_actions',
                    'test title',
                    "/totara/useraction/scheduled_actions.php",
                    ['totara/useraction:manage_actions']
                )
            );
        }
        parent::setUp();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();

        // We cannot completely reset the menu as it is a singleton
        // but at least we can purge/reset its content
        $this->admin_menu->purge_children(false);
        $this->admin_menu = null;

        if (!empty($_POST['id'])) {
            unset($_POST['id']);
        }
    }
}
