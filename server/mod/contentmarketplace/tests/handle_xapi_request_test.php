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

use contentmarketplace_linkedin\entity\user_progress;
use core\entity\course;
use core\entity\user;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_contentmarketplace\completion_constants;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\testing\mock\create_course_interactor;
use totara_oauth2\entity\access_token;
use totara_oauth2\testing\generator as oauth2_generator;
use contentmarketplace_linkedin\testing\generator;
use totara_xapi\controller\receiver_controller;
use totara_xapi\entity\xapi_statement;
use totara_xapi\request\request as xapi_request;
use totara_xapi\response\json_result;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_handle_xapi_request_testcase extends testcase {
    /**
     * @var user|null
     */
    private $user;

    /**
     * @var access_token|null
     */
    private $access_token;

    /**
     * @var int|null
     */
    private $time_now;

    /**
     * @return void
     */
    protected function setUp(): void {
        oauth2_generator::setup_required_configuration();
        $generator = self::getDataGenerator();

        $this->time_now = time();

        $this->user = new user(
            $generator->create_user([
                "firstname" => "Bolobala",
                "lastname" => "Ng"
            ])
        );

        $oauth2_generator = oauth2_generator::instance();
        $this->access_token = $oauth2_generator->create_access_token(
            "client_id",
            ["expires" => $this->time_now + HOURSECS]
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->access_token = null;
        $this->user = null;
        $this->time_now = null;
    }

    /**
     * @return void
     */
    public function test_handle_xapi_for_learning_object_that_exists_in_two_courses(): void {
        $linkedin_generator = generator::instance();
        $learning_object = $linkedin_generator->create_learning_object("urn:lyndaCourse:252");

        // Create a course out of these two learning objects and enroll the user.
        self::setAdminUser();
        $course_builder = course_builder::create_with_learning_object(
            "contentmarketplace_linkedin",
            $learning_object->id,
            new create_course_interactor()
        );

        // Set to on content marketplace criteria.
        $course_builder->set_module_completion_condition(completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE);

        $result_one = $course_builder->create_course();
        $result_two = $course_builder->create_course();

        $db = builder::get_db();

        // First content marketplace instance.
        $instance_id_one = $db->get_field(
            "contentmarketplace",
            "id",
            ["course" => $result_one->get_course_id()],
            MUST_EXIST
        );

        $instance_id_two = $db->get_field(
            "contentmarketplace",
            "id",
            ["course" => $result_two->get_course_id()],
            MUST_EXIST
        );


        // Check that the module instance is created correctly.
        self::assertTrue(
            $db->record_exists(
                "contentmarketplace",
                [
                    "id" => $instance_id_one,
                    "completion_condition" => completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE
                ]
            )
        );

        self::assertTrue(
            $db->record_exists(
                "contentmarketplace",
                [
                    "id" => $instance_id_two,
                    "completion_condition" => completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE
                ]
            )
        );

        [$course_one_record, $cm_one] = get_course_and_cm_from_instance($instance_id_one, "contentmarketplace");
        [$course_two_record, $cm_two] = get_course_and_cm_from_instance($instance_id_two, "contentmarketplace");

        // Enrol user into these courses.
        $generator = self::getDataGenerator();
        $generator->enrol_user($this->user->id, $course_one_record->id);
        $generator->enrol_user($this->user->id, $course_two_record->id);

        // Reset the user out of session.
        self::setUser();

        $course_one_completion = new completion_info($course_one_record);
        $course_two_completion = new completion_info($course_two_record);

        // Check that the user does not have completion record for module yet.
        self::assertFalse(
            $db->record_exists(
                "course_modules_completion",
                [
                    "coursemoduleid" => $cm_one->id,
                    "userid" => $this->user->id,
                    "completionstate" => COMPLETION_COMPLETE
                ]
            )
        );

        self::assertFalse(
            $db->record_exists(
                "course_modules_completion",
                [
                    "coursemoduleid" => $cm_two->id,
                    "userid" => $this->user->id,
                    "completionstate" => COMPLETION_COMPLETE
                ]
            )
        );

        self::assertFalse($course_one_completion->is_course_complete($this->user->id));
        self::assertFalse($course_two_completion->is_course_complete($this->user->id));

        $request = xapi_request::create_from_global(
            ["component" => "contentmarketplace_linkedin"],
            [],
            ["AUTHORIZATION" => "Bearer {$this->access_token}"],
            ["REQUEST_METHOD" => "POST"]
        );

        // Set the xapi request content
        $request->set_content(
            json_encode([
                "actor" => [
                    "mbox" => "mailto:{$this->user->email}",
                    "objectType" => "Agent"
                ],
                "id" => "some-random-id",
                "timestamp" => date(DATE_ISO8601, $this->time_now),
                "verb" => [
                    "display" => ["en-US" => "COMPLETED"],
                    "id" => "http://adlnet.gov/expapi/verbs/completed"
                ],
                "object" => [
                    "definition" => [
                        "type" => "http://adlnet.gov/expapi/activities/course"
                    ],
                    "id" => $learning_object->urn,
                    "objectType" => "Activity"
                ],
                "result" => [
                    "completion" => true,
                    "extensions" => [
                        "https://w3id.org/xapi/cmi5/result/extensions/progress" => "100"
                    ]
                ]
            ])
        );

        self::assertEquals(0, $db->count_records(xapi_statement::TABLE));
        self::assertEquals(0, $db->count_records(user_progress::TABLE, ["user_id" => $this->user->id]));

        $receiver_controller = new receiver_controller($request, $this->time_now);

        /** @var json_result $response_result */
        $response_result = $receiver_controller->action();

        self::assertInstanceOf(json_result::class, $response_result);
        $data = $response_result->get_data();

        self::assertIsArray($data);
        self::assertArrayHasKey("success", $data);
        self::assertTrue($data["success"]);


        // Check that the xapi statement is updated accordingly
        self::assertEquals(1, $db->count_records(xapi_statement::TABLE));
        self::assertEquals(1, $db->count_records(user_progress::TABLE, ["user_id" => $this->user->id]));

        // Get the CM of the course.
        self::assertTrue(
            $db->record_exists(
                "course_modules_completion",
                [
                    "coursemoduleid" => $cm_one->id,
                    "userid" => $this->user->id,
                    "completionstate" => COMPLETION_COMPLETE,
                    'progress' => 100,
                ]
            )
        );

        self::assertTrue(
            $db->record_exists(
                "course_modules_completion",
                [
                    "coursemoduleid" => $cm_two->id,
                    "userid" => $this->user->id,
                    "completionstate" => COMPLETION_COMPLETE,
                    'progress' => 100,
                ]
            )
        );

        // Check that the user is now completed the course.
        self::assertTrue($course_one_completion->is_course_complete($this->user->id));
        self::assertTrue($course_two_completion->is_course_complete($this->user->id));
    }

    /**
     * @return void
     */
    public function test_handle_xapi_for_learning_object_which_not_complete_the_course_because_of_unenrolled(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/completion/criteria/completion_criteria_activity.php");

        $linkedin_generator = generator::instance();
        $learning_object = $linkedin_generator->create_learning_object("urn:lyndaCourse:252");

        $admin_user = get_admin();
        $course_builder = course_builder::create_with_learning_object(
            "contentmarketplace_linkedin",
            $learning_object->id,
            new create_course_interactor($admin_user->id)
        );

        $course_builder->set_module_completion_condition(completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE);
        $result = $course_builder->create_course();

        $db = builder::get_db();

        self::assertTrue($result->is_successful());
        self::assertTrue(
            $db->record_exists("course", ["id" => $result->get_course_id()])
        );

        // Get module instance of the newly created course.
        $module_instance_id = $db->get_field(
            "course_modules",
            "id",
            [
                "course" => $result->get_course_id(),
                "module" => $db->get_field("modules", "id", ["name" => "contentmarketplace"])
            ]
        );

        // Adding course completion criteria.
        $criteria_activity = new completion_criteria_activity();
        $criteria_activity->course = $result->get_course_id();
        $criteria_activity->moduleinstance = $module_instance_id;
        $criteria_activity->module = "contentmarketplace";
        $criteria_activity->criteriatype = COMPLETION_CRITERIA_TYPE_ACTIVITY;
        $criteria_activity->insert();

        $request = xapi_request::create_from_global(
            ["component" => "contentmarketplace_linkedin"],
            [],
            ["Authorization" => "Bearer {$this->access_token}"],
            ["REQUEST_METHOD" => "POST"]
        );

        $request->set_content(
            json_encode([
                "actor" => [
                    "mbox" => "mailto:{$this->user->email}",
                    "objectType" => "Agent"
                ],
                "result" => [
                    "completion" => true,
                    "extensions" => [
                        "https://w3id.org/xapi/cmi5/result/extensions/progress" => "100"
                    ]
                ],
                "id" => "some-random-id",
                "verb" => [
                    "display" => ["en-US" => "COMPLETED"],
                    "id" => "http://adlnet.gov/expapi/verbs/completed"
                ],
                "object" => [
                    "definition" => [
                        "type" => "http://adlnet.gov/expapi/activities/course"
                    ],
                    "id" => $learning_object->urn,
                    "objectType" => "Activity"
                ],
                "timestamp" => date(DATE_ISO8601, $this->time_now)
            ])
        );

        $course = new course($result->get_course_id());
        $completion_info = new completion_info($course->to_record());
        $context_course = context_course::instance($result->get_course_id());

        self::assertFalse($completion_info->is_course_complete($this->user->id));
        self::assertFalse(is_enrolled($context_course, $this->user->id));

        $controller = new receiver_controller($request, $this->time_now);
        $result = $controller->action();

        self::assertInstanceOf(json_result::class, $result);
        $data = $result->get_data();

        self::assertIsArray($data);
        self::assertNotEmpty($data);
        self::assertArrayHasKey("success", $data);
        self::assertTrue($data["success"]);

        // The course should be completed.
        self::assertFalse($completion_info->is_course_complete($this->user->id));
        self::assertFalse(is_enrolled($context_course, $this->user->id));
    }

    /**
     * @return void
     */
    public function test_enrol_user_should_not_mark_completion_of_module(): void {
        $linkedin_generator = generator::instance();
        $learning_object = $linkedin_generator->create_learning_object("urn:lyndaCourse:252");

        $admin_user = get_admin();
        $course_builder = course_builder::create_with_learning_object(
            "contentmarketplace_linkedin",
            $learning_object->id,
            new create_course_interactor($admin_user->id)
        );

        $course_builder->set_module_completion_condition(completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE);
        $result = $course_builder->create_course();

        $db = builder::get_db();

        self::assertTrue($result->is_successful());
        self::assertTrue(
            $db->record_exists("course", ["id" => $result->get_course_id()])
        );

        // Get module instance of the newly created course.
        $module_instance_id = $db->get_field(
            "course_modules",
            "id",
            [
                "course" => $result->get_course_id(),
                "module" => $db->get_field("modules", "id", ["name" => "contentmarketplace"])
            ]
        );

        $generator = self::getDataGenerator();
        $generator->enrol_user($this->user->id, $result->get_course_id());

        self::assertFalse(
            $db->record_exists(
                "course_modules_completion",
                [
                    "coursemoduleid" => $module_instance_id,
                    "userid" => $this->user->id,
                    "completionstate" => COMPLETION_COMPLETE
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_user_incomplete_the_module_with_xapi(): void {
        $linkedin_generator = generator::instance();
        $learning_object = $linkedin_generator->create_learning_object("urn:lyndaCourse:252");

        $admin_user = get_admin();
        $course_builder = course_builder::create_with_learning_object(
            "contentmarketplace_linkedin",
            $learning_object->id,
            new create_course_interactor($admin_user->id)
        );

        $course_builder->set_module_completion_condition(completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE);
        $result = $course_builder->create_course();

        $db = builder::get_db();

        self::assertTrue($result->is_successful());
        self::assertTrue(
            $db->record_exists("course", ["id" => $result->get_course_id()])
        );

        // Get module instance of the newly created course.
        $module_instance_id = $db->get_field(
            "course_modules",
            "id",
            [
                "course" => $result->get_course_id(),
                "module" => $db->get_field("modules", "id", ["name" => "contentmarketplace"])
            ]
        );

        $generator = self::getDataGenerator();
        $generator->enrol_user($this->user->id, $result->get_course_id());

        $request = xapi_request::create_from_global(
            ["component" => "contentmarketplace_linkedin"],
            [],
            ["Authorization" => "Bearer {$this->access_token}"],
            ["REQUEST_METHOD" => "POST"]
        );

        $request->set_content(
            json_encode([
                "actor" => [
                    "mbox" => "mailto:{$this->user->email}",
                    "objectType" => "Agent"
                ],
                "result" => [
                    "completion" => false,
                    "extensions" => [
                        "https://w3id.org/xapi/cmi5/result/extensions/progress" => "39"
                    ]
                ],
                "id" => "some-random-id",
                "verb" => [
                    "display" => ["en-US" => "PROGRESSED"],
                    "id" => "http://adlnet.gov/expapi/verbs/completed"
                ],
                "object" => [
                    "definition" => [
                        "type" => "http://adlnet.gov/expapi/activities/course"
                    ],
                    "id" => $learning_object->urn,
                    "objectType" => "Activity"
                ],
                "timestamp" => date(DATE_ISO8601, $this->time_now)
            ])
        );

        self::assertFalse(
            $db->record_exists(
                "course_modules_completion",
                [
                    "userid" => $this->user->id,
                    "coursemoduleid" => $module_instance_id,
                    "completionstate" => COMPLETION_INCOMPLETE
                ]
            )
        );

        $course = new course($result->get_course_id());
        $completion_info = new completion_info($course->to_record());

        self::assertFalse($completion_info->is_course_complete($this->user->id));

        $controller = new receiver_controller($request, $this->time_now);
        $result = $controller->action();

        self::assertInstanceOf(json_result::class, $result);
        $data = $result->get_data();

        self::assertIsArray($data);
        self::assertNotEmpty($data);
        self::assertArrayHasKey("success", $data);
        self::assertTrue($data["success"]);

        // Since it is not incomplete and the completion record for module is not yet
        // existing. Hence, no record will be populated/updated.
        self::assertTrue(
            $db->record_exists(
                "course_modules_completion",
                [
                    "userid" => $this->user->id,
                    "coursemoduleid" => $module_instance_id,
                    "completionstate" => COMPLETION_INCOMPLETE,
                    "progress" => 39,
                ]
            )
        );

        self::assertFalse(
            $db->record_exists(
                "course_modules_completion",
                [
                    "userid" => $this->user->id,
                    "coursemoduleid" => $module_instance_id,
                    "completionstate" => COMPLETION_COMPLETE
                ]
            )
        );

        // The course should be completed.
        self::assertFalse($completion_info->is_course_complete($this->user->id));
    }
}
