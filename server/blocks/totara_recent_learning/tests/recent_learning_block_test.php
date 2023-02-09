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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package block_totara_recent_learning
 */
defined('MOODLE_INTERNAL') || die();

use core\record\tenant;
use core_phpunit\testcase;

class block_totara_recent_learning_testcase extends testcase {

    private const CAP1 = 'moodle/course:viewhiddencourses';
    private const CAP2 = 'totara/coursecatalog:manageaudiencevisibility';

    /**
     *   Scenario:
     *     1. test site user
     *     2. test tenancy user
     *     3. tenancy user has the 'viewhiddencourses' capability
     *     4. audience visibility is switched on
     *   Outcomes:
     *     1. For site users the results include hidden courses.
     *     2. For tenant members the courses found are only linked to the tenancy
     *        the user belongs to.
     *     3. For tenant members the results include hidden courses because of capability.
     */
    public function test_content_multitenancy_cap1_scenario1() {
        set_config('audiencevisibility', '1');
        $this->course_search(6, 3, [self::CAP1]);
    }

    /**
     *   Scenario:
     *     1. test site user
     *     2. test tenancy user
     *     3. tenancy user has the 'viewhiddencourses' capability
     *     4. audience visibility is switched off
     *   Outcome:
     *     1. For site users the results exclude hidden courses.
     *     2. For tenant members the courses found are only linked to the tenancy
     *        the user belongs to.
     *     3. For tenant members the results include hidden courses because of capability.
     */
    public function test_course_search_multitenancy_cap1_scenario2() {
        set_config('audiencevisibility', '0');
        $this->course_search(4, 3, [self::CAP1]);
    }

    /**
     *   Scenario:
     *     1. test site user
     *     2. test tenancy user
     *     3. tenancy user has the 'totara/coursecatalog:manageaudiencevisibility' capability
     *     4. audience visibility is switched on
     *   Outcome:
     *     1. For site users the results include hidden courses.
     *     2. For tenant members the courses found are only linked to the tenancy
     *        the user belongs to.
     *     3. For tenant members the results include hidden courses because of capability.
     */
    public function test_course_search_multitenancy_cap2_scenario_1() {
        set_config('audiencevisibility', '1');
        $this->course_search(6, 3, [self::CAP2]);
    }

    /**
     *   Scenario:
     *     1. test site user
     *     2. test tenancy user
     *     3. tenancy user has the 'totara/coursecatalog:manageaudiencevisibility' capability
     *     4. audience visibility is switched off
     *   Outcome:
     *     1. For site users the results exclude hidden courses.
     *     2. For tenant members the courses found are only linked to the tenancy
     *        the user belongs to.
     *     3. For tenant members the results exclude hidden courses.
     */
    public function test_course_search_multitenancy_cap2_scenario_2() {
        set_config('audiencevisibility', '0');
        $this->course_search(4, 2, [self::CAP2]);
    }

    /**
     *   Scenario:
     *     1. test site user
     *     2. test tenancy user
     *     3. audience visibility is switched on
     *   Outcome:
     *     1. For site users the results include hidden courses.
     *     2. For tenant members the courses found are only linked to the tenancy
     *        the user belongs to.
     *     3. For tenant members the results include hidden courses.
     */
    public function test_course_search_multitenancy_nocap_scenario1() {
        set_config('audiencevisibility', '1');
        $this->course_search(6, 3, []);
    }

    /**
     *   Scenario:
     *     1. test site user
     *     2. test tenancy user
     *     3. audience visibility is switched off
     *   Outcome:
     *     1. For site users the results exclude hidden courses.
     *     2. For tenant members the courses found are only linked to the tenancy
     *        the user belongs to.
     *     3. For tenant members the results exclude hidden courses.
     */
    public function test_course_search_multitenancy_nocap_scenario2() {
        set_config('audiencevisibility', '0');
        $this->course_search(4, 2, []);
    }

