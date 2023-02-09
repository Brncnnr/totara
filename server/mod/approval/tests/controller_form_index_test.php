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

use container_approval\approval as approval_container;
use container_approval\approval as container_approval;
use core\entity\user;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\controllers\form\index;
use mod_approval\exception\access_denied_exception;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\controllers\form\index
 */
class mod_approval_controller_form_index_testcase extends testcase {

    public function test_context(): void {
        $this->setAdminUser();
        self::assertSame(approval_container::get_default_category_context(), (new index())->get_context());
    }

    public function test_action_as_admin(): void {
        $this->setAdminUser();
        $report_view = (new index())->action();
        $this->assertEquals('Manage approval forms', $report_view->get_report_title());
    }

    public function test_action_as_random_user(): void {
        $user = new user($this->getDataGenerator()->create_user()->id);
        $this->setUser($user);
        try {
            (new index())->action();
            $this->fail("Permission check should have failed but didn't");
        } catch (access_denied_exception $exception) {
            $this->assertEquals('Access denied to manage the workflows (Cannot manage approval workflow forms)', $exception->getMessage());
        }
    }

    public function test_action_as_capable_user(): void {
        $user = new user($this->getDataGenerator()->create_user()->id);
        $this->setUser($user);

        // Check when the user has manage workflows capability.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability('mod/approval:manage_workflows', CAP_ALLOW, $user_role->id, container_approval::get_default_category_context(), true);
        $report_view = (new index())->action();
        $this->assertEquals('Manage approval forms', $report_view->get_report_title());

        // Check the button.
        $additional_data = $report_view->get_additional_data();
        $this->assertNotEmpty($additional_data['add_button']);
    }
}
