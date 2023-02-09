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
use core_phpunit\testcase;
use contentmarketplace_linkedin\event\import_course_partial_failure;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_event_import_course_partial_failure_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_event_name(): void {
        self::assertNotEquals(
            "contentmarketplace_linkedin:import course partial failure",
            import_course_partial_failure::get_name()
        );

        self::assertEquals(
            get_string("import_course_partial_failure_title", "contentmarketplace_linkedin"),
            import_course_partial_failure::get_name()
        );
    }

    /**
     * @return void
     */
    public function test_instantiate_from_list_of_learning_object_ids(): void {
        $admin = get_admin();
        $event = import_course_partial_failure::from_list_of_learning_object_ids([42], $admin->id,);

        self::assertEquals($admin->id, $event->userid);
        self::assertNull($event->relateduserid);
        self::assertEquals("c", $event->crud);
        self::assertEquals(import_course_partial_failure::LEVEL_TEACHING, $event->edulevel);
        self::assertEquals(context_system::instance(), $event->get_context());

        self::assertNotEmpty($event->other);
        self::assertIsArray($event->other);
        self::assertArrayHasKey("learning_object_ids", $event->other);
        self::assertIsArray($event->other["learning_object_ids"]);
        self::assertEquals([42], $event->other["learning_object_ids"]);

        self::assertEquals(
            sprintf(
                "Several Linkedin Learning learning items (%s) were failed to import by user %d",
                "42",
                $admin->id
            ),
            $event->get_description()
        );
    }

    /**
     * @return void
     */
    public function test_instantiate_from_session_actor_id(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        self::setUser($user);
        $event = import_course_partial_failure::from_list_of_learning_object_ids([42]);

        self::assertEquals($user->id, $event->userid);
        self::assertNull($event->relateduserid);
    }

    /**
     * @return void
     */
    public function test_instantiate_from_no_actor_id(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("There is no user in the session");

        import_course_partial_failure::from_list_of_learning_object_ids([42]);
    }

    /**
     * @return void
     */
    public function test_get_notification_event_data(): void {
        $admin = get_admin();
        $event = import_course_partial_failure::from_list_of_learning_object_ids([42], $admin->id);

        self::assertEquals(
            [
                "learning_object_ids" => ["42"],
                "user_id" => (string) $admin->id
            ],
            $event->get_notification_event_data()
        );
    }
}