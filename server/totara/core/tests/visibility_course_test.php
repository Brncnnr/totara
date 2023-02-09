<?php
/*
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test Totara visibility for courses
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_core_course_visibility_testcase
 *
 */
class totara_core_visibility_course_testcase extends advanced_testcase {

    private const CAP = 'moodle/course:viewhiddencourses';

    public function test_complex_traditional_setup_no_multitenancy() {
        global $CFG, $DB;

        $CFG->audiencevisibility = null;
        unset($CFG->audiencevisibility);

        $gen = $this->getDataGenerator();

        // Allowed via role.
        $user_admin = $gen->create_user(['idnumber' => 'admin']);
        $user_site = $gen->create_user(['idnumber' => 'site']);
        $user_category1 = $gen->create_user(['idnumber' => 'category1']);
        $user_category1_1 = $gen->create_user(['idnumber' => 'category1_1']);
        $user_category2 = $gen->create_user(['idnumber' => 'category2']);
        $user_course_1 = $gen->create_user(['idnumber' => 'course_1']);
        $user_course_1_1_2 = $gen->create_user(['idnumber' => 'course_1_1_2']);
        $user_none = $gen->create_user(['idnumber' => 'none']);

        // Allowed view role overrides.
        $user_override_category2 = $gen->create_user(['idnumber' => 'override_category2']);
        $user_override_category1_1 = $gen->create_user(['idnumber' => 'override_category1_1']);

        $users = [
            $user_admin, $user_site, $user_category1, $user_category1_1, $user_category2, $user_course_1, $user_course_1_1_2, $user_none,
            $user_override_category2, $user_override_category1_1
        ];

        $context_system = \context_system::instance();

        $allow_roleid = $gen->create_role();
        role_change_permission($allow_roleid, $context_system, self::CAP, CAP_ALLOW);

        $prevent_roleid = $gen->create_role();
        role_change_permission($prevent_roleid, $context_system, self::CAP, CAP_PREVENT);

        $DB->set_field('course', 'idnumber', 'site', ['id' => SITEID]);

        $cc1 = $gen->create_category(['idnumber' => 'cc1']);
        $cc1_1 = $gen->create_category(['idnumber' => 'cc1_1', 'parent' => $cc1->id, 'visible' => 1]);
        $cc1_1_1 = $gen->create_category(['idnumber' => 'cc1_1_1', 'parent' => $cc1_1->id]);
        $cc1_1_2 = $gen->create_category(['idnumber' => 'cc1_1_2', 'parent' => $cc1_1->id]);
        $cc1_2 = $gen->create_category(['idnumber' => 'cc1_2', 'parent' => $cc1->id, 'visible' => 0]);
        $cc1_2_1 = $gen->create_category(['idnumber' => 'cc1_2_1', 'parent' => $cc1_2->id]);
        $cc1_2_2 = $gen->create_category(['idnumber' => 'cc1_2_2', 'parent' => $cc1_2->id]);
        $cc2 = $gen->create_category(['idnumber' => 'cc2']);
        $last = $cc2;
        for ($i = 0; $i <= 20; $i++) {
            $last = $gen->create_category(['idnumber' => 'cc2_'.$i, 'parent' => $last->id]);
        }
        $cat_deep = $last;

        $categories = [
            $cc1, $cc1_1, $cc1_1_1, $cc1_1_2, $cc1_2, $cc1_2_1, $cc1_2_2, $cc2, $cat_deep
        ];

        $count = 0;
        $all_courses = [];
        $all_visible = ['site'];
        $all_hidden = [];
        foreach ($categories as $cat) {
            $count += 1;
            $all_courses[] = $gen->create_course(['idnumber' => $cat->idnumber . '_visible_1', 'visible' => 1, 'category' => $cat->id]);
            $all_courses[] = $gen->create_course(['idnumber' => $cat->idnumber . '_visible_2', 'visible' => 1, 'category' => $cat->id]);
            $all_courses[] = $gen->create_course(['idnumber' => $cat->idnumber . '_hidden', 'visible' => 0, 'category' => $cat->id]);

            $all_visible[] = $cat->idnumber . '_visible_1';
            $all_visible[] = $cat->idnumber . '_visible_2';
            $all_hidden[] = $cat->idnumber . '_hidden';
        }

        // Assign the allow roles.
        $CFG->siteadmins = $CFG->siteadmins . ',' . $user_admin->id;
        role_assign($allow_roleid, $user_site->id, $context_system);
        role_assign($allow_roleid, $user_category1->id, \context_coursecat::instance($cc1->id));
        role_assign($allow_roleid, $user_category1_1->id, \context_coursecat::instance($cc1_1->id));
        role_assign($allow_roleid, $user_category2->id, \context_coursecat::instance($cc2->id));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_hidden'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_hidden'])
        ));

        // Assign the override roles.
        role_assign($prevent_roleid, $user_override_category2->id, $context_system);
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc2->id));
        role_assign($prevent_roleid, $user_override_category1_1->id, $context_system);
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc1_1->id));

        $manager = \totara_core\visibility_controller::course();
        $manager->map()->recalculate_complete_map();

        self::assertTrue(is_siteadmin($user_admin->id));

        foreach ($users as $user) {
            if (empty($CFG->disable_visibility_maps)) {
                $sql = (new \core\dml\sql('SELECT c.idnumber FROM {course} c'))
                    ->append($manager->sql_where_visible($user->id, 'c'), ' WHERE ');
                $actual = $DB->get_fieldset_sql($sql);
            } else {
                [$sql, $params] = totara_visibility_where($user->id, 'c.id', 'c.visible', 'c.audiencevisible', 'c');
                $sql = "SELECT c.idnumber
                 FROM {course} c
           INNER JOIN {context} ctx
                   ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                WHERE {$sql}";
                $params['contextlevel'] = CONTEXT_COURSE;
                $actual = $DB->get_fieldset_sql($sql, $params);
            }

            foreach ($all_courses as $course) {
                if ($course->visible == 1) {
                    continue;
                }
                $context = \context_course::instance($course->id);

                $useridnumber = $DB->get_field('user', 'idnumber', ['id' => $user->id]);

                $message = "User `{$useridnumber}` for course `{$course->idnumber}` is ";
                if (in_array($course->idnumber, $actual)) {
                    // It's there, check we've got the capability
                    self::assertTrue(has_capability(self::CAP, $context, $user->id), $message . ' expected');
                } else {
                    // Check they don't.
                    self::assertFalse(has_capability(self::CAP, $context, $user->id), $message . ' not expected');
                }
            }
        }
    }

    public function test_complex_traditional_setup_no_multitenancy_with_maps_disabled() {
        global $CFG;
        $CFG->disable_visibility_maps = true;

        $this->test_complex_traditional_setup_no_multitenancy();

        unset($CFG->disable_visibility_maps);
    }

    public function test_complex_traditional_setup_multitenancy_loose() {
        global $CFG, $DB;

        $CFG->audiencevisibility = null;
        unset($CFG->audiencevisibility);

        $gen = $this->getDataGenerator();
        /** @var \totara_tenant\testing\generator $multitenancy */
        $multitenancy = $gen->get_plugin_generator('totara_tenant');

        $multitenancy->enable_tenants();

        $tenant1 = $multitenancy->create_tenant();
        $tenant2 = $multitenancy->create_tenant();

        // Allowed via role.
        $user_admin = $gen->create_user(['idnumber' => 'admin']);
        $user_site = $gen->create_user(['idnumber' => 'site']);
        $user_category1 = $gen->create_user(['idnumber' => 'category1', 'tenantid' => $tenant1->id]);
        $user_category1_1 = $gen->create_user(['idnumber' => 'category1_1', 'tenantid' => $tenant1->id]);
        $user_category2 = $gen->create_user(['idnumber' => 'category2', 'tenantid' => $tenant2->id]);
        $user_course_1 = $gen->create_user(['idnumber' => 'course_1', 'tenantid' => $tenant1->id]);
        $user_course_1_1_2 = $gen->create_user(['idnumber' => 'course_1_1_2', 'tenantid' => $tenant1->id]);
        $user_none = $gen->create_user(['idnumber' => 'none']);
        $user_none_tenant = $gen->create_user(['idnumber' => 'none', 'tenantid' => $tenant2->id]);

        // Allowed view role overrides.
        $user_override_category2 = $gen->create_user(['idnumber' => 'override_category2', 'tenantid' => $tenant2->id]);
        $user_override_category1_1 = $gen->create_user(['idnumber' => 'override_category1_1', 'tenantid' => $tenant1->id]);

        $users = [
            $user_admin, $user_site, $user_category1, $user_category1_1, $user_category2, $user_course_1, $user_course_1_1_2,
            $user_none, $user_none_tenant,
            $user_override_category2, $user_override_category1_1
        ];

        $context_system = \context_system::instance();

        $allow_roleid = $gen->create_role();
        role_change_permission($allow_roleid, $context_system, self::CAP, CAP_ALLOW);

        $prevent_roleid = $gen->create_role();
        role_change_permission($prevent_roleid, $context_system, self::CAP, CAP_PREVENT);

        $DB->set_field('course', 'idnumber', 'site', ['id' => SITEID]);

        $cc1 = $gen->create_category(['idnumber' => 'cc1', 'parent' => $tenant1->categoryid]);
        $cc1_1 = $gen->create_category(['idnumber' => 'cc1_1', 'parent' => $cc1->id, 'visible' => 1]);
        $cc1_1_1 = $gen->create_category(['idnumber' => 'cc1_1_1', 'parent' => $cc1_1->id]);
        $cc1_1_2 = $gen->create_category(['idnumber' => 'cc1_1_2', 'parent' => $cc1_1->id]);
        $cc1_2 = $gen->create_category(['idnumber' => 'cc1_2', 'parent' => $cc1->id, 'visible' => 0]);
        $cc1_2_1 = $gen->create_category(['idnumber' => 'cc1_2_1', 'parent' => $cc1_2->id]);
        $cc1_2_2 = $gen->create_category(['idnumber' => 'cc1_2_2', 'parent' => $cc1_2->id]);
        $cc2 = $gen->create_category(['idnumber' => 'cc2', 'parent' => $tenant2->categoryid]);
        $last = $cc2;
        for ($i = 0; $i <= 20; $i++) {
            $last = $gen->create_category(['idnumber' => 'cc2_'.$i, 'parent' => $last->id]);
        }
        $cat_deep = $last;

        $categories = [
            $cc1, $cc1_1, $cc1_1_1, $cc1_1_2, $cc1_2, $cc1_2_1, $cc1_2_2, $cc2, $cat_deep
        ];

        $count = 0;
        $all_courses = [];
        $all_visible = ['site'];
        $all_hidden = [];
        foreach ($categories as $cat) {
            $count += 1;
            $all_courses[] = $gen->create_course(['idnumber' => $cat->idnumber . '_visible_1', 'visible' => 1, 'category' => $cat->id]);
            $all_courses[] = $gen->create_course(['idnumber' => $cat->idnumber . '_visible_2', 'visible' => 1, 'category' => $cat->id]);
            $all_courses[] = $gen->create_course(['idnumber' => $cat->idnumber . '_hidden', 'visible' => 0, 'category' => $cat->id]);

            $all_visible[] = $cat->idnumber . '_visible_1';
            $all_visible[] = $cat->idnumber . '_visible_2';
            $all_hidden[] = $cat->idnumber . '_hidden';
        }

        // Assign the allow roles.
        $CFG->siteadmins = $CFG->siteadmins . ',' . $user_admin->id;
        role_assign($allow_roleid, $user_site->id, $context_system);
        role_assign($allow_roleid, $user_category1->id, \context_coursecat::instance($cc1->id));
        role_assign($allow_roleid, $user_category1_1->id, \context_coursecat::instance($cc1_1->id));
        role_assign($allow_roleid, $user_category2->id, \context_coursecat::instance($cc2->id));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_hidden'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_hidden'])
        ));

        // Assign the override roles.
        role_assign($prevent_roleid, $user_override_category2->id, $context_system);
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc2->id));
        role_assign($prevent_roleid, $user_override_category1_1->id, $context_system);
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc1_1->id));

        $manager = \totara_core\visibility_controller::course();
        $manager->map()->recalculate_complete_map();

        self::assertTrue(is_siteadmin($user_admin->id));

        foreach ($users as $user) {
            if (empty($CFG->disable_visibility_maps)) {
                $sql = (new \core\dml\sql('SELECT c.idnumber FROM {course} c'))
                    ->append($manager->sql_where_visible($user->id, 'c'), ' WHERE ');
                $actual = $DB->get_fieldset_sql($sql);
            } else {
                [$sql, $params] = totara_visibility_where($user->id, 'c.id', 'c.visible', 'c.audiencevisible', 'c');
                $sql = "SELECT c.idnumber
                 FROM {course} c
           INNER JOIN {context} ctx
                   ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                WHERE {$sql}";
                $params['contextlevel'] = CONTEXT_COURSE;
                $actual = $DB->get_fieldset_sql($sql, $params);
            }

            foreach ($all_courses as $course) {
                if ($course->visible == 1) {
                    continue;
                }
                $context = \context_course::instance($course->id);

                $useridnumber = $DB->get_field('user', 'idnumber', ['id' => $user->id]);

                $message = "User `{$useridnumber}` for course `{$course->idnumber}` is ";
                if (in_array($course->idnumber, $actual)) {
                    // It's there, check we've got the capability
                    self::assertTrue(has_capability(self::CAP, $context, $user->id), $message . ' expected');
                } else {
                    // Check they don't.
                    self::assertFalse(has_capability(self::CAP, $context, $user->id), $message . ' not expected');
                }
            }
        }
    }

    public function test_complex_traditional_setup_multitenancy_loose_with_maps_disabled() {
        global $CFG;
        $CFG->disable_visibility_maps = true;

        $this->test_complex_traditional_setup_multitenancy_loose();

        unset($CFG->disable_visibility_maps);
    }

    public function test_complex_traditional_setup_multitenancy_strict() {
        global $CFG, $DB;

        $CFG->audiencevisibility = null;
        unset($CFG->audiencevisibility);

        $gen = $this->getDataGenerator();
        /** @var \totara_tenant\testing\generator $multitenancy */
        $multitenancy = $gen->get_plugin_generator('totara_tenant');

        $multitenancy->enable_tenants();
        set_config('tenantsisolated', 1);

        $tenant1 = $multitenancy->create_tenant();
        $tenant2 = $multitenancy->create_tenant();

        // Allowed via role.
        $user_admin = $gen->create_user(['idnumber' => 'admin']);
        $user_site = $gen->create_user(['idnumber' => 'site']);
        $user_category1 = $gen->create_user(['idnumber' => 'category1', 'tenantid' => $tenant1->id]);
        $user_category1_1 = $gen->create_user(['idnumber' => 'category1_1', 'tenantid' => $tenant1->id]);
        $user_category2 = $gen->create_user(['idnumber' => 'category2', 'tenantid' => $tenant2->id]);
        $user_course_1 = $gen->create_user(['idnumber' => 'course_1', 'tenantid' => $tenant1->id]);
        $user_course_1_1_2 = $gen->create_user(['idnumber' => 'course_1_1_2', 'tenantid' => $tenant1->id]);
        $user_none = $gen->create_user(['idnumber' => 'none']);
        $user_none_tenant = $gen->create_user(['idnumber' => 'none', 'tenantid' => $tenant2->id]);

        // Allowed view role overrides.
        $user_override_category2 = $gen->create_user(['idnumber' => 'override_category2', 'tenantid' => $tenant2->id]);
        $user_override_category1_1 = $gen->create_user(['idnumber' => 'override_category1_1', 'tenantid' => $tenant1->id]);

        $users = [
            $user_admin, $user_site, $user_category1, $user_category1_1, $user_category2, $user_course_1, $user_course_1_1_2,
            $user_none, $user_none_tenant,
            $user_override_category2, $user_override_category1_1
        ];

        $context_system = \context_system::instance();

        $allow_roleid = $gen->create_role();
        role_change_permission($allow_roleid, $context_system, self::CAP, CAP_ALLOW);

        $prevent_roleid = $gen->create_role();
        role_change_permission($prevent_roleid, $context_system, self::CAP, CAP_PREVENT);

        $DB->set_field('course', 'idnumber', 'site', ['id' => SITEID]);

        $cc1 = $gen->create_category(['idnumber' => 'cc1', 'parent' => $tenant1->categoryid]);
        $cc1_1 = $gen->create_category(['idnumber' => 'cc1_1', 'parent' => $cc1->id, 'visible' => 1]);
        $cc1_1_1 = $gen->create_category(['idnumber' => 'cc1_1_1', 'parent' => $cc1_1->id]);
        $cc1_1_2 = $gen->create_category(['idnumber' => 'cc1_1_2', 'parent' => $cc1_1->id]);
        $cc1_2 = $gen->create_category(['idnumber' => 'cc1_2', 'parent' => $cc1->id, 'visible' => 0]);
        $cc1_2_1 = $gen->create_category(['idnumber' => 'cc1_2_1', 'parent' => $cc1_2->id]);
        $cc1_2_2 = $gen->create_category(['idnumber' => 'cc1_2_2', 'parent' => $cc1_2->id]);
        $cc2 = $gen->create_category(['idnumber' => 'cc2', 'parent' => $tenant2->categoryid]);
        $last = $cc2;
        for ($i = 0; $i <= 20; $i++) {
            $last = $gen->create_category(['idnumber' => 'cc2_'.$i, 'parent' => $last->id]);
        }
        $cat_deep = $last;

        $categories = [
            $cc1, $cc1_1, $cc1_1_1, $cc1_1_2, $cc1_2, $cc1_2_1, $cc1_2_2, $cc2, $cat_deep
        ];

        $count = 0;
        $all_courses = [];
        $all_visible = ['site'];
        $all_hidden = [];
        foreach ($categories as $cat) {
            $count += 1;
            $all_courses[] = $gen->create_course(['idnumber' => $cat->idnumber . '_visible_1', 'visible' => 1, 'category' => $cat->id]);
            $all_courses[] = $gen->create_course(['idnumber' => $cat->idnumber . '_visible_2', 'visible' => 1, 'category' => $cat->id]);
            $all_courses[] = $gen->create_course(['idnumber' => $cat->idnumber . '_hidden', 'visible' => 0, 'category' => $cat->id]);

            $all_visible[] = $cat->idnumber . '_visible_1';
            $all_visible[] = $cat->idnumber . '_visible_2';
            $all_hidden[] = $cat->idnumber . '_hidden';
        }

        // Assign the allow roles.
        $CFG->siteadmins = $CFG->siteadmins . ',' . $user_admin->id;
        role_assign($allow_roleid, $user_site->id, $context_system);
        role_assign($allow_roleid, $user_category1->id, \context_coursecat::instance($cc1->id));
        role_assign($allow_roleid, $user_category1_1->id, \context_coursecat::instance($cc1_1->id));
        role_assign($allow_roleid, $user_category2->id, \context_coursecat::instance($cc2->id));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_hidden'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_hidden'])
        ));

        // Assign the override roles.
        role_assign($prevent_roleid, $user_override_category2->id, $context_system);
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc2->id));
        role_assign($prevent_roleid, $user_override_category1_1->id, $context_system);
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc1_1->id));

        $manager = \totara_core\visibility_controller::course();
        $manager->map()->recalculate_complete_map();

        self::assertTrue(is_siteadmin($user_admin->id));

        foreach ($users as $user) {
            if (empty($CFG->disable_visibility_maps)) {
                $sql = (new \core\dml\sql('SELECT c.idnumber FROM {course} c'))
                    ->append($manager->sql_where_visible($user->id, 'c'), ' WHERE ');
                $actual = $DB->get_fieldset_sql($sql);
            } else {
                [$sql, $params] = totara_visibility_where($user->id, 'c.id', 'c.visible', 'c.audiencevisible', 'c');
                $sql = "SELECT c.idnumber
                 FROM {course} c
           INNER JOIN {context} ctx
                   ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                WHERE {$sql}";
                $params['contextlevel'] = CONTEXT_COURSE;
                $actual = $DB->get_fieldset_sql($sql, $params);
            }

            foreach ($all_courses as $course) {
                if ($course->visible == 1) {
                    continue;
                }
                $context = \context_course::instance($course->id);

                $useridnumber = $DB->get_field('user', 'idnumber', ['id' => $user->id]);

                $message = "User `{$useridnumber}` for course `{$course->idnumber}` is ";
                if (in_array($course->idnumber, $actual)) {
                    // It's there, check we've got the capability
                    self::assertTrue(has_capability(self::CAP, $context, $user->id), $message . ' expected');
                } else {
                    // Check they don't.
                    self::assertFalse(has_capability(self::CAP, $context, $user->id), $message . ' not expected');
                }
            }
        }
    }

    public function test_complex_traditional_setup_multitenancy_strict_with_maps_disabled() {
        global $CFG;
        $CFG->disable_visibility_maps = true;

        $this->test_complex_traditional_setup_multitenancy_strict();

        unset($CFG->disable_visibility_maps);
    }

    public function test_complex_audience_based_setup_no_multitenancy() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/core/totara.php');

        $CFG->audiencevisibility = 1;

        $gen = $this->getDataGenerator();

        // Set up our users.
        $user_admin = $gen->create_user(['idnumber' => 'admin']);
        $user_site = $gen->create_user(['idnumber' => 'site']);
        // Allowed via category
        $user_category1 = $gen->create_user(['idnumber' => 'category1']);
        $user_category1_1 = $gen->create_user(['idnumber' => 'category1_1']);
        $user_category2 = $gen->create_user(['idnumber' => 'category2']);
        // Allowed directly via course
        $user_course_1 = $gen->create_user(['idnumber' => 'course_1']);
        $user_course_1_1_2 = $gen->create_user(['idnumber' => 'course_1_1_2']);
        // Allowed via audience
        $user_audience_1_2 = $gen->create_user(['idnumber' => 'audience_1_2']);
        $user_audience_2 = $gen->create_user(['idnumber' => 'audience_2']);
        // Allowed via enrolled.
        $user_enrolled_1_2 = $gen->create_user(['idnumber' => 'enrolled_1_2']);
        $user_enrolled_2 = $gen->create_user(['idnumber' => 'enrolled_2']);
        // Allowed view role overrides.
        $user_override_category = $gen->create_user(['idnumber' => 'override_category']);
        $user_override_category1_1 = $gen->create_user(['idnumber' => 'override_category1_1']);
        $user_override_category1_2 = $gen->create_user(['idnumber' => 'override_category1_2']);
        $user_override_category_deep = $gen->create_user(['idnumber' => 'override_category_deep']);
        // No special allowance.
        $user_none = $gen->create_user(['idnumber' => 'none']);

        // Set up audiences.
        $audience1 = $gen->create_cohort();
        $audience2 = $gen->create_cohort();
        cohort_add_member($audience1->id, $user_audience_1_2->id);
        cohort_add_member($audience2->id, $user_audience_2->id);

        $context_system = \context_system::instance();

        // Create roles to allow, prevent, and prohibit.
        $allow_roleid = $gen->create_role();
        role_change_permission($allow_roleid, $context_system, self::CAP, CAP_ALLOW);
        $prevent_roleid = $gen->create_role();
        role_change_permission($prevent_roleid, $context_system, self::CAP, CAP_PREVENT);
        $prohibit_override_roleid = $gen->create_role();
        role_change_permission($prohibit_override_roleid, $context_system, self::CAP, CAP_ALLOW);

        $DB->set_field('course', 'idnumber', 'site', ['id' => SITEID]);

        $cc1 = $gen->create_category(['idnumber' => 'cc1', 'parent' => '0']);
        $cc1_1 = $gen->create_category(['idnumber' => 'cc1_1', 'parent' => $cc1->id, 'visible' => 1]);
        $cc1_1_1 = $gen->create_category(['idnumber' => 'cc1_1_1', 'parent' => $cc1_1->id]);
        $cc1_1_2 = $gen->create_category(['idnumber' => 'cc1_1_2', 'parent' => $cc1_1->id]);
        $cc1_2 = $gen->create_category(['idnumber' => 'cc1_2', 'parent' => $cc1->id, 'visible' => 0]);
        $cc1_2_1 = $gen->create_category(['idnumber' => 'cc1_2_1', 'parent' => $cc1_2->id]);
        $cc1_2_2 = $gen->create_category(['idnumber' => 'cc1_2_2', 'parent' => $cc1_2->id]);
        $cc2 = $gen->create_category(['idnumber' => 'cc2', 'parent' => '0']);
        $last = $cc2;
        for ($i = 0; $i <= 20; $i++) {
            $last = $gen->create_category(['idnumber' => 'cc2_'.$i, 'parent' => $last->id]);
        }
        $cat_deep = $last;

        $categories = [
            $cc1, $cc1_1, $cc1_1_1, $cc1_1_2, $cc1_2, $cc1_2_1, $cc1_2_2, $cc2, $cat_deep
        ];

        $count = 0;
        $all_courses = [];
        foreach ($categories as $cat) {
            $count += 1;
            $map = [
                1 => COHORT_VISIBLE_ALL,
                2 => COHORT_VISIBLE_ENROLLED,
                3 => COHORT_VISIBLE_AUDIENCE,
                4 => COHORT_VISIBLE_NOUSERS,
            ];
            foreach ($map as $count => $vis) {
                $all_courses[] = $gen->create_course([
                    'idnumber' => $cat->idnumber . '_course_'.$count,
                    'audiencevisible' => $vis,
                    'category' => $cat->id
                ]);
            }
        }

        $expectedcourses = [
            $user_audience_1_2->id => [],
            $user_audience_2->id => [],
        ];

        $sql = $DB->sql_like('idnumber', ':cc1_2');
        $params = [
            'cc1_2' => 'cc1_2_course_%',
        ];
        $courses = $DB->get_records_sql_menu("SELECT id, audiencevisible FROM {course} WHERE {$sql}", $params);
        foreach ($courses as $courseid => $audiencevisible) {
            totara_cohort_add_association($audience1->id, $courseid, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
            if ($audiencevisible == COHORT_VISIBLE_AUDIENCE) {
                $expectedcourses[$user_audience_1_2->id][] = $courseid;
                $expectedcourses[$user_enrolled_1_2->id][] = $courseid;
            }
            $gen->enrol_user($user_enrolled_1_2->id, $courseid);
            if ($audiencevisible == COHORT_VISIBLE_ENROLLED) {
                $expectedcourses[$user_enrolled_1_2->id][] = $courseid;
            }
        }

        $sql = $DB->sql_like('idnumber', ':cc2');
        $params = ['cc2' => 'cc2_course_%',];
        $courses = $DB->get_records_sql_menu("SELECT id, audiencevisible FROM {course} WHERE {$sql}", $params);
        foreach ($courses as $courseid => $audiencevisible) {
            totara_cohort_add_association($audience2->id, $courseid, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
            if ($audiencevisible == COHORT_VISIBLE_AUDIENCE) {
                $expectedcourses[$user_audience_2->id][] = $courseid;
                $expectedcourses[$user_enrolled_2->id][] = $courseid;
            }
            $gen->enrol_user($user_enrolled_2->id, $courseid);
            if ($audiencevisible == COHORT_VISIBLE_ENROLLED) {
                $expectedcourses[$user_enrolled_2->id][] = $courseid;
            }
        }

        // Assign the allow roles.
        $CFG->siteadmins = $CFG->siteadmins . ',' . $user_admin->id;
        role_assign($allow_roleid, $user_site->id, $context_system);
        role_assign($allow_roleid, $user_category1->id, \context_coursecat::instance($cc1->id));
        role_assign($allow_roleid, $user_category1_1->id, \context_coursecat::instance($cc1_1->id));
        role_assign($allow_roleid, $user_category2->id, \context_coursecat::instance($cc2->id));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_course_2'])
        ));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_course_3'])
        ));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_course_4'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_course_2'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_course_3'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_course_4'])
        ));

        // Assign the allow role but prohibit at cat 2
        role_assign($prohibit_override_roleid, $user_override_category1_2->id, $context_system);
        role_assign($prohibit_override_roleid, $user_override_category_deep->id, $context_system);
        // Assign the allow role but prohibit at depth.
        assign_capability(self::CAP, CAP_PROHIBIT, $prohibit_override_roleid, \context_coursecat::instance($cc1_2->id));
        assign_capability(self::CAP, CAP_PROHIBIT, $prohibit_override_roleid, \context_coursecat::instance($cat_deep->id));

        // Assign the override roles.
        role_assign($prevent_roleid, $user_override_category->id, $context_system);
        role_assign($prevent_roleid, $user_override_category1_1->id, \context_coursecat::instance($cc1_1->id));
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc2->id));
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc1_1->id));


        $manager = \totara_core\visibility_controller::course();
        $manager->map()->recalculate_complete_map();

        self::assertTrue(is_siteadmin($user_admin->id));

        $users = [
            $user_admin, $user_site,
            $user_category1, $user_category1_1, $user_category2, $user_course_1, $user_none,
            $user_override_category1_1, $user_override_category, $user_override_category1_2, $user_override_category_deep,
            $user_audience_1_2, $user_audience_2,
            $user_enrolled_1_2, $user_enrolled_2,
        ];

        $map = [
            $user_admin->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '4', $cc1_2_1->id => '4', $cc1_2_2->id => '4',
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_site->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '4', $cc1_2_1->id => '4', $cc1_2_2->id => '4',
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_category1->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '4', $cc1_2_1->id => '4', $cc1_2_2->id => '4',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_category1_1->id => [
                $cc1->id => '1',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_category2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_course_1->id => [
                $cc1->id => '4',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_course_1_1_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_none->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_override_category->id => [
                $cc1->id => '1',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_override_category1_1->id => [
                $cc1->id => '1',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_override_category1_2->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '4', $cat_deep->id => '1',
            ],
            $user_override_category_deep->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '4', $cat_deep->id => '1',
            ],
            $user_audience_1_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '2', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_audience_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '2', $cat_deep->id => '1',
            ],
            $user_enrolled_1_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '3', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_enrolled_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '3', $cat_deep->id => '1',
            ],
        ];

        foreach ($users as $user) {
            if (empty($CFG->disable_visibility_maps)) {
                $sql = (new \core\dml\sql('SELECT c.idnumber FROM {course} c'))
                    ->append($manager->sql_where_visible($user->id, 'c'), ' WHERE ');
                $actual = $DB->get_fieldset_sql($sql);
            } else {
                [$sql, $params] = totara_visibility_where($user->id, 'c.id', 'c.visible', 'c.audiencevisible', 'c');
                $sql = "SELECT c.idnumber
                 FROM {course} c
           INNER JOIN {context} ctx
                   ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                WHERE {$sql}";
                $params['contextlevel'] = CONTEXT_COURSE;
                $actual = $DB->get_fieldset_sql($sql, $params);
            }

            foreach ($all_courses as $course) {
                $useridnumber = $DB->get_field('user', 'idnumber', ['id' => $user->id]);
                $message = "User `{$useridnumber}` for course `{$course->idnumber}` ({$course->id}) is ";

                if ($course->audiencevisible == COHORT_VISIBLE_ALL) {
                    // The course is visible to everyone, no need for the check.
                    self::assertContains($course->idnumber, $actual, $message . 'expected');
                    self::assertTrue(totara_course_is_viewable($course->id, $user->id));
                    continue;
                }

                if (isset($expectedcourses[$user->id]) && in_array($course->id, $expectedcourses[$user->id])) {
                    // The course is visible to this user, no need for the cap checks.
                    self::assertContains($course->idnumber, $actual, $message . 'expected');
                    self::assertTrue(totara_course_is_viewable($course->id, $user->id));
                    continue;
                }

                $context = \context_course::instance($course->id);

                if (in_array($course->idnumber, $actual)) {
                    // It's there, check we've got the capability
                    // This fails with visibility maps disabled for users with role overrides.
                    if (empty($CFG->disable_visibility_maps) || ($user->id != $user_override_category1_2->id && $user->id != $user_override_category_deep->id)) {
                        self::assertTrue(has_capability(self::CAP, $context, $user->id), $message . 'expected');
                        self::assertTrue(totara_course_is_viewable($course->id, $user->id));
                    }
                } else {
                    // Check they don't.
                    self::assertFalse(has_capability(self::CAP, $context, $user->id), $message . 'not expected');
                    self::assertFalse(totara_course_is_viewable($course->id, $user->id));
                }
            }

            $counts = $manager->get_visible_counts_for_all_categories($user->id);
            ksort($counts);
            ksort($map[$user->id]);
            self::assertSame($map[$user->id], $counts, "User `{$useridnumber}` mismapped");
            foreach ($map[$user->id] as $categoryid => $count) {
                self::assertCount($count, $manager->get_visible_in_category($categoryid, $user->id, ['id']));
            }
        }
    }

    public function test_complex_audience_based_setup_no_multitenancy_with_maps_disabled() {
        global $CFG;
        $CFG->disable_visibility_maps = true;

        $this->test_complex_audience_based_setup_no_multitenancy();

        unset($CFG->disable_visibility_maps);
    }

    public function test_complex_audience_based_setup_multitenancy_loose() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/core/totara.php');

        $CFG->audiencevisibility = 1;

        $gen = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $multitenancy */
        $multitenancy = $gen->get_plugin_generator('totara_tenant');

        $multitenancy->enable_tenants();

        $tenant1 = $multitenancy->create_tenant();
        $tenant2 = $multitenancy->create_tenant();

        // Set up our users.
        $user_admin = $gen->create_user(['idnumber' => 'admin']);
        $user_site = $gen->create_user(['idnumber' => 'site']);
        // Allowed via category
        $user_category1 = $gen->create_user(['idnumber' => 'category1', 'tenantid' => $tenant1->id]);
        $user_category1_1 = $gen->create_user(['idnumber' => 'category1_1', 'tenantid' => $tenant1->id]);
        $user_category2 = $gen->create_user(['idnumber' => 'category2', 'tenantid' => $tenant2->id]);
        // Allowed directly via course
        $user_course_1 = $gen->create_user(['idnumber' => 'course_1', 'tenantid' => $tenant1->id]);
        $user_course_1_1_2 = $gen->create_user(['idnumber' => 'course_1_1_2', 'tenantid' => $tenant1->id]);
        // Allowed via audience
        $user_audience_1_2 = $gen->create_user(['idnumber' => 'audience_1_2', 'tenantid' => $tenant1->id]);
        $user_audience_2 = $gen->create_user(['idnumber' => 'audience_2', 'tenantid' => $tenant2->id]);
        // Allowed via enrolled.
        $user_enrolled_1_2 = $gen->create_user(['idnumber' => 'enrolled_1_2', 'tenantid' => $tenant1->id]);
        $user_enrolled_2 = $gen->create_user(['idnumber' => 'enrolled_2', 'tenantid' => $tenant2->id]);
        // Allowed view role overrides.
        $user_override_category = $gen->create_user(['idnumber' => 'override_category']);
        $user_override_category1_1 = $gen->create_user(['idnumber' => 'override_category1_1', 'tenantid' => $tenant1->id]);
        $user_override_category1_2 = $gen->create_user(['idnumber' => 'override_category1_2', 'tenantid' => $tenant1->id]);
        $user_override_category_deep = $gen->create_user(['idnumber' => 'override_category_deep', 'tenantid' => $tenant2->id]);
        // No special allowance.
        $user_none = $gen->create_user(['idnumber' => 'none']);
        $user_none_tenant2 = $gen->create_user(['idnumber' => 'none', 'tenantid' => $tenant2->id]);

        // Set up audiences.
        $audience1 = $gen->create_cohort();
        $audience2 = $gen->create_cohort();
        cohort_add_member($audience1->id, $user_audience_1_2->id);
        cohort_add_member($audience2->id, $user_audience_2->id);

        $context_system = \context_system::instance();

        // Create roles to allow, prevent, and prohibit.
        $allow_roleid = $gen->create_role();
        role_change_permission($allow_roleid, $context_system, self::CAP, CAP_ALLOW);
        $prevent_roleid = $gen->create_role();
        role_change_permission($prevent_roleid, $context_system, self::CAP, CAP_PREVENT);
        $prohibit_override_roleid = $gen->create_role();
        role_change_permission($prohibit_override_roleid, $context_system, self::CAP, CAP_ALLOW);

        $DB->set_field('course', 'idnumber', 'site', ['id' => SITEID]);

        $cc1 = $gen->create_category(['idnumber' => 'cc1', 'parent' => $tenant1->categoryid]);
        $cc1_1 = $gen->create_category(['idnumber' => 'cc1_1', 'parent' => $cc1->id, 'visible' => 1]);
        $cc1_1_1 = $gen->create_category(['idnumber' => 'cc1_1_1', 'parent' => $cc1_1->id]);
        $cc1_1_2 = $gen->create_category(['idnumber' => 'cc1_1_2', 'parent' => $cc1_1->id]);
        $cc1_2 = $gen->create_category(['idnumber' => 'cc1_2', 'parent' => $cc1->id, 'visible' => 0]);
        $cc1_2_1 = $gen->create_category(['idnumber' => 'cc1_2_1', 'parent' => $cc1_2->id]);
        $cc1_2_2 = $gen->create_category(['idnumber' => 'cc1_2_2', 'parent' => $cc1_2->id]);
        $cc2 = $gen->create_category(['idnumber' => 'cc2', 'parent' => $tenant2->categoryid]);
        $last = $cc2;
        for ($i = 0; $i <= 20; $i++) {
            $last = $gen->create_category(['idnumber' => 'cc2_'.$i, 'parent' => $last->id]);
        }
        $cat_deep = $last;

        $categories = [
            $cc1, $cc1_1, $cc1_1_1, $cc1_1_2, $cc1_2, $cc1_2_1, $cc1_2_2, $cc2, $cat_deep
        ];

        $count = 0;
        $all_courses = [];
        foreach ($categories as $cat) {
            $count += 1;
            $map = [
                1 => COHORT_VISIBLE_ALL,
                2 => COHORT_VISIBLE_ENROLLED,
                3 => COHORT_VISIBLE_AUDIENCE,
                4 => COHORT_VISIBLE_NOUSERS,
            ];
            foreach ($map as $count => $vis) {
                $all_courses[] = $gen->create_course([
                    'idnumber' => $cat->idnumber . '_course_'.$count,
                    'audiencevisible' => $vis,
                    'category' => $cat->id
                ]);
            }
        }

        $expectedcourses = [
            $user_audience_1_2->id => [],
            $user_audience_2->id => [],
        ];

        $sql = $DB->sql_like('idnumber', ':cc1_2');
        $params = [
            'cc1_2' => 'cc1_2_course_%',
        ];
        $courses = $DB->get_records_sql_menu("SELECT id, audiencevisible FROM {course} WHERE {$sql}", $params);
        foreach ($courses as $courseid => $audiencevisible) {
            totara_cohort_add_association($audience1->id, $courseid, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
            if ($audiencevisible == COHORT_VISIBLE_AUDIENCE) {
                $expectedcourses[$user_audience_1_2->id][] = $courseid;
                $expectedcourses[$user_enrolled_1_2->id][] = $courseid;
            }
            $gen->enrol_user($user_enrolled_1_2->id, $courseid);
            if ($audiencevisible == COHORT_VISIBLE_ENROLLED) {
                $expectedcourses[$user_enrolled_1_2->id][] = $courseid;
            }
        }

        $sql = $DB->sql_like('idnumber', ':cc2');
        $params = ['cc2' => 'cc2_course_%',];
        $courses = $DB->get_records_sql_menu("SELECT id, audiencevisible FROM {course} WHERE {$sql}", $params);
        foreach ($courses as $courseid => $audiencevisible) {
            totara_cohort_add_association($audience2->id, $courseid, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
            if ($audiencevisible == COHORT_VISIBLE_AUDIENCE) {
                $expectedcourses[$user_audience_2->id][] = $courseid;
                $expectedcourses[$user_enrolled_2->id][] = $courseid;
            }
            $gen->enrol_user($user_enrolled_2->id, $courseid);
            if ($audiencevisible == COHORT_VISIBLE_ENROLLED) {
                $expectedcourses[$user_enrolled_2->id][] = $courseid;
            }
        }

        // Assign the allow roles.
        $CFG->siteadmins = $CFG->siteadmins . ',' . $user_admin->id;
        role_assign($allow_roleid, $user_site->id, $context_system);
        role_assign($allow_roleid, $user_category1->id, \context_coursecat::instance($cc1->id));
        role_assign($allow_roleid, $user_category1_1->id, \context_coursecat::instance($cc1_1->id));
        role_assign($allow_roleid, $user_category2->id, \context_coursecat::instance($cc2->id));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_course_2'])
        ));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_course_3'])
        ));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_course_4'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_course_2'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_course_3'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_course_4'])
        ));

        // Assign the allow role but prohibit at cat 2
        role_assign($prohibit_override_roleid, $user_override_category1_2->id, $context_system);
        role_assign($prohibit_override_roleid, $user_override_category_deep->id, $context_system);
        // Assign the allow role but prohibit at depth.
        assign_capability(self::CAP, CAP_PROHIBIT, $prohibit_override_roleid, \context_coursecat::instance($cc1_2->id));
        assign_capability(self::CAP, CAP_PROHIBIT, $prohibit_override_roleid, \context_coursecat::instance($cat_deep->id));

        // Assign the override roles.
        role_assign($prevent_roleid, $user_override_category->id, $context_system);
        role_assign($prevent_roleid, $user_override_category1_1->id, \context_coursecat::instance($cc1_1->id));
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc2->id));
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc1_1->id));


        $manager = \totara_core\visibility_controller::course();
        $manager->map()->recalculate_complete_map();

        self::assertTrue(is_siteadmin($user_admin->id));

        $users = [
            $user_admin, $user_site,
            $user_category1, $user_category1_1, $user_category2, $user_course_1,
            $user_none, $user_none_tenant2,
            $user_override_category1_1, $user_override_category, $user_override_category1_2, $user_override_category_deep,
            $user_audience_1_2, $user_audience_2,
            $user_enrolled_1_2, $user_enrolled_2,
        ];

        $map = [
            $user_admin->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '4', $cc1_2_1->id => '4', $cc1_2_2->id => '4',
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_site->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '4', $cc1_2_1->id => '4', $cc1_2_2->id => '4',
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_category1->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '4', $cc1_2_1->id => '4', $cc1_2_2->id => '4',
            ],
            $user_category1_1->id => [
                $cc1->id => '1',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_category2->id => [
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_course_1->id => [
                $cc1->id => '4',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_course_1_1_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_none->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_none_tenant2->id => [
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_override_category->id => [
                $cc1->id => '1',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_override_category1_1->id => [
                $cc1->id => '1',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_override_category1_2->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_override_category_deep->id => [
                $cc2->id => '4', $cat_deep->id => '1',
            ],
            $user_audience_1_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '2', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_audience_2->id => [
                $cc2->id => '2', $cat_deep->id => '1',
            ],
            $user_enrolled_1_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '3', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_enrolled_2->id => [
                $cc2->id => '3', $cat_deep->id => '1',
            ],
        ];

        foreach ($users as $user) {
            if (empty($CFG->disable_visibility_maps)) {
                $sql = (new \core\dml\sql('SELECT c.idnumber FROM {course} c'))
                    ->append($manager->sql_where_visible($user->id, 'c'), ' WHERE ');
                $actual = $DB->get_fieldset_sql($sql);
            } else {
                [$sql, $params] = totara_visibility_where($user->id, 'c.id', 'c.visible', 'c.audiencevisible', 'c');
                $sql = "SELECT c.idnumber
                 FROM {course} c
           INNER JOIN {context} ctx
                   ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                WHERE {$sql}";
                $params['contextlevel'] = CONTEXT_COURSE;
                $actual = $DB->get_fieldset_sql($sql, $params);
            }

            foreach ($all_courses as $course) {
                $useridnumber = $DB->get_field('user', 'idnumber', ['id' => $user->id]);
                $message = "User `{$useridnumber}` for course `{$course->idnumber}` ({$course->id}) is ";

                $context = \context_course::instance($course->id);

                if ($course->audiencevisible == COHORT_VISIBLE_ALL && (empty($user->tenantid) || $user->tenantid == $context->tenantid)) {
                    // The course is visible to everyone, no need for the check.
                    self::assertContains($course->idnumber, $actual, $message . 'expected');
                    self::assertTrue(totara_course_is_viewable($course->id, $user->id));
                    continue;
                }

                if (isset($expectedcourses[$user->id]) && in_array($course->id, $expectedcourses[$user->id])) {
                    // The course is visible to this user, no need for the cap checks.
                    self::assertContains($course->idnumber, $actual, $message . 'expected');
                    self::assertTrue(totara_course_is_viewable($course->id, $user->id));
                    continue;
                }

                if (in_array($course->idnumber, $actual)) {
                    // It's there, check we've got the capability
                    // This fails with visibility maps disabled for users with role overrides.
                    if (empty($CFG->disable_visibility_maps) || ($user->id != $user_override_category1_2->id && $user->id != $user_override_category_deep->id)) {
                        self::assertTrue(has_capability(self::CAP, $context, $user->id), $message . 'expected');
                        self::assertTrue(totara_course_is_viewable($course->id, $user->id));
                    }
                } else {
                    // Check they don't.
                    self::assertFalse(has_capability(self::CAP, $context, $user->id), $message . 'not expected');
                    self::assertFalse(totara_course_is_viewable($course->id, $user->id));
                }
            }

            $counts = $manager->get_visible_counts_for_all_categories($user->id);
            ksort($counts);
            ksort($map[$user->id]);
            self::assertSame($map[$user->id], $counts, "User `{$useridnumber}` mismapped");
            foreach ($map[$user->id] as $categoryid => $count) {
                self::assertCount($count, $manager->get_visible_in_category($categoryid, $user->id, ['id']));
            }
        }
    }

    public function test_complex_audience_based_setup_multitenancy_loose_with_maps_disabled() {
        global $CFG;
        $CFG->disable_visibility_maps = true;

        $this->test_complex_audience_based_setup_multitenancy_loose();

        unset($CFG->disable_visibility_maps);
    }

    public function test_complex_audience_based_setup_multitenancy_strict() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/core/totara.php');

        $CFG->audiencevisibility = 1;

        $gen = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $multitenancy */
        $multitenancy = $gen->get_plugin_generator('totara_tenant');

        $multitenancy->enable_tenants();
        set_config('tenantsisolated', 1);

        $tenant1 = $multitenancy->create_tenant();
        $tenant2 = $multitenancy->create_tenant();

        // Set up our users.
        $user_admin = $gen->create_user(['idnumber' => 'admin']);
        $user_site = $gen->create_user(['idnumber' => 'site']);
        // Allowed via category
        $user_category1 = $gen->create_user(['idnumber' => 'category1', 'tenantid' => $tenant1->id]);
        $user_category1_1 = $gen->create_user(['idnumber' => 'category1_1', 'tenantid' => $tenant1->id]);
        $user_category2 = $gen->create_user(['idnumber' => 'category2', 'tenantid' => $tenant2->id]);
        // Allowed directly via course
        $user_course_1 = $gen->create_user(['idnumber' => 'course_1', 'tenantid' => $tenant1->id]);
        $user_course_1_1_2 = $gen->create_user(['idnumber' => 'course_1_1_2', 'tenantid' => $tenant1->id]);
        // Allowed via audience
        $user_audience_1_2 = $gen->create_user(['idnumber' => 'audience_1_2', 'tenantid' => $tenant1->id]);
        $user_audience_2 = $gen->create_user(['idnumber' => 'audience_2', 'tenantid' => $tenant2->id]);
        // Allowed via enrolled.
        $user_enrolled_1_2 = $gen->create_user(['idnumber' => 'enrolled_1_2', 'tenantid' => $tenant1->id]);
        $user_enrolled_2 = $gen->create_user(['idnumber' => 'enrolled_2', 'tenantid' => $tenant2->id]);
        // Allowed view role overrides.
        $user_override_category = $gen->create_user(['idnumber' => 'override_category']);
        $user_override_category1_1 = $gen->create_user(['idnumber' => 'override_category1_1', 'tenantid' => $tenant1->id]);
        $user_override_category1_2 = $gen->create_user(['idnumber' => 'override_category1_2', 'tenantid' => $tenant1->id]);
        $user_override_category_deep = $gen->create_user(['idnumber' => 'override_category_deep', 'tenantid' => $tenant2->id]);
        // No special allowance.
        $user_none = $gen->create_user(['idnumber' => 'none']);
        $user_none_tenant2 = $gen->create_user(['idnumber' => 'none', 'tenantid' => $tenant2->id]);

        // Set up audiences.
        $audience1 = $gen->create_cohort();
        $audience2 = $gen->create_cohort();
        cohort_add_member($audience1->id, $user_audience_1_2->id);
        cohort_add_member($audience2->id, $user_audience_2->id);

        $context_system = \context_system::instance();

        // Create roles to allow, prevent, and prohibit.
        $allow_roleid = $gen->create_role();
        role_change_permission($allow_roleid, $context_system, self::CAP, CAP_ALLOW);
        $prevent_roleid = $gen->create_role();
        role_change_permission($prevent_roleid, $context_system, self::CAP, CAP_PREVENT);
        $prohibit_override_roleid = $gen->create_role();
        role_change_permission($prohibit_override_roleid, $context_system, self::CAP, CAP_ALLOW);

        $DB->set_field('course', 'idnumber', 'site', ['id' => SITEID]);

        $cc1 = $gen->create_category(['idnumber' => 'cc1', 'parent' => $tenant1->categoryid]);
        $cc1_1 = $gen->create_category(['idnumber' => 'cc1_1', 'parent' => $cc1->id, 'visible' => 1]);
        $cc1_1_1 = $gen->create_category(['idnumber' => 'cc1_1_1', 'parent' => $cc1_1->id]);
        $cc1_1_2 = $gen->create_category(['idnumber' => 'cc1_1_2', 'parent' => $cc1_1->id]);
        $cc1_2 = $gen->create_category(['idnumber' => 'cc1_2', 'parent' => $cc1->id, 'visible' => 0]);
        $cc1_2_1 = $gen->create_category(['idnumber' => 'cc1_2_1', 'parent' => $cc1_2->id]);
        $cc1_2_2 = $gen->create_category(['idnumber' => 'cc1_2_2', 'parent' => $cc1_2->id]);
        $cc2 = $gen->create_category(['idnumber' => 'cc2', 'parent' => $tenant2->categoryid]);
        $last = $cc2;
        for ($i = 0; $i <= 20; $i++) {
            $last = $gen->create_category(['idnumber' => 'cc2_'.$i, 'parent' => $last->id]);
        }
        $cat_deep = $last;

        $categories = [
            $cc1, $cc1_1, $cc1_1_1, $cc1_1_2, $cc1_2, $cc1_2_1, $cc1_2_2, $cc2, $cat_deep
        ];

        $count = 0;
        $all_courses = [];
        foreach ($categories as $cat) {
            $count += 1;
            $map = [
                1 => COHORT_VISIBLE_ALL,
                2 => COHORT_VISIBLE_ENROLLED,
                3 => COHORT_VISIBLE_AUDIENCE,
                4 => COHORT_VISIBLE_NOUSERS,
            ];
            foreach ($map as $count => $vis) {
                $all_courses[] = $gen->create_course([
                    'idnumber' => $cat->idnumber . '_course_'.$count,
                    'audiencevisible' => $vis,
                    'category' => $cat->id
                ]);
            }
        }

        $expectedcourses = [
            $user_audience_1_2->id => [],
            $user_audience_2->id => [],
        ];

        $sql = $DB->sql_like('idnumber', ':cc1_2');
        $params = [
            'cc1_2' => 'cc1_2_course_%',
        ];
        $courses = $DB->get_records_sql_menu("SELECT id, audiencevisible FROM {course} WHERE {$sql}", $params);
        foreach ($courses as $courseid => $audiencevisible) {
            totara_cohort_add_association($audience1->id, $courseid, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
            if ($audiencevisible == COHORT_VISIBLE_AUDIENCE) {
                $expectedcourses[$user_audience_1_2->id][] = $courseid;
                $expectedcourses[$user_enrolled_1_2->id][] = $courseid;
            }
            $gen->enrol_user($user_enrolled_1_2->id, $courseid);
            if ($audiencevisible == COHORT_VISIBLE_ENROLLED) {
                $expectedcourses[$user_enrolled_1_2->id][] = $courseid;
            }
        }

        $sql = $DB->sql_like('idnumber', ':cc2');
        $params = ['cc2' => 'cc2_course_%',];
        $courses = $DB->get_records_sql_menu("SELECT id, audiencevisible FROM {course} WHERE {$sql}", $params);
        foreach ($courses as $courseid => $audiencevisible) {
            totara_cohort_add_association($audience2->id, $courseid, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);
            if ($audiencevisible == COHORT_VISIBLE_AUDIENCE) {
                $expectedcourses[$user_audience_2->id][] = $courseid;
                $expectedcourses[$user_enrolled_2->id][] = $courseid;
            }
            $gen->enrol_user($user_enrolled_2->id, $courseid);
            if ($audiencevisible == COHORT_VISIBLE_ENROLLED) {
                $expectedcourses[$user_enrolled_2->id][] = $courseid;
            }
        }

        // Assign the allow roles.
        $CFG->siteadmins = $CFG->siteadmins . ',' . $user_admin->id;
        role_assign($allow_roleid, $user_site->id, $context_system);
        role_assign($allow_roleid, $user_category1->id, \context_coursecat::instance($cc1->id));
        role_assign($allow_roleid, $user_category1_1->id, \context_coursecat::instance($cc1_1->id));
        role_assign($allow_roleid, $user_category2->id, \context_coursecat::instance($cc2->id));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_course_2'])
        ));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_course_3'])
        ));
        role_assign($allow_roleid, $user_course_1->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_course_4'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_course_2'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_course_3'])
        ));
        role_assign($allow_roleid, $user_course_1_1_2->id, \context_course::instance(
            $DB->get_field('course', 'id', ['idnumber' => 'cc1_1_2_course_4'])
        ));

        // Assign the allow role but prohibit at cat 2
        role_assign($prohibit_override_roleid, $user_override_category1_2->id, $context_system);
        role_assign($prohibit_override_roleid, $user_override_category_deep->id, $context_system);
        // Assign the allow role but prohibit at depth.
        assign_capability(self::CAP, CAP_PROHIBIT, $prohibit_override_roleid, \context_coursecat::instance($cc1_2->id));
        assign_capability(self::CAP, CAP_PROHIBIT, $prohibit_override_roleid, \context_coursecat::instance($cat_deep->id));

        // Assign the override roles.
        role_assign($prevent_roleid, $user_override_category->id, $context_system);
        role_assign($prevent_roleid, $user_override_category1_1->id, \context_coursecat::instance($cc1_1->id));
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc2->id));
        assign_capability(self::CAP, CAP_ALLOW, $prevent_roleid, \context_coursecat::instance($cc1_1->id));


        $manager = \totara_core\visibility_controller::course();
        $manager->map()->recalculate_complete_map();

        self::assertTrue(is_siteadmin($user_admin->id));

        $users = [
            $user_admin, $user_site,
            $user_category1, $user_category1_1, $user_category2, $user_course_1,
            $user_none, $user_none_tenant2,
            $user_override_category1_1, $user_override_category, $user_override_category1_2, $user_override_category_deep,
            $user_audience_1_2, $user_audience_2,
            $user_enrolled_1_2, $user_enrolled_2,
        ];

        $map = [
            $user_admin->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '4', $cc1_2_1->id => '4', $cc1_2_2->id => '4',
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_site->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '4', $cc1_2_1->id => '4', $cc1_2_2->id => '4',
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_category1->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '4', $cc1_2_1->id => '4', $cc1_2_2->id => '4',
            ],
            $user_category1_1->id => [
                $cc1->id => '1',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_category2->id => [
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_course_1->id => [
                $cc1->id => '4',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_course_1_1_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_none->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_none_tenant2->id => [
                $cc2->id => '1', $cat_deep->id => '1',
            ],
            $user_override_category->id => [
                $cc1->id => '1',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
                $cc2->id => '4', $cat_deep->id => '4',
            ],
            $user_override_category1_1->id => [
                $cc1->id => '1',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_override_category1_2->id => [
                $cc1->id => '4',
                $cc1_1->id => '4', $cc1_1_1->id => '4', $cc1_1_2->id => '4',
                $cc1_2->id => '1', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_override_category_deep->id => [
                $cc2->id => '4', $cat_deep->id => '1',
            ],
            $user_audience_1_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '2', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_audience_2->id => [
                $cc2->id => '2', $cat_deep->id => '1',
            ],
            $user_enrolled_1_2->id => [
                $cc1->id => '1',
                $cc1_1->id => '1', $cc1_1_1->id => '1', $cc1_1_2->id => '1',
                $cc1_2->id => '3', $cc1_2_1->id => '1', $cc1_2_2->id => '1',
            ],
            $user_enrolled_2->id => [
                $cc2->id => '3', $cat_deep->id => '1',
            ],
        ];

        foreach ($users as $user) {
            if (empty($CFG->disable_visibility_maps)) {
                $sql = (new \core\dml\sql('SELECT c.idnumber FROM {course} c'))
                    ->append($manager->sql_where_visible($user->id, 'c'), ' WHERE ');
                $actual = $DB->get_fieldset_sql($sql);
            } else {
                [$sql, $params] = totara_visibility_where($user->id, 'c.id', 'c.visible', 'c.audiencevisible', 'c');
                $sql = "SELECT c.idnumber
                 FROM {course} c
           INNER JOIN {context} ctx
                   ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                WHERE {$sql}";
                $params['contextlevel'] = CONTEXT_COURSE;
                $actual = $DB->get_fieldset_sql($sql, $params);
            }

            foreach ($all_courses as $course) {
                $useridnumber = $DB->get_field('user', 'idnumber', ['id' => $user->id]);
                $message = "User `{$useridnumber}` for course `{$course->idnumber}` ({$course->id}) is ";

                $context = \context_course::instance($course->id);

                if ($course->audiencevisible == COHORT_VISIBLE_ALL && (empty($user->tenantid) || $user->tenantid == $context->tenantid)) {
                    // The course is visible to everyone, no need for the check.
                    self::assertContains($course->idnumber, $actual, $message . 'expected');
                    self::assertTrue(totara_course_is_viewable($course->id, $user->id));
                    continue;
                }

                if (isset($expectedcourses[$user->id]) && in_array($course->id, $expectedcourses[$user->id])) {
                    // The course is visible to this user, no need for the cap checks.
                    self::assertContains($course->idnumber, $actual, $message . 'expected');
                    self::assertTrue(totara_course_is_viewable($course->id, $user->id));
                    continue;
                }

                if (in_array($course->idnumber, $actual)) {
                    // It's there, check we've got the capability
                    self::assertTrue(has_capability(self::CAP, $context, $user->id), $message . 'expected');
                    self::assertTrue(totara_course_is_viewable($course->id, $user->id));
                } else {
                    // Check they don't.
                    self::assertFalse(has_capability(self::CAP, $context, $user->id), $message . 'not expected');
                    self::assertFalse(totara_course_is_viewable($course->id, $user->id));
                }
            }

            $counts = $manager->get_visible_counts_for_all_categories($user->id);
            ksort($counts);
            ksort($map[$user->id]);
            self::assertSame($map[$user->id], $counts, "User `{$useridnumber}` mismapped");
            foreach ($map[$user->id] as $categoryid => $count) {
                self::assertCount($count, $manager->get_visible_in_category($categoryid, $user->id, ['id']));
            }
        }
    }

    public function test_complex_audience_based_setup_multitenancy_strict_with_maps_disabled() {
        global $CFG;
        $CFG->disable_visibility_maps = true;

        $this->test_complex_audience_based_setup_multitenancy_strict();

        unset($CFG->disable_visibility_maps);
    }
}
