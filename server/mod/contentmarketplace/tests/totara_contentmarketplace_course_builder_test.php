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
 * @package mod_contentmarketplace
 */

use container_course\course;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\course\result;
use totara_contentmarketplace\testing\helper;
use totara_contentmarketplace\testing\generator as marketplace_generator;
use totara_contentmarketplace\testing\mock\create_course_interactor;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_totara_contentmarketplace_course_builder_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_course_as_authenticated_user_but_no_cap_for_course_creator(): void {
        global $CFG;

        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $course_creator_role = helper::get_course_creator_role();
        $context_category = helper::get_default_course_category_context();

        self::assertFalse(has_capability('totara/contentmarketplace:add', $context_category, $user->id));
        role_assign($course_creator_role, $user->id, $context_category->id);
        self::assertTrue(has_capability('totara/contentmarketplace:add', $context_category, $user->id));

        // We will need to prevent the ability to add activity marketplace to the user.
        assign_capability('mod/contentmarketplace:addinstance', CAP_PROHIBIT, $CFG->creatornewroleid, $context_category->id);

        $marketplace_generator = marketplace_generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $course_builder = new course_builder(
            $learning_object,
            $context_category->instanceid,
            new create_course_interactor($user->id)
        );

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));

        $result = $course_builder->create_course_in_transaction();
        self::assertFalse($result->is_successful());
        self::assertTrue($result->is_error());
        self::assertEquals(result::ERROR_ON_MODULE_CREATION, $result->get_code());

        // Nothing should be created
        self::assertEquals(0, $db->count_records('course', ['containertype' => course::get_type()]));

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
    }
}