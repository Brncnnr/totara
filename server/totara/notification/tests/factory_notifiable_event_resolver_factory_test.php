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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_notification\factory\notifiable_event_resolver_factory;
use totara_core\extended_context;

class totara_notification_factory_notifiable_event_resolver_factory_test extends testcase {

    public function test_context_has_resolvers_with_capabilities() {
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $course_context = \context_course::instance($course->id);
        $course_extended_context = extended_context::make_with_context($course_context);

        $program = $generator->get_plugin_generator('totara_program')->create_program();
        $program_context = \context_program::instance($program->id);
        $program_extended_context = extended_context::make_with_context($program_context);

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $role1id = $generator->create_role();
        $role2id = $generator->create_role();
        assign_capability('totara/notification:managenotifications', CAP_ALLOW, $role1id, $course_context);
        assign_capability('moodle/course:managecoursenotifications', CAP_ALLOW, $role2id, $course_context);
        assign_capability('totara/notification:managenotifications', CAP_ALLOW, $role1id, $program_context);
        assign_capability('moodle/course:managecoursenotifications', CAP_ALLOW, $role2id, $program_context);

        $generator->role_assign($role1id, $user1->id, $course_context->id);
        $generator->role_assign($role2id, $user2->id, $course_context->id);
        $generator->role_assign($role1id, $user1->id, $program_context->id);
        $generator->role_assign($role2id, $user2->id, $program_context->id);

        accesslib_clear_all_caches_for_unit_testing();

        // Has the correct cap to manage notifications
        self::assertTrue(notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($program_extended_context, $user1->id));
        // Has the correct cap to manage notifications
        self::assertTrue(notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($course_extended_context, $user1->id));

        // Does not have the correct cap (has course, not prog).
        self::assertFalse(notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($program_extended_context, $user2->id));
        // Has the correct cap to manage course notifications
        self::assertTrue(notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($course_extended_context, $user2->id));

        // Does not have the correct cap.
        self::assertFalse(notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($program_extended_context, $user3->id));
        // Does not have the correct cap.
        self::assertFalse(notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($course_extended_context, $user3->id));
    }
}
