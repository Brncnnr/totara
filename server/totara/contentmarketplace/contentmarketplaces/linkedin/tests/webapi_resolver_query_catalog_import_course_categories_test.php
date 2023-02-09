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

use core_phpunit\testcase;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_contentmarketplace\testing\helper;

/**
 * @covers \contentmarketplace_linkedin\webapi\resolver\query\catalog_import_course_categories
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_webapi_resolver_query_catalog_import_course_categories_testcase extends testcase {

    use webapi_phpunit_helper;

    private const QUERY = 'contentmarketplace_linkedin_catalog_import_course_categories';

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $plugin = contentmarketplace::plugin('linkedin');
        $plugin->enable();
    }

    /**
     * @return void
     */
    public function test_catalog_import_course_categories_with_plugin_disabled(): void {
        $plugin = contentmarketplace::plugin('linkedin');
        $plugin->disable();

        $this->setAdminUser();
        
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('LinkedIn Learning content marketplace disabled');
        $this->resolve_graphql_query(self::QUERY);
    }

    /**
     * @return void
     */
    public function test_catalog_import_course_categories_with_authenticated_user(): void {
        $gen = self::getDataGenerator();
        $user_one = $gen->create_user();

        $this->setUser($user_one);
        self::expectException(required_capability_exception::class);
        self::expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Add content marketplace)');
        $this->resolve_graphql_query(self::QUERY);
    }

    /**
     * @return void
     */
    public function test_catalog_import_course_categories_with_admin(): void {
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $cc = $gen->create_category();
        $child_cc_1 = $gen->create_category(['parent' => $cc->id]);
        $child_cc_2 = $gen->create_category(['parent' => $cc->id]);

        $categories = $this->resolve_graphql_query(self::QUERY);
        self::assertIsArray($categories);
        self::assertCount(4, $categories);

        /** @var coursecat $cc_1 */
        $cc_1 = array_shift($categories);
        self::assertIsObject($cc_1);
        self::assertEquals('Miscellaneous', $cc_1->name);
        $cc_2 = array_shift($categories);
        self::assertEquals($cc->id, $cc_2->id);
        self::assertEquals($cc->name, $cc_2->name);
        $cc_3 = array_shift($categories);
        self::assertEquals($child_cc_1->id, $cc_3->id);
        self::assertEquals($cc_2->name . ' / ' . $child_cc_1->name, $cc_3->name);
        $cc_4 = array_shift($categories);
        self::assertEquals($child_cc_2->id, $cc_4->id);
        self::assertEquals($cc_2->name . ' / ' . $child_cc_2->name, $cc_4->name);
    }

    /**
     * @return void
     */
    public function test_catalog_import_course_categories_with_course_creator(): void {
        $gen = self::getDataGenerator();
        $user_one = $gen->create_user();
        $cc = $gen->create_category();

        $role_id = helper::get_course_creator_role();
        role_assign($role_id, $user_one->id, $cc->get_context()->id);

        $this->setUser($user_one);
        $categories = $this->resolve_graphql_query(self::QUERY);
        self::assertIsArray($categories);
        self::assertCount(1, $categories);
        self::assertEquals($cc->id, $categories[0]->id);
        self::assertEquals($cc->name, $categories[0]->name);
    }
}