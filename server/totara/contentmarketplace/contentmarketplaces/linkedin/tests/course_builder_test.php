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
 * @package contentmarketplace_linkedin
 */

use container_course\course;
use core_container\factory;
use totara_contentmarketplace\interactor\catalog_import_interactor;
use contentmarketplace_linkedin\model\learning_object;
use contentmarketplace_linkedin\testing\generator;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\course\result;
use totara_contentmarketplace\testing\helper;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_course_builder_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_course_successfully_as_admin(): void {
        $generator = generator::instance();
        $entity = $generator->create_learning_object('urn:lyndaCourse:252');

        $admin = get_admin();
        $category_id = helper::get_default_course_category_id();
        $course_builder = new course_builder(
            new learning_object($entity),
            $category_id,
            new catalog_import_interactor($admin->id)
        );

        $result = $course_builder->create_course_in_transaction();
        self::assertFalse($result->is_error());
        self::assertTrue($result->is_successful());
        self::assertNotNull($result->get_course_id());
        self::assertNull($result->get_message());
        self::assertNull($result->get_exception());

        $db = builder::get_db();

        // As admin user, the enrolment should not be needed for the admin.
        self::assertFalse(
            $db->record_exists_sql(
                '
                    SELECT 1 FROM "ttr_enrol" e
                    INNER JOIN "ttr_user_enrolments" ue ON e.id = ue.enrolid
                    WHERE e.courseid = :course_id AND ue.userid = :user_id
                ',
                [
                    'course_id' => $result->get_course_id(),
                    'user_id' => $admin->id,
                ]
            )
        );

        // Check if the module exist for course.
        $module_id = $db->get_field('modules', 'id', ['name' => 'contentmarketplace'], MUST_EXIST);
        self::assertTrue(
            $db->record_exists(
                'course_modules',
                [
                    'module' => $module_id,
                    'course' => $result->get_course_id(),
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_create_course_as_authenticated_user_without_permission_and_with_interactor(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $linkedin_generator = generator::instance();
        $entity = $linkedin_generator->create_learning_object('urn:lyndaCourse:252');

        $category_id = helper::get_default_course_category_id();
        $catalog_import_interactor = new catalog_import_interactor($user->id);

        $course_builder = new course_builder(
            new learning_object($entity),
            $category_id,
            $catalog_import_interactor
        );

        $result = $course_builder->create_course();

        self::assertFalse($result->is_successful());
        self::assertTrue($result->is_error());
        self::assertEquals(result::ERROR_ON_COURSE_CREATION, $result->get_code());
        self::assertNotEquals(result::ERROR_ON_MODULE_CREATION, $result->get_code());

        $message = $result->get_message();
        self::assertNotNull($message);

        $category_name = context_coursecat::instance($category_id)->get_context_name(false);
        self::assertEquals(
            get_string('error:cannot_add_course_to_category', 'totara_contentmarketplace', $category_name),
            $message
        );
    }

    /**
     * @return void
     */
    public function test_create_a_course_as_guest_user(): void {
        $generator = generator::instance();
        $entity = $generator->create_learning_object('urn:lyndaCourse:252');

        $guest_user = guest_user();
        $category_id = helper::get_default_course_category_id();

        $course_builder = new course_builder(
            new learning_object($entity),
            $category_id,
            new catalog_import_interactor($guest_user->id)
        );

        $result = $course_builder->create_course_in_transaction();
        self::assertTrue($result->is_error());
        self::assertFalse($result->is_successful());
        self::assertNull($result->get_exception());
        self::assertEquals(result::ERROR_ON_COURSE_CREATION, $result->get_code());
        self::assertNotEquals(result::ERROR_ON_MODULE_CREATION, $result->get_code());

        $message = $result->get_message();
        self::assertNotNull($message);

        $context_category = context_coursecat::instance($category_id);
        self::assertEquals(
            get_string(
                'error:cannot_add_course_to_category',
                'totara_contentmarketplace',
                $context_category->get_context_name(false)
            ),
            $message
        );
    }

    /**
     * @return void
     */
    public function test_create_course_with_image(): void {
        global $CFG;

        $generator = generator::instance();
        $entity = $generator->create_learning_object(
            'urn:li:lyndaCourse:252',
            [
                'primary_image_url' => 'https://example.com/image_one.png'
            ]
        );

        require_once("{$CFG->dirroot}/lib/filelib.php");
        curl::mock_response("This is image");

        $admin = get_admin();
        $course_builder = new course_builder(
            new learning_object($entity),
            helper::get_default_course_category_id(),
            new catalog_import_interactor($admin->id)
        );

        $result = $course_builder->create_course_in_transaction();
        self::assertFalse($result->is_error());
        self::assertTrue($result->is_successful());
        self::assertNotNull($result->get_course_id());
        self::assertNull($result->get_message());
        self::assertNull($result->get_exception());

        $context_course = context_course::instance($result->get_course_id());

        $fs = get_file_storage();
        self::assertTrue(
            $fs->file_exists(
                $context_course->id,
                'course',
                'images',
                0,
                '/',
                'image_one.png'
            )
        );
    }

    /**
     * @return void
     */
    public function test_add_activity_as_guest_user(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        /** @var course $courseModel */
        $courseModel = factory::from_id($course->id);
        $course_section = $generator->create_course_section(['course' => $course->id, 'section' => 0]);
        $linkedin_generator = generator::instance();
        $entity = $linkedin_generator->create_learning_object('urn:lyndaCourse:252');

        $guest_user = guest_user();

        $course_builder = new course_builder(
            new learning_object($entity),
            null,
            new catalog_import_interactor($guest_user->id)
        );

        $result = $course_builder->add_activity_to_course($courseModel, $course_section->section);
        self::assertTrue($result->is_error());
        self::assertFalse($result->is_successful());
        self::assertNull($result->get_exception());
        self::assertEquals(result::ERROR_ON_MODULE_CREATION, $result->get_code());
        self::assertNotEquals(result::ERROR_ON_COURSE_CREATION, $result->get_code());

        $message = $result->get_message();
        self::assertNotNull($message);

        self::assertEquals(
            get_string(
                'error:cannot_add_module_to_course',
                'totara_contentmarketplace',
                $course->fullname
            ),
            $message
        );
    }

    /**
     * @return void
     */
    public function test_add_activity_as_authenticated_user_without_permission_and_with_interactor(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        /** @var course $courseModel */
        $courseModel = factory::from_id($course->id);
        $course_section = $generator->create_course_section(['course' => $course->id, 'section' => 0]);

        $linkedin_generator = generator::instance();
        $entity = $linkedin_generator->create_learning_object('urn:lyndaCourse:252');

        $catalog_import_interactor = new catalog_import_interactor($user->id);

        $course_builder = new course_builder(
            new learning_object($entity),
            null,
            $catalog_import_interactor
        );

        $result = $course_builder->add_activity_to_course($courseModel, $course_section->section);

        self::assertFalse($result->is_successful());
        self::assertTrue($result->is_error());
        self::assertEquals(result::ERROR_ON_MODULE_CREATION, $result->get_code());
        self::assertNotEquals(result::ERROR_ON_COURSE_CREATION, $result->get_code());

        $message = $result->get_message();
        self::assertNotNull($message);

        self::assertEquals(
            get_string(
                'error:cannot_add_module_to_course',
                'totara_contentmarketplace',
                $course->fullname
            ),
            $message
        );
    }

    public function test_add_activity_successfully_as_admin(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        /** @var course $courseModel */
        $courseModel = factory::from_id($course->id);
        $course_section = $generator->create_course_section(['course' => $course->id, 'section' => 0]);
        $linkedin_generator = generator::instance();
        $entity = $linkedin_generator->create_learning_object('urn:lyndaCourse:252');

        $admin = get_admin();
        $course_builder = new course_builder(
            new learning_object($entity),
            null,
            new catalog_import_interactor($admin->id)
        );

        $result = $course_builder->add_activity_to_course($courseModel, $course_section->section);
        self::assertFalse($result->is_error());
        self::assertTrue($result->is_successful());
        self::assertNotNull($result->get_module_id());
        self::assertNull($result->get_message());
        self::assertNull($result->get_exception());

        $db = builder::get_db();

        // Check if the module exist for course.
        $module_id = $db->get_field('modules', 'id', ['name' => 'contentmarketplace'], MUST_EXIST);
        self::assertTrue(
            $db->record_exists(
                'course_modules',
                [
                    'module' => $module_id,
                    'course' => $course->id,
                ]
            )
        );
    }
}