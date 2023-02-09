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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */

use totara_contentmarketplace\interactor\catalog_import_interactor;
use core_phpunit\testcase;
use totara_contentmarketplace\testing\helper;
use totara_tenant\testing\generator as tenant_generator;

/**
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_catalog_import_interactor_testcase extends testcase {
    /**
     * @return void
     */
    public function test_check_actions_for_course_creator(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $interactor = new catalog_import_interactor($user_one->id);
        self::assertFalse($interactor->can_view_catalog_import_page());
        self::assertFalse($interactor->can_add_course());

        $course_category = coursecat::create(['name' => 'Category custom']);
        $context_category = $course_category->get_context();

        self::assertFalse($interactor->can_add_course_to_category($context_category));

        // Assign course creator role to this very user.
        $role_id = helper::get_course_creator_role();
        role_assign($role_id, $user_one->id, $context_category->id);

        self::assertTrue($interactor->can_view_catalog_import_page());
        self::assertTrue($interactor->can_add_course());
        self::assertTrue($interactor->can_add_course_to_category($context_category));

        // Create new category and check that if user should not be able to add course to this
        // very category.
        $new_course_category = coursecat::create(['name' => 'Category custom 2']);
        $new_context_category = $new_course_category->get_context();

        self::assertFalse($interactor->can_add_course_to_category($new_context_category));
    }

    /**
     * @return void
     */
    public function test_check_actions_for_site_manager(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $interactor = new catalog_import_interactor($user_one->id);
        self::assertFalse($interactor->can_view_catalog_import_page());
        self::assertFalse($interactor->can_add_course());

        $category_id = helper::get_default_course_category_id();
        $context_category = context_coursecat::instance($category_id);

        self::assertFalse($interactor->can_add_course_to_category($context_category));
        $context_system = context_system::instance();
        $role_id = helper::get_site_manager_role();

        // Assign site manager to the user one in context system.
        role_assign($role_id, $user_one->id, $context_system->id);

        self::assertTrue($interactor->can_view_catalog_import_page());
        self::assertTrue($interactor->can_add_course());
        self::assertTrue($interactor->can_add_course_to_category($context_category));

        // Create a new course category.
        $new_course_category = coursecat::create(['name' => 'New category']);
        $new_context_category = $new_course_category->get_context();

        self::assertTrue($interactor->can_add_course_to_category($new_context_category));

        // Un assign user from site manager in the context system.
        role_unassign($role_id, $user_one->id, $context_system->id);

        self::assertFalse($interactor->can_view_catalog_import_page());
        self::assertFalse($interactor->can_add_course());
        self::assertFalse($interactor->can_add_course_to_category($context_category));
        self::assertFalse($interactor->can_add_course_to_category($new_context_category));

        // Assign user to site manager in one of the context category.
        role_assign($role_id, $user_one->id, $context_category->id);
        self::assertTrue($interactor->can_view_catalog_import_page());
        self::assertTrue($interactor->can_add_course());
        self::assertTrue($interactor->can_add_course_to_category($context_category));
        self::assertFalse($interactor->can_add_course_to_category($new_context_category));
    }

    /**
     * @return void
     */
    public function test_check_actions_for_tenant_domain_manager(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $interactor = new catalog_import_interactor($user_one->id);
        $category_id = helper::get_default_course_category_id();
        $context_category = context_coursecat::instance($category_id);

        self::assertFalse($interactor->can_view_catalog_import_page());
        self::assertFalse($interactor->can_add_course());
        self::assertFalse($interactor->can_add_course_to_category($context_category));

        $tenant_generator = tenant_generator::instance();
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();

        $role_id = helper::get_tenant_domain_manager_id();
        $context_tenant_category = context_coursecat::instance($tenant_one->categoryid);

        role_assign($role_id, $user_one->id, $context_tenant_category->id);
        self::assertTrue($interactor->can_view_catalog_import_page());
        self::assertTrue($interactor->can_add_course());
        self::assertTrue($interactor->can_add_course_to_category($context_tenant_category));
        self::assertFalse($interactor->can_add_course_to_category($context_category));
    }

    /**
     * @return void
     */
    public function test_require_view_catalog_page(): void {
        $cap_str = get_string('contentmarketplace:add', 'totara_contentmarketplace');

        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage(
            get_string('nopermissions', 'error', $cap_str)
        );

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $interactor = new catalog_import_interactor($user_one->id);
        $interactor->require_view_catalog_import_page();
    }

    /**
     * @return void
     */
    public function test_require_add_course(): void {
        $cap_str = get_string('contentmarketplace:add', 'totara_contentmarketplace');

        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage(
            get_string('nopermissions', 'error', $cap_str)
        );

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $interactor = new catalog_import_interactor($user_one->id);
        $interactor->require_add_course();
    }

    /**
     * @return void
     */
    public function test_require_add_course_to_category(): void {
        $category_id = helper::get_default_course_category_id();
        $context_category = context_coursecat::instance($category_id);

        $cap_str = get_string('contentmarketplace:add', 'totara_contentmarketplace');

        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage(get_string('nopermissions', 'error', $cap_str));

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $interactor = new catalog_import_interactor($user_one->id);
        $interactor->require_add_course_to_category($context_category);
    }
}