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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\workflow\core_course\coursecreate\contentmarketplace;
use contentmarketplace_linkedin\workflow\totara_contentmarketplace\exploremarketplace\linkedin;
use core_phpunit\testcase;
use totara_contentmarketplace\plugininfo\contentmarketplace as contentmarketplace_plugin;
use totara_contentmarketplace\testing\helper;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_lil_workflow_testcase extends testcase {
    /**
     * @return void
     */
    public function test_coursecreate_workflow_basis(): void {
        $obj = contentmarketplace::instance();
        self::assertTrue($obj->is_enabled());
        self::assertEquals(get_string('add_linkedin_courses', 'contentmarketplace_linkedin'), $obj->get_name());
        self::assertEquals(get_string('add_linkedin_courses_description', 'contentmarketplace_linkedin'), $obj->get_description());
    }

    /**
     * @return void
     */
    public function test_coursecreate_workflow_plugin_disabled(): void {
        $plugin = contentmarketplace_plugin::plugin('linkedin');
        self::assertFalse($plugin->is_enabled());

        $obj = contentmarketplace::instance();

        // Admin can not access
        self::setAdminUser();
        self::assertFalse($obj->can_access());

        // Normal user can not access
        $user = $this->login_as_user();
        self::assertFalse($obj->can_access());

        // Course creator can not access
        $course_category = coursecat::create(['name' => 'Category custom']);
        $context_category = $course_category->get_context();
        $this->assign_role($user->id, $context_category->id);

        self::assertFalse($obj->can_access());
    }

    /**
     * @return void
     */
    public function test_coursecreate_workflow_access_with_siteadmin(): void {
        self::setAdminUser();
        $obj = contentmarketplace::instance();
        self::assertFalse($obj->can_access());

        $plugin = contentmarketplace_plugin::plugin('linkedin');
        $plugin->enable();

        // Category is empty
        self::assertTrue($obj->can_access());

        $course_category = coursecat::create(['name' => 'Category custom']);
        // Category is set
        $obj->set_params(['category' => $course_category->id]);
        self::assertTrue($obj->can_access());
    }

    /**
     * @return void
     */
    public function test_coursecreate_workflow_access_with_course_creator(): void {
        $plugin = contentmarketplace_plugin::plugin('linkedin');
        $plugin->enable();

        $user = $this->login_as_user();

        $obj = contentmarketplace::instance();
        self::assertFalse($obj->can_access());

        $course_category = coursecat::create(['name' => 'Category custom']);
        $context_category = $course_category->get_context();
        $this->assign_role($user->id, $context_category->id);

        $obj->set_params(['category' => $course_category->id]);
        self::assertTrue($obj->can_access());
    }

    /**
     * @return void
     */
    public function test_coursecreate_workflow_access_with_authenticate_user(): void {
        $plugin = contentmarketplace_plugin::plugin('linkedin');
        $plugin->enable();

        $this->login_as_user();

        $obj = contentmarketplace::instance();
        self::assertFalse($obj->can_access());

        // Category is empty
        self::assertFalse($obj->can_access());

        $course_category = coursecat::create(['name' => 'Category custom']);

        // Category is set
        $obj->set_params(['category' => $course_category->id]);
        self::assertFalse($obj->can_access());
    }

    /**
     * @return void
     */
    public function test_exploremarketplace_workflow_basis(): void {
        $obj = linkedin::instance();
        self::assertTrue($obj->is_enabled());
        self::assertEquals(get_string('explore_lil_marketplace', 'contentmarketplace_linkedin'), $obj->get_name());
        self::assertEquals(get_string('explore_lil_marketplace_description', 'contentmarketplace_linkedin'), $obj->get_description());
    }

    /**
     * @return void
     */
    public function test_exploremarketplace_workflow_with_admin(): void {
        $obj = linkedin::instance();
        self::assertTrue($obj->is_enabled());
        self::assertEquals(get_string('explore_lil_marketplace', 'contentmarketplace_linkedin'), $obj->get_name());
        self::assertEquals(get_string('explore_lil_marketplace_description', 'contentmarketplace_linkedin'), $obj->get_description());
    }

    /**
     * @return stdClass
     */
    private function login_as_user(): stdClass {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        self::setUser($user);

        return $user;
    }

    /**
     * @param int $user_id
     * @param int $category_id
     */
    private function assign_role(int $user_id, int $category_id): void {
        $role_id = helper::get_course_creator_role();
        role_assign($role_id, $user_id, $category_id);
    }
}