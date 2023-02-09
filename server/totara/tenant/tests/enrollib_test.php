<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests covering multitenancy related changes in lib/enrollib.php
 */
class totara_tenant_enrollib_testcase extends advanced_testcase {
    public function test_enrol_get_shared_courses() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $this->getDataGenerator()->enrol_user($user0_1->id, $course0_1->id, 'manager');

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);
        $this->getDataGenerator()->enrol_user($user1_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_2->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user0_1->id, $course1_1->id, 'student');

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);
        $this->getDataGenerator()->enrol_user($user2_1->id, $course2_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_2->id, $course2_1->id, 'student');

        set_config('tenantsisolated', '0');

        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_1, $user0_2)));
        $this->assertSame([(int)$course0_1->id, (int)$course1_1->id], array_keys(enrol_get_shared_courses($user0_1, $user1_1)));
        $this->assertSame([(int)$course1_1->id], array_keys(enrol_get_shared_courses($user0_1, $user1_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_1, $user1_3)));
        $this->assertSame([(int)$course0_1->id], array_keys(enrol_get_shared_courses($user0_1, $user2_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_1, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_1, $user2_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user1_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user1_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user1_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user2_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user2_3)));
        $this->assertSame([(int)$course1_1->id], array_keys(enrol_get_shared_courses($user1_1, $user1_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_1, $user1_3)));
        $this->assertSame([(int)$course0_1->id], array_keys(enrol_get_shared_courses($user1_1, $user2_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_1, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_1, $user2_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_2, $user1_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_2, $user2_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_2, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_2, $user2_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_3, $user2_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_3, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_3, $user2_3)));
        $this->assertSame([(int)$course2_1->id], array_keys(enrol_get_shared_courses($user2_1, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user2_1, $user2_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user2_2, $user2_3)));

        set_config('tenantsisolated', '1');

        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_1, $user0_2)));
        $this->assertSame([(int)$course1_1->id], array_keys(enrol_get_shared_courses($user0_1, $user1_1)));
        $this->assertSame([(int)$course1_1->id], array_keys(enrol_get_shared_courses($user0_1, $user1_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_1, $user1_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_1, $user2_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_1, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_1, $user2_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user1_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user1_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user1_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user2_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user0_2, $user2_3)));
        $this->assertSame([(int)$course1_1->id], array_keys(enrol_get_shared_courses($user1_1, $user1_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_1, $user1_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_1, $user2_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_1, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_1, $user2_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_2, $user1_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_2, $user2_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_2, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_2, $user2_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_3, $user2_1)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_3, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user1_3, $user2_3)));
        $this->assertSame([(int)$course2_1->id], array_keys(enrol_get_shared_courses($user2_1, $user2_2)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user2_1, $user2_3)));
        $this->assertSame([], array_keys(enrol_get_shared_courses($user2_2, $user2_3)));
    }

    public function test_enrol_get_all_users_courses() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $this->getDataGenerator()->enrol_user($user0_1->id, $course0_1->id, 'manager');

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);
        $this->getDataGenerator()->enrol_user($user1_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_2->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user0_1->id, $course1_1->id, 'student');

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);
        $this->getDataGenerator()->enrol_user($user2_1->id, $course2_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_2->id, $course2_1->id, 'student');

        set_config('tenantsisolated', '0');

        $this->assertSame([(int)$course0_1->id, (int)$course1_1->id], array_keys(enrol_get_all_users_courses($user0_1->id)));
        $this->assertSame([], array_keys(enrol_get_all_users_courses($user0_2->id)));
        $this->assertSame([(int)$course0_1->id, (int)$course1_1->id], array_keys(enrol_get_all_users_courses($user1_1->id)));
        $this->assertSame([(int)$course1_1->id], array_keys(enrol_get_all_users_courses($user1_2->id)));
        $this->assertSame([], array_keys(enrol_get_all_users_courses($user1_3->id)));
        $this->assertSame([(int)$course0_1->id, (int)$course2_1->id], array_keys(enrol_get_all_users_courses($user2_1->id)));
        $this->assertSame([(int)$course2_1->id], array_keys(enrol_get_all_users_courses($user2_2->id)));

        set_config('tenantsisolated', '1');

        $this->assertSame([(int)$course0_1->id, (int)$course1_1->id], array_keys(enrol_get_all_users_courses($user0_1->id)));
        $this->assertSame([], array_keys(enrol_get_all_users_courses($user0_2->id)));
        $this->assertSame([(int)$course1_1->id], array_keys(enrol_get_all_users_courses($user1_1->id)));
        $this->assertSame([(int)$course1_1->id], array_keys(enrol_get_all_users_courses($user1_2->id)));
        $this->assertSame([], array_keys(enrol_get_all_users_courses($user1_3->id)));
        $this->assertSame([(int)$course2_1->id], array_keys(enrol_get_all_users_courses($user2_1->id)));
        $this->assertSame([(int)$course2_1->id], array_keys(enrol_get_all_users_courses($user2_2->id)));
    }

    public function test_is_enrolled() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null]);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);

        $this->getDataGenerator()->enrol_user($user0_1->id, $course0_1->id, 'manager');
        $this->getDataGenerator()->enrol_user($user0_1->id, $course1_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user1_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_1->id, $course1_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user1_2->id, $course1_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user2_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course2_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user2_2->id, $course2_1->id, 'student');

        set_config('tenantsisolated', '0');

        $this->assertTrue(is_enrolled(context_course::instance($course0_1->id), $user0_1));
        $this->assertTrue(is_enrolled(context_course::instance($course1_1->id), $user0_1));

        $this->assertTrue(is_enrolled(context_course::instance($course0_1->id), $user1_1));
        $this->assertTrue(is_enrolled(context_course::instance($course1_1->id), $user1_1));

        $this->assertTrue(is_enrolled(context_course::instance($course1_1->id), $user1_2));

        $this->assertTrue(is_enrolled(context_course::instance($course0_1->id), $user2_1));
        $this->assertFalse(is_enrolled(context_course::instance($course1_1->id), $user2_1));
        $this->assertTrue(is_enrolled(context_course::instance($course2_1->id), $user2_1));

        $this->assertTrue(is_enrolled(context_course::instance($course2_1->id), $user2_2));

        set_config('tenantsisolated', '1');

        $this->assertTrue(is_enrolled(context_course::instance($course0_1->id), $user0_1));
        $this->assertTrue(is_enrolled(context_course::instance($course1_1->id), $user0_1));

        $this->assertFalse(is_enrolled(context_course::instance($course0_1->id), $user1_1));
        $this->assertTrue(is_enrolled(context_course::instance($course1_1->id), $user1_1));

        $this->assertTrue(is_enrolled(context_course::instance($course1_1->id), $user1_2));

        $this->assertFalse(is_enrolled(context_course::instance($course0_1->id), $user2_1));
        $this->assertFalse(is_enrolled(context_course::instance($course1_1->id), $user2_1));
        $this->assertTrue(is_enrolled(context_course::instance($course2_1->id), $user2_1));

        $this->assertTrue(is_enrolled(context_course::instance($course2_1->id), $user2_2));
    }

    public function test_get_enrolled_join() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $frontpage = $DB->get_record('course', ['id' => SITEID], '*', MUST_EXIST);
        $admin = get_admin();

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null]);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);

        $this->getDataGenerator()->enrol_user($user0_1->id, $course0_1->id, 'manager');
        $this->getDataGenerator()->enrol_user($user0_1->id, $course1_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user1_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_1->id, $course1_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user1_2->id, $course1_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user2_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course2_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user2_2->id, $course2_1->id, 'student');

        $this->setUser(0);

        set_config('tenantsisolated', '0');

        $join = get_enrolled_join(context_course::instance($frontpage->id), 'u.id', false);
        $sql = "SELECT u.id
                  FROM {user} u
          $join->joins
                 WHERE $join->wheres
              ORDER BY u.id ASC";
        $expected = [$admin->id, $user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, $DB->get_fieldset_sql($sql, $join->params));

        $join = get_enrolled_join(context_course::instance($frontpage->id), 'u.id', true);
        $sql = "SELECT u.id
                  FROM {user} u
          $join->joins
                 WHERE $join->wheres
              ORDER BY u.id ASC";
        $expected = [$admin->id, $user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, $DB->get_fieldset_sql($sql, $join->params));

        $join = get_enrolled_join(context_course::instance($course0_1->id), 'u.id', false);
        $sql = "SELECT u.id
                  FROM {user} u
          $join->joins
                 WHERE $join->wheres
              ORDER BY u.id ASC";
        $expected = [$user0_1->id, $user1_1->id, $user2_1->id];
        $this->assertSame($expected, $DB->get_fieldset_sql($sql, $join->params));

        $join = get_enrolled_join(context_course::instance($course0_1->id), 'u.id', true);
        $sql = "SELECT u.id
                  FROM {user} u
          $join->joins
                 WHERE $join->wheres
              ORDER BY u.id ASC";
        $expected = [$user0_1->id, $user1_1->id, $user2_1->id];
        $this->assertSame($expected, $DB->get_fieldset_sql($sql, $join->params));

        $join = get_enrolled_join(context_course::instance($course1_1->id), 'u.id', false);
        $sql = "SELECT u.id
                  FROM {user} u
          $join->joins
                 WHERE $join->wheres
              ORDER BY u.id ASC";
        $expected = [$user0_1->id, $user1_1->id, $user1_2->id, $user2_1->id];
        $this->assertSame($expected, $DB->get_fieldset_sql($sql, $join->params));

        $join = get_enrolled_join(context_course::instance($course1_1->id), 'u.id', true);
        $sql = "SELECT u.id
                  FROM {user} u
          $join->joins
                 WHERE $join->wheres
              ORDER BY u.id ASC";
        $expected = [$user0_1->id, $user1_1->id, $user1_2->id];
        $this->assertSame($expected, $DB->get_fieldset_sql($sql, $join->params));

        $join = get_enrolled_join(context_course::instance($course2_1->id), 'u.id', false);
        $sql = "SELECT u.id
                  FROM {user} u
          $join->joins
                 WHERE $join->wheres
              ORDER BY u.id ASC";
        $expected = [$user2_1->id, $user2_2->id];
        $this->assertSame($expected, $DB->get_fieldset_sql($sql, $join->params));

        $join = get_enrolled_join(context_course::instance($course2_1->id), 'u.id', true);
        $sql = "SELECT u.id
                  FROM {user} u
          $join->joins
                 WHERE $join->wheres
              ORDER BY u.id ASC";
        $expected = [$user2_1->id, $user2_2->id];
        $this->assertSame($expected, $DB->get_fieldset_sql($sql, $join->params));
    }
}
