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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use container_approval\approval;
use core\collection;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_core\advanced_feature;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\settings\settings
 */
class mod_approval_settings_settings_testcase extends testcase {

    public function test_setup_settings_with_all_capabilities() {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        $user_role = builder::table('role')->where('shortname', 'user')->one();

        // Enable capabilities moodle/site:config & mod/approval:manage_workflows for user role.
        assign_capability(
            'moodle/site:config',
            CAP_ALLOW,
            $user_role->id,
            context_system::instance()->id,
            true
        );
        assign_capability(
            'mod/approval:manage_workflows',
            CAP_ALLOW,
            $user_role->id,
            approval::get_default_category_context()->id,
            true
        );

        $admin_root = admin_get_root(true, false);
        $mod_approval_folder = $this->get_approval_folder_category($admin_root);
        $this->assertGreaterThanOrEqual(3, count($mod_approval_folder->get_children()));

        // Assert approvalworkflows category folder.
        $this->assertEquals('approvalworkflows', $mod_approval_folder->name);
        $this->assertFalse($mod_approval_folder->hidden);

        // Assert settings page.
        $this->assertEquals('manageapprovalworflowtypes', $mod_approval_folder->get_children()[0]->name);
        $this->assertFalse($mod_approval_folder->get_children()[0]->hidden);

        // Assert manage approval forms page.
        $this->assertEquals('manageapprovalforms', $mod_approval_folder->get_children()[1]->name);
        $this->assertFalse($mod_approval_folder->get_children()[1]->hidden);

        // Assert manage approval workflows page.
        $this->assertEquals('manageapprovalworkflows', $mod_approval_folder->get_children()[2]->name);
        $this->assertFalse($mod_approval_folder->get_children()[2]->hidden);
    }

    public function test_setup_settings_with_only_site_config_cap() {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        $user_role = builder::table('role')->where('shortname', 'user')->one();

        // Enable only capabilities moodle/site:config for user role.
        assign_capability(
            'moodle/site:config',
            CAP_ALLOW,
            $user_role->id,
            context_system::instance()->id,
            true
        );

        $admin_root = admin_get_root(true, false);
        $mod_approval_folder = $this->get_approval_folder_category($admin_root);
        $this->assertEquals(0, count($mod_approval_folder->get_children()));

        // Assert approvalworkflows category folder.
        $this->assertEquals('approvalworkflows', $mod_approval_folder->name);
        $this->assertFalse($mod_approval_folder->hidden);

        // Assert manage workflows page isn't available.
        $manage_workflow_page = collection::new($mod_approval_folder->get_children())->find('name', 'manageapprovalworkflows');

        $this->assertNull(
            $manage_workflow_page,
            "Manage approval workflows page should not be available without mod/approval:manage_workflows capability"
        );
    }

    /**
     * @param admin_root $admin_root
     * @return admin_category
     */
    private function get_approval_folder_category(admin_root $admin_root): admin_category {
        /** @var admin_category $mod_approval_folder */
        $mod_approval_folder = collection::new($admin_root->get_children())->find('name', 'approvalworkflows');
        if (is_null($mod_approval_folder)) {
            $this->fail('approvalworkflows not found in modsettings root.');
        }

        return $mod_approval_folder;
    }
}