    protected function course_search(int $expected_count_site, int $expected_count_tenant, array $allowed_caps) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
        require_once($CFG->dirroot . '/blocks/totara_recent_learning/block_totara_recent_learning.php');

        $gen = $this->getDataGenerator();

        // Create tenants - this will also create the top level categories that we need.
        /** @var totara_tenant_generator $tenant_gen */
        $tenant_gen = $gen->get_plugin_generator('totara_tenant');
        $tenant_gen->enable_tenants();

        /** @var tenant[] $tenants */
        $tenants = [
            $tenant1 = $tenant_gen->create_tenant(),
            $tenant2 = $tenant_gen->create_tenant(),
        ];

        // Create users
        $users = [
            $gen->create_user(['idnumber' => 'site']),
            $user_tenant1 = $gen->create_user(['idnumber' => 'site', 'tenantid' => $tenant1->id, 'tenantdomainmanager' => $tenant1->idnumber]),
            $user_tenant2 = $gen->create_user(['idnumber' => 'site', 'tenantid' => $tenant2->id, 'tenantdomainmanager' => $tenant2->idnumber]),
        ];

        $context_system = \context_system::instance();

        // Assign the audience visibility capability to tenant users.
        $allow_roleid = $gen->create_role();

        foreach ($allowed_caps as $cap) {
            role_change_permission($allow_roleid, $context_system, $cap, CAP_ALLOW);
        }
        role_assign($allow_roleid, $user_tenant1->id, $context_system);
        role_assign($allow_roleid, $user_tenant2->id, $context_system);

        // Create a course in each tenant category.
        $completion_gen = $gen->get_plugin_generator('core_completion');
        $courses = [];
        foreach ($tenants as $tenant) {
            $courses[$tenant->id] = [
                $gen->create_course([
                    'idnumber' => $tenant->idnumber . '_visible_1',
                    'visible' => 1,
                    'category' => $tenant->categoryid
                ]),
                $gen->create_course([
                    'idnumber' => $tenant->idnumber . '_visible_2',
                    'visible' => 1,
                    'category' => $tenant->categoryid
                ]),
                $gen->create_course([
                    'idnumber' => $tenant->idnumber . '_hidden',
                    'visible' => 0,
                    'category' => $tenant->categoryid
                ]),
            ];

            // Enable completion tracking.
            $completion_gen->enable_completion_tracking($courses[$tenant->id][0]);
            $completion_gen->enable_completion_tracking($courses[$tenant->id][1]);
            $completion_gen->enable_completion_tracking($courses[$tenant->id][2]);


            // Enroll and complete course.
            foreach ($users as $user) {
                $gen->enrol_user($user->id, $courses[$tenant->id][0]->id);
                $gen->enrol_user($user->id, $courses[$tenant->id][1]->id);
                $gen->enrol_user($user->id, $courses[$tenant->id][2]->id);
                $completion_gen->complete_course($courses[$tenant->id][0], $user);
                $completion_gen->complete_course($courses[$tenant->id][1], $user);
                $completion_gen->complete_course($courses[$tenant->id][2], $user);
            }
        }

        foreach ($users as $user) {
            $this->setUser($user);
            cache_helper::purge_all();

            $block_instance = new block_totara_recent_learning();
            $content = $block_instance->get_content();

            $found = 0;
            foreach ($courses as $tenant_id => $tenant_courses) {
                foreach ($tenant_courses as $tenant_course) {
                    if ((empty($user->tenantid) || $user->tenantid == $tenant_id)
                        && stripos($content->text, $tenant_course->fullname)) {
                        ++$found;
                    }
                }
            }

            if (empty($user->tenantid)) {
                $this->assertEquals($expected_count_site, $found, 'Invalid recent learning content');
            } else {
                $this->assertEquals($expected_count_tenant, $found, 'Invalid recent learning content');
            }
        }
    }

}