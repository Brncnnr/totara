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
 * @package totara_contentmarketplace
 */

use container_course\course;
use totara_contentmarketplace\interactor\catalog_import_interactor;
use core\entity\enrol;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\course\result;
use totara_contentmarketplace\testing\mock\create_course_interactor;
use totara_contentmarketplace\testing\mock\learning_object;
use totara_contentmarketplace\testing\generator;
use totara_contentmarketplace\testing\helper;

/**
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_course_builder_testcase extends testcase {

    /**
     * @return void
     */
    public function test_get_shortname(): void {
        $generator = self::getDataGenerator();
        $course_a = $generator->create_course(['shortname' => 'Content Marketplace Course A']);

        $ref_class = new ReflectionClass(course_builder::class);
        $method = $ref_class->getMethod('get_short_name');
        $method->setAccessible(true);

        $short_name = $method->invokeArgs(null, ['Content Marketplace Course A']);
        self::assertNotEquals($course_a->shortname, $short_name);
        self::assertEquals('Content Marketplace Course A (1)', $short_name);

        $course_a_1 = $generator->create_course(['shortname' => 'Content Marketplace Course A (1)']);
        $short_name = $method->invokeArgs(null, ['Content Marketplace Course A']);
        self::assertNotEquals($course_a_1->shortname, $short_name);
        self::assertEquals('Content Marketplace Course A (2)', $short_name);

        $short_name = $method->invokeArgs(null, ['Content Marketplace Course B']);
        self::assertEquals('Content Marketplace Course B', $short_name);
    }

    /**
     * @return void
     */
    public function test_create_course_as_admin(): void {
        $generator = generator::instance();
        $learning_object = $generator->create_learning_object('contentmarketplace_linkedin');

        $db = builder::get_db();
        $admin = get_admin();

        $category_id = $db->get_field('course_categories', 'id', ['issystem' => 0], MUST_EXIST);
        $course_builder = new course_builder(
            $learning_object,
            $category_id,
            new create_course_interactor($admin->id)
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
    public function test_create_course_as_authenticated_user_without_permission(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $db = builder::get_db();
        $category_id = $db->get_field('course_categories', 'id', ['issystem' => 0], MUST_EXIST);

        $course_builder = new course_builder(
            new learning_object(),
            $category_id,
            new create_course_interactor($user->id)
        );

        $result = $course_builder->create_course();
        self::assertFalse($result->is_successful());
        self::assertTrue($result->is_error());
        self::assertEquals(result::ERROR_ON_COURSE_CREATION, $result->get_code());

        self::assertNull($result->get_course_id());

        // There should only be 1 course record, which is the system.
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));

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
    public function test_create_course_as_authenticated_user_with_permission(): void {
        global $CFG;
        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $role_id = helper::get_authenticated_user_role();
        $category_id = helper::get_default_course_category_id();
        $context_category = context_coursecat::instance($category_id);

        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category, $user->id));

        assign_capability('totara/contentmarketplace:add', CAP_ALLOW, $role_id, $context_category->id);
        self::assertTrue(has_capability('totara/contentmarketplace:add', $context_category, $user->id));

        $marketplace_generator = generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $course_builder = new course_builder(
            $learning_object,
            $category_id,
            new create_course_interactor($user->id)
        );

        $result = $course_builder->create_course();
        self::assertFalse($result->is_error());
        self::assertTrue($result->is_successful());
        self::assertNull($result->get_message());
        self::assertNull($result->get_exception());

        $course_id = $result->get_course_id();
        $db = builder::get_db();

        self::assertNotNull($course_id);
        self::assertTrue($db->record_exists('course', ['id' => $course_id]));

        // Authenticated user should be enrolled to the coursel.
        self::assertTrue(
            $db->record_exists_sql(
                '
                    SELECT 1 FROM "ttr_user_enrolments" ue
                    INNER JOIN "ttr_enrol" e ON ue.enrolid = e.id
                    WHERE e.courseid = :course_id AND ue.userid = :user_id
                ',
                [
                    'course_id' => $course_id,
                    'user_id' => $user->id
                ]
            )
        );

        // Check the role of user.
        $context_course = context_course::instance($course_id);
        $role_assignments = get_users_roles($context_course, [$user->id], false);

        self::assertNotEmpty($role_assignments);
        self::assertArrayHasKey($user->id, $role_assignments);

        $user_role_assignments = $role_assignments[$user->id];
        self::assertNotEmpty($user_role_assignments);
        self::assertCount(1, $user_role_assignments);

        $role_assignment = reset($user_role_assignments);
        self::assertIsObject($role_assignment);
        self::assertObjectHasAttribute('roleid', $role_assignment);

        // The role assignment should be using creatornewroleid.
        self::assertEquals($CFG->creatornewroleid, $role_assignment->roleid);

        // The context id should be the course context id.
        self::assertObjectHasAttribute('contextid', $role_assignment);
        self::assertEquals($context_course->id, $role_assignment->contextid);
    }

    /**
     * @return void
     */
    public function test_create_course_with_non_existing_learning_object_with_transaction(): void {
        $admin = get_admin();
        $learning_object = new learning_object(42, 'en', 'Name');

        $course_builder = new course_builder(
            $learning_object,
            helper::get_default_course_category_id(),
            new create_course_interactor($admin->id)
        );

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));
        $result = $course_builder->create_course_in_transaction();

        // The creation should not leaving any trailing record if the error yield
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));

        self::assertFalse($result->is_successful());
        self::assertTrue($result->is_error());
        self::assertNull($result->get_course_id());
        self::assertEquals(result::ERROR_ON_MODULE_CREATION, $result->get_code());

        $message = $result->get_message();
        self::assertNotNull($message);
        self::assertEquals(
            get_string('error:cannot_add_module_to_course', 'totara_contentmarketplace', 'Name'),
            $message
        );

        // Check for the exception.
        $exception = $result->get_exception();
        self::assertNotNull($exception);
        self::assertInstanceOf(coding_exception::class, $exception);
        self::assertStringContainsString(
            "Incorrect function 'contentmarketplace_add_instance'",
            $exception->getMessage()
        );
    }

    /**
     * @return void
     */
    public function test_create_course_with_non_existing_learning_object_without_transaction(): void {
        $admin = get_admin();
        $learning_object = new learning_object(42, 'en', 'Name');

        $course_builder = new course_builder(
            $learning_object,
            helper::get_default_course_category_id(),
            new create_course_interactor($admin->id)
        );

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));
        $result = $course_builder->create_course();

        // The creation should not leaving any trailing record if the error yield
        self::assertEquals(1, $db->count_records('course', ['containertype' => course::get_type()]));

        // The course record should had been created at this point, and not being rollback due to the
        // behaviour of the function we are invoking.
        self::assertTrue(
            $db->record_exists('course', ['containertype' => course::get_type(), 'fullname' => 'Name'])
        );

        self::assertFalse($result->is_successful());
        self::assertTrue($result->is_error());
        self::assertNull($result->get_course_id());
        self::assertEquals(result::ERROR_ON_MODULE_CREATION, $result->get_code());

        $message = $result->get_message();
        self::assertNotNull($message);
        self::assertEquals(
            get_string('error:cannot_add_module_to_course', 'totara_contentmarketplace', 'Name'),
            $message
        );

        // Check for the exception.
        $exception = $result->get_exception();
        self::assertNotNull($exception);
        self::assertInstanceOf(coding_exception::class, $exception);
        self::assertStringContainsString(
            "Incorrect function 'contentmarketplace_add_instance'",
            $exception->getMessage()
        );
    }

    /**
     * @return void
     */
    public function test_create_course_without_creator_new_role_id_as_a_course_creator(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $course_creator_role = helper::get_course_creator_role();
        $context_category = helper::get_default_course_category_context();

        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category, $user->id));
        role_assign($course_creator_role, $user->id, $context_category->id);
        self::assertTrue(has_capability('totara/contentmarketplace:add', $context_category, $user->id));

        // Remove the creator new role id, so that we can get the error.
        set_config('creatornewroleid', 0);

        $marketplace_generator = generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $course_builder = new course_builder(
            $learning_object,
            $context_category->instanceid,
            new catalog_import_interactor($user->id)
        );

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));

        $result = $course_builder->create_course_in_transaction();

        self::assertFalse($result->is_successful());
        self::assertTrue($result->is_error());
        self::assertEmpty($result->get_course_id());

        self::assertEquals(result::ERROR_ON_MODULE_CREATION, $result->get_code());
        self::assertNotEquals(result::ERROR_ON_COURSE_CREATION, $result->get_code());

        $message = $result->get_message();
        self::assertNotNull($message);
        self::assertEquals(
            get_string('error:cannot_add_module_to_course', 'totara_contentmarketplace', $learning_object->get_name()),
            $message
        );

        $exception = $result->get_exception();
        self::assertNotNull($exception);
        self::assertInstanceOf(coding_exception::class, $exception);
        self::assertStringContainsString(
            "Cannot add module 'contentmarketplace' to course '{$learning_object->get_name()}'",
            $exception->getMessage()
        );

        // No course should be created after the fail execution.
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));
    }

    /**
     * @return void
     */
    public function test_create_course_with_single_activity(): void {
        $admin = get_admin();

        $marketplace_generator = generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $interactor = new create_course_interactor($admin->id);
        $course_builder = new course_builder(
            $learning_object,
            helper::get_default_course_category_id(),
            $interactor
        );

        $result = $course_builder->create_course_in_transaction();
        self::assertTrue($result->is_successful());
        self::assertFalse($result->is_error());

        self::assertNull($result->get_message());
        self::assertNull($result->get_exception());

        $course_id = $result->get_course_id();
        $db = builder::get_db();

        self::assertTrue($db->record_exists('course', ['id' => $course_id]));

        // we only default 1 section created when create a course.
        self::assertEquals(
            1,
            $db->count_records('course_sections', ['course' => $course_id])
        );

        $format_option = $db->get_record(
            'course_format_options',
            [
                'courseid' => $course_id,
                'format' => 'singleactivity'
            ],
            '*',
            MUST_EXIST
        );

        self::assertObjectHasAttribute('name', $format_option);
        self::assertEquals('activitytype', $format_option->name);

        self::assertObjectHasAttribute('value', $format_option);
        self::assertEquals('contentmarketplace', $format_option->value);
    }

    /**
     * @return void
     */
    public function test_create_course_with_topics_activity(): void {
        $admin = get_admin();

        $marketplace_generator = generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $interactor = new create_course_interactor($admin->id);
        $course_builder = new course_builder(
            $learning_object,
            helper::get_default_course_category_id(),
            $interactor
        );

        $course_builder->set_course_format('topics');
        $result = $course_builder->create_course_in_transaction();

        self::assertTrue($result->is_successful());
        self::assertFalse($result->is_error());
        self::assertNull($result->get_message());
        self::assertNull($result->get_exception());

        $course_id = $result->get_course_id();
        $db = builder::get_db();

        self::assertTrue($db->record_exists('course', ['id' => $course_id]));

        // We only default 1 section to be created when create a course.
        // The rest will be created when viewing the course.
        self::assertEquals(
            1,
            $db->count_records('course_sections', ['course' => $course_id])
        );
    }

    /**
     * @return void
     */
    public function test_instantiate_course_builder_from_non_exist_record(): void {
        $this->expectException(dml_missing_record_exception::class);
        $this->expectExceptionMessage('Can not find data record in database.');

        $admin = get_admin();
        $interactor = new create_course_interactor($admin->id);

        course_builder::create_with_learning_object(
            'contentmarketplace_linkedin',
            15,
            $interactor
        );
    }

    /**
     * @return void
     */
    public function test_instantiate_course_builder(): void {
        $admin = get_admin();

        $marketplace_generator = generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $interactor = new create_course_interactor($admin->id);
        $course_builder = course_builder::create_with_learning_object(
            $learning_object::get_marketplace_component(),
            $learning_object->get_id(),
            $interactor
        );

        $result = $course_builder->create_course();
        self::assertTrue($result->is_successful());
        self::assertFalse($result->is_error());
        self::assertNull($result->get_exception());
        self::assertNull($result->get_message());

        $db = builder::get_db();
        $course_id = $result->get_course_id();

        self::assertNotNull($course_id);
        self::assertTrue($db->record_exists('course', ['id' => $course_id]));
    }

    /**
     * @return void
     */
    public function test_create_course_with_completion_criteria(): void {
        $admin = get_admin();
        $marketplace_generator = generator::instance();

        $learning_object = $marketplace_generator->create_learning_object("contentmarketplace_linkedin");
        $interactor = new create_course_interactor($admin->id);

        $course_builder = course_builder::create_with_learning_object(
            $learning_object::get_marketplace_component(),
            $learning_object->get_id(),
            $interactor
        );

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records("course_completion_criteria"));

        $result = $course_builder->create_course();
        self::assertTrue($result->is_successful());

        $course_id = $result->get_course_id();
        self::assertNotNull($course_id);

        // Check that the completion criteria had been created.
        self::assertTrue(
            $db->record_exists(
                "course_completion_criteria",
                [
                    "course" => $course_id,
                    "criteriatype" => COMPLETION_CRITERIA_TYPE_ACTIVITY,
                    "module" => "contentmarketplace"
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_create_course_without_completion_criteria(): void {
        $admin = get_admin();
        $marketplace_generator = generator::instance();

        $learning_object = $marketplace_generator->create_learning_object("contentmarketplace_linkedin");
        $interactor = new create_course_interactor($admin->id);

        $course_builder = course_builder::create_with_learning_object(
            $learning_object::get_marketplace_component(),
            $learning_object->get_id(),
            $interactor
        );

        $course_builder->set_enable_course_completion(false);

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records("course_completion_criteria"));

        $result = $course_builder->create_course();
        self::assertTrue($result->is_successful());

        $course_id = $result->get_course_id();
        self::assertNotNull($course_id);

        // No course criteria get created.
        self::assertEquals(0, $db->count_records("course_completion_criteria"));
    }

    /**
     * @return void
     */
    public function test_create_course_with_mobile_compatible(): void {
        self::setAdminUser();
        set_config('enable', true, 'totara_mobile');
        $table = builder::table('totara_mobile_compatible_courses');
        self::assertEquals(0, $table->count());

        $marketplace_generator = generator::instance();
        $learning_object = $marketplace_generator->create_learning_object("contentmarketplace_linkedin");
        $interactor = new create_course_interactor(get_admin()->id);

        $course_builder = course_builder::create_with_learning_object(
            $learning_object::get_marketplace_component(),
            $learning_object->get_id(),
            $interactor
        );

        $result = $course_builder->create_course();
        self::assertTrue($result->is_successful());
        self::assertEquals(1, $table->count());
        self::assertTrue($table->where('courseid', $result->get_course_id())->exists());
    }
}