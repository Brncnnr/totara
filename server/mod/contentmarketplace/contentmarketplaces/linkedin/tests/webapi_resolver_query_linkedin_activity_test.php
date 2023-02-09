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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package contentmarketplaceactivity_linkedin
 */

use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_contentmarketplace\model\content_marketplace as model;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplaceactivity_linkedin_webapi_resolver_query_linkedin_activity_test_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var string
     */
    protected const QUERY_NAME = 'contentmarketplaceactivity_linkedin_linkedin_activity';

    /**
     * @return void
     */
    public function test_content_marketplace_query_with_admin(): void {
        self::setAdminUser();

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);

        $result = $this->resolve_graphql_query(
            self::QUERY_NAME,
            ['cm_id' => $cm->cmid]
        );

        self::assertInstanceOf(model::class, $result);

        self::assertEquals($result->course_id, $cm->course);
        self::assertEquals($result->id, $cm->id);
        self::assertEquals($result->name, $cm->name);
        self::assertEquals($result->completion_condition, $cm->completion_condition);

        $learning_object = $result->get_learning_object();

        self::assertNotEmpty($learning_object->get_id());
        self::assertEquals($result->name, $learning_object->get_name());
    }

    /**
     * @return void
     */
    public function test_content_marketplace_query_with_logged_user(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();

        self::setUser($user);

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $this->resolve_graphql_query(
            self::QUERY_NAME,
            ['cm_id' => $cm->cmid]
        );
    }

    /**
     * @return void
     */
    public function test_content_marketplace_query_with_guest(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        self::setGuestUser();
        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $this->resolve_graphql_query(
            self::QUERY_NAME,
            ['cm_id' => $cm->cmid]
        );
    }

    /**
     * @return void
     */
    public function test_content_marketplace_query_with_enrol_user(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();

        self::setUser($user);

        $generator->enrol_user($user->id, $course->id);
        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);

        $result = $this->resolve_graphql_query(
            self::QUERY_NAME,
            ['cm_id' => $cm->cmid]
        );

        self::assertInstanceOf(model::class, $result);
    }

    /**
     * @return void
     */
    public function test_content_marketplace_query_with_guest_enrol(): void {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);

        self::setGuestUser();

        $enrol = $DB->get_record(
            'enrol',
            ['enrol' => 'guest', 'courseid' => $course->id],
            '*',
            MUST_EXIST
        );

        $plugin = enrol_get_plugin('guest');

        $new_enrol = new stdClass();
        $new_enrol->status = ENROL_INSTANCE_ENABLED;
        $plugin->update_instance($enrol, $new_enrol);

        $result = $this->resolve_graphql_query(
            self::QUERY_NAME,
            ['cm_id' => $cm->cmid]
        );

        self::assertInstanceOf(model::class, $result);
    }

    /**
     * @return void
     */
    public function test_execute_content_marketplace_query_gql_operation(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course(["format" => "singleactivity"]);

        $content_marketplace = $generator->create_module("contentmarketplace", ["course" => $course->id]);

        self::setAdminUser();
        $result = $this->execute_graphql_operation(
            "contentmarketplaceactivity_linkedin_linkedin_activity",
            ["cm_id" => $content_marketplace->cmid]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);

        self::assertIsArray($result->data);
        self::assertArrayHasKey("instance", $result->data);

        $instance_data = $result->data["instance"];
        self::assertIsArray($instance_data);
        self::assertArrayHasKey("module", $instance_data);
        self::assertArrayHasKey("learning_object", $instance_data);

        $module_data = $instance_data["module"];
        self::assertIsArray($module_data);
        self::assertArrayHasKey("course", $module_data);
        self::assertArrayHasKey("url", $module_data["course"]);
        self::assertEquals(course_get_url($course)->out(), $module_data["course"]["url"]);

        self::assertArrayHasKey("course_format", $module_data["course"]);
        $course_format = $module_data["course"]["course_format"];

        self::assertIsArray($course_format);
        self::assertArrayHasKey("has_course_view_page", $course_format);
        self::assertFalse($course_format["has_course_view_page"]);
    }
}