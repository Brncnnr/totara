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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\dto\add_activity_result;
use contentmarketplace_linkedin\exception\section_not_found;
use contentmarketplace_linkedin\testing\generator;
use contentmarketplace_linkedin\webapi\resolver\mutation\catalog_import_add_activity;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_contentmarketplace\testing\helper;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_webapi_resolver_mutation_catalog_add_activity_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        $marketplace_linkedin = contentmarketplace::plugin('linkedin');
        $marketplace_linkedin->enable();
    }

    /**
     * @return void
     */
    public function test_add_activity_with_authenticated_user(): void {
        $linkedin_generator = generator::instance();
        $learning_object = $linkedin_generator->create_learning_object('urn:lyndaCourse:252');

        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $course_section = $generator->create_course_section(['course' => $course->id, 'section' => 2]);

        self::setUser($user);

        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage(
            get_string(
                'nopermissions',
                'error',
                get_string('contentmarketplace:add', 'totara_contentmarketplace')
            )
        );

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(catalog_import_add_activity::class),
            [
                'learning_object_id' => $learning_object->id,
                'section_id' => $course_section->id
            ]
        );
    }

    /**
     * @return void
     */
    public function test_add_activity_with_disabled_plugin(): void {
        $marketplace_linkedin = contentmarketplace::plugin('linkedin');
        $marketplace_linkedin->disable();

        $linkedin_generator = generator::instance();
        $learning_object = $linkedin_generator->create_learning_object('urn:lyndaCourse:252');

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $course_section = $generator->create_course_section(['course' => $course->id, 'section' => 2]);

        self::setAdminUser();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(
            get_string(
                'error:disabledmarketplace',
                'totara_contentmarketplace',
                $marketplace_linkedin->displayname
            )
        );

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(catalog_import_add_activity::class),
            [
                'learning_object_id' => $learning_object->id,
                'section_id' => $course_section->id
            ]
        );
    }

    /**
     * @return void
     */
    public function test_add_activity_as_guest_user(): void {
        $linkedin_generator = generator::instance();
        $learning_object = $linkedin_generator->create_learning_object('urn:lyndaCourse:252');
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $course_section = $generator->create_course_section(['course' => $course->id, 'section' => 1]);

        self::setGuestUser();

        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage(
            get_string(
                'nopermissions',
                'error',
                get_string('contentmarketplace:add', 'totara_contentmarketplace')
            )
        );

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(catalog_import_add_activity::class),
            [
                'learning_object_id' => $learning_object->id,
                'section_id' => $course_section->id
            ]
        );
    }

    /**
     * @return void
     */
    public function test_add_activity_with_invalid_section_id(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();
        $course = $generator->create_course();
        $course_context = context_course::instance($course->id);
        $course_section = $generator->create_course_section(['course' => $course, 'section' => 0]);

        assign_capability(
            'mod/contentmarketplace:addinstance',
            CAP_ALLOW,
            helper::get_authenticated_user_role(),
            $course_context->id
        );

        // Create learning object.
        $linkedin_generator = generator::instance();
        $learning_object = $linkedin_generator->create_learning_object('urn:lyndaCourse:469');

        self::setUser($user_one->id);
        self::expectException(section_not_found::class);

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(catalog_import_add_activity::class),
            [
                'learning_object_id' => $learning_object->id,
                'section_id' => $course_section->id + 200000
            ]
        );
    }

    /**
     * @return void
     */
    public function test_add_activity_with_authenticated_user_that_has_permission(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();
        $course = $generator->create_course();
        $course_context = context_course::instance($course->id);
        $course_section = $generator->create_course_section(['course' => $course, 'section' => 0]);

        self::assertFalse(has_capability('mod/contentmarketplace:addinstance', $course_context, $user_one->id));

        // assign capability for the user, within this very course context.
        assign_capability(
            'mod/contentmarketplace:addinstance',
            CAP_ALLOW,
            helper::get_authenticated_user_role(),
            $course_context->id
        );

        self::assertTrue(has_capability('mod/contentmarketplace:addinstance', $course_context, $user_one->id));

        // Create learning object.
        $linkedin_generator = generator::instance();
        $learning_object = $linkedin_generator->create_learning_object('urn:lyndaCourse:469');

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records('course_modules', ['course' => $course->id]));

        self::setUser($user_one->id);

        /** @var add_activity_result $result */
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(catalog_import_add_activity::class),
            [
                'learning_object_id' => $learning_object->id,
                'section_id' => $course_section->id
            ]
        );

        self::assertInstanceOf(add_activity_result::class, $result);
        self::assertTrue($result->is_successful());
        self::assertEquals(
            get_string('content_creation_success_add_activity', 'contentmarketplace_linkedin'),
            $result->get_message()
        );

        $redirect_url = $result->get_redirect_url();
        self::assertNotNull($redirect_url);
        self::assertEquals(
            new moodle_url('/course/view.php', ['id' => $course->id]),
            $redirect_url
        );

        self::assertEquals(1, $db->count_records('course_modules', ['course' => $course->id]));
    }
}