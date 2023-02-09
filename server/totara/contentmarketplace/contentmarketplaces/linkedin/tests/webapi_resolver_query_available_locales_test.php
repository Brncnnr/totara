<?php
/**
 * This file is part of Totara Core
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
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\entity\learning_object;
use contentmarketplace_linkedin\webapi\resolver\query\available_locales;
use core\orm\query\builder;
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_webapi\phpunit\webapi_phpunit_helper;
use contentmarketplace_linkedin\dto\locale;
use totara_contentmarketplace\testing\helper;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_webapi_resolver_query_available_locales_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        $plugin = contentmarketplace::plugin("linkedin");
        $plugin->enable();
    }

    /**
     * @return void
     */
    public function test_get_available_locales_from_empty_learning_asset(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(learning_object::TABLE));

        self::setAdminUser();
        self::assertEmpty(
            $this->resolve_graphql_query(
                $this->get_graphql_name(available_locales::class)
            )
        );
    }

    /**
     * Create two locales from three learning assets. This is to test the distinct
     * selection from query builder.
     * @return void
     */
    public function test_get_available_locales_from_learning_asset(): void {
        $generator = generator::instance();
        $generator->create_learning_object("urn:lyndaCourse:1", ["locale_language" => "en", "locale_country" => "US"]);
        $generator->create_learning_object("urn:lyndaCourse:2", ["locale_language" => "en", "locale_country" => "US"]);
        $generator->create_learning_object("urn:lyndaCourse:3", ["locale_language" => "de", "locale_country" => "DE"]);

        $db = builder::get_db();
        self::assertEquals(3, $db->count_records(learning_object::TABLE));

        self::setAdminUser();
        $locales = $this->resolve_graphql_query(
            $this->get_graphql_name(available_locales::class)
        );

        self::assertIsArray($locales);
        self::assertCount(2, $locales);

        /** @var locale $first_locale */
        $first_locale = reset($locales);

        /** @var locale $last_locale */
        $last_locale = end($locales);

        self::assertInstanceOf(locale::class, $first_locale);
        self::assertInstanceOf(locale::class, $last_locale);

        self::assertEquals("de", $first_locale->get_lang());
        self::assertEquals("DE", $first_locale->get_country());

        self::assertEquals("en", $last_locale->get_lang());
        self::assertEquals("US", $last_locale->get_country());
    }

    /**
     * @return void
     */
    public function test_get_available_by_different_type_user(): void {
        $generator = generator::instance();
        $generator->create_learning_object("urn:lyndaCourse:1", ["locale_language" => "en", "locale_country" => "US"]);
        $generator->create_learning_object("urn:lyndaCourse:2", ["locale_language" => "en", "locale_country" => "US"]);
        $generator->create_learning_object("urn:lyndaCourse:3", ["locale_language" => "de", "locale_country" => "DE"]);

        // Test with admin user
        self::setAdminUser();

        $locales = $this->resolve_graphql_query(
            $this->get_graphql_name(available_locales::class)
        );

        self::assertIsArray($locales);
        self::assertCount(2, $locales);

        // Test with course creator
        $gen = self::getDataGenerator();
        $user = $gen->create_user();
        $course_category = coursecat::create(['name' => 'Category custom']);
        $context_category = $course_category->get_context();
        $role_id = helper::get_course_creator_role();
        role_assign($role_id, $user->id, $context_category->id);
        self::setUser($user);
        $locales = $this->resolve_graphql_query(
            $this->get_graphql_name(available_locales::class)
        );

        self::assertIsArray($locales);
        self::assertCount(2, $locales);

        // Test with student role
        $user = $gen->create_user();
        $student_id = helper::get_student_role();
        role_assign($student_id, $user->id, context_system::instance());
        assign_capability('totara/contentmarketplace:add', CAP_ALLOW, $student_id, context_system::instance());
        self::setUser($user);
        $locales = $this->resolve_graphql_query(
            $this->get_graphql_name(available_locales::class)
        );

        self::assertIsArray($locales);
        self::assertCount(2, $locales);

        // Test with authenticated user
        $user = $gen->create_user();
        self::setUser($user);

        self::expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Add content marketplace)');
        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_query(
            $this->get_graphql_name(available_locales::class)
        );
    }

}