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
 * @package mod_approval
 */

namespace mod_approval\settings;

use admin_category;
use admin_externalpage;
use admin_root;
use container_approval\approval;
use core\entity\user;
use lang_string;
use mod_approval\controllers\workflow\dashboard;
use mod_approval\controllers\workflow\types\index as manage_workflow_types;
use mod_approval\interactor\category_interactor;
use moodle_url;
use totara_core\advanced_feature;

/**
 * Facade class for mod_approval settings
 *
 * @package mod_approval
 */
class settings {

    public const PARENT_FOLDER_NAME = 'approvalworkflows';

    /**
     * Initialises admin settings for Approval Workflows
     *
     * @param admin_root $admin_root
     */
    public static function init_admin_settings(\admin_root $admin_root) {
        // Check on/off switch.
        if (advanced_feature::is_disabled('approval_workflows')) {
            return;
        }

        // Add parent category.
        $admin_root->add(
            'root',
            new admin_category(self::PARENT_FOLDER_NAME, new lang_string('admin_category_approval_workflows', 'mod_approval')),
        );

        // Check user capability.
        $user = user::logged_in();
        if (!$user) {
            return;
        }
        $default_category_id = approval::get_default_category_id();
        $category_interactor = category_interactor::from_category_id($default_category_id, $user->id);
        if (!$category_interactor->can_manage_workflows()) {
            return;
        }

        // Add settings pages.
        static::add_manage_workflow_types_link($admin_root);
        static::add_manage_approvalforms_link($admin_root);
        static::add_manage_approval_workflow_link($admin_root);
    }

    private static function add_manage_workflow_types_link(\admin_root $admin_root) {
        $workflow_types_page = new admin_externalpage(
            'manageapprovalworflowtypes',
            get_string('manage_approval_workflows_types', 'mod_approval'),
            new moodle_url(manage_workflow_types::URL),
            'mod/approval:manage_workflows',
            false
        );

        $admin_root->add(
            static::PARENT_FOLDER_NAME,
            $workflow_types_page
        );
    }

    private static function add_manage_approvalforms_link(\admin_root $admin_root) {
        $form_plugin_page = new admin_externalpage(
            'manageapprovalforms',
            get_string('manage_approval_forms', 'mod_approval'),
            new moodle_url('/mod/approval/form/index.php'),
            'mod/approval:manage_workflows',
            false
        );

        $admin_root->add(
            static::PARENT_FOLDER_NAME,
            $form_plugin_page
        );
    }

    private static function add_manage_approval_workflow_link(\admin_root $admin_root) {
        $workflow_dashboard_page = new admin_externalpage(
            'manageapprovalworkflows',
            get_string('manage_approval_workflows', 'mod_approval'),
            new moodle_url(dashboard::URL),
            'mod/approval:manage_workflows',
            false
        );

        $admin_root->add(
            static::PARENT_FOLDER_NAME,
            $workflow_dashboard_page
        );
    }
}
