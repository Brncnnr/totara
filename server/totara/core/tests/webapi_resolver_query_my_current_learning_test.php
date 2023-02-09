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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara current learning query resolver
 */
class totara_core_webapi_resolver_query_my_current_learning_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     * Create some users for testing.
     * @return []
     */
    private function create_faux_users() {
        $users = [];
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        return $users;
    }

    /**
     * Create some courses and assign some users for testing.
     * @return []
     */
    private function create_faux_courses(array $users = []) {
        if (empty($users)) {
            $users = $this->create_faux_users();
        }

        $courses = [];
        $courses[] = $this->getDataGenerator()->create_course(['shortname' => 'c1', 'fullname' => 'course1', 'summary' => 'first course']);
        $courses[] = $this->getDataGenerator()->create_course(['shortname' => 'c2', 'fullname' => 'course2', 'summary' => 'second course']);
        $courses[] = $this->getDataGenerator()->create_course(['shortname' => 'c3', 'fullname' => 'course3', 'summary' => 'third course']);

        $this->getDataGenerator()->enrol_user($users[0]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[1]->id, 'student', 'manual');

        return $courses;
    }

    /**
     *
     * Create some programs and assign some users for testing.
     * @return []
     */
    private function create_faux_programs(array $users = []) {
        if (empty($users)) {
            $users = $this->create_faux_users();
        }

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();
        $c4 = $this->getDataGenerator()->create_course();

        $programs = [];
        $programs[] = $prog_gen->create_program(['shortname' => 'p1', 'fullname' => 'prog1', 'summary' => 'first prog']);
        $programs[] = $prog_gen->create_program(['shortname' => 'p2', 'fullname' => 'prog2', 'summary' => 'second prog']);
        $programs[] = $prog_gen->create_program(['shortname' => 'p3', 'fullname' => 'prog3', 'summary' => 'third prog']);

        $prog_gen->add_courses_and_courseset_to_program($programs[0], [[$c1, $c2], [$c3]], CERTIFPATH_STD);
        $prog_gen->add_courses_and_courseset_to_program($programs[1], [[$c3], [$c4]], CERTIFPATH_STD);

        $prog_gen->assign_program($programs[0]->id, [$users[0]->id, $users[1]->id]);
        $prog_gen->assign_program($programs[1]->id, [$users[1]->id]);

        return $programs;
    }

    /**
     *
     * Create some certifications and assign some users for testing.
     * @return []
     */
    private function create_faux_certifications(array $users = []) {
        if (empty($users)) {
            $users = $this->create_faux_users();
        }

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();
        $c4 = $this->getDataGenerator()->create_course();
        $c5 = $this->getDataGenerator()->create_course();
        $c6 = $this->getDataGenerator()->create_course();

        $certifications = [];
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c1', 'fullname' => 'cert1', 'summary' => 'first cert']);
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c2', 'fullname' => 'cert2', 'summary' => 'second cert']);
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c3', 'fullname' => 'cert3', 'summary' => 'third cert']);

        $prog_gen->add_courses_and_courseset_to_program($certifications[0], [[$c1, $c2], [$c3]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certifications[0], [[$c1], [$c3]], CERTIFPATH_RECERT);

        $prog_gen->add_courses_and_courseset_to_program($certifications[1], [[$c4, $c5], [$c6]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certifications[1], [[$c4], [$c6]], CERTIFPATH_RECERT);

        $prog_gen->assign_program($certifications[0]->id, [$users[0]->id, $users[1]->id]);
        $prog_gen->assign_program($certifications[1]->id, [$users[1]->id]);

        return $certifications;
    }

    /**
     * Create some courses and assign some users for testing.
     * @return []
     */
    private function create_faux_learning_items(array $users = []) {
        if (empty($users)) {
            $users = $this->create_faux_users();
        }

        $items = [];
        $items['courses'] = $this->create_faux_courses($users);
        $items['programs'] = $this->create_faux_programs($users);
        $items['certifications'] = $this->create_faux_certifications($users);

        return $items;
    }

    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $this->resolve_graphql_query('totara_core_my_current_learning', []);
    }

    /**
     * Test the results of the query when the current user is logged in as the guest user.
     */
    public function test_resolve_guest_user() {
        $this->setGuestUser();

        $results = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertEmpty($results); // Guests shouldn't have any items.
    }

    /**
     * Test the results of the query when the current user is the site administrator.
     */
    public function test_resolve_admin_user() {
        $this->setAdminUser();

        $results = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertEmpty($results);
    }

    /**
     * Test the results of the query when a user has no current learning items.
     */
    public function test_resolve_no_current_learning() {
        $users = $this->create_faux_users();
        $user = array_pop($users);
        $this->setUser($user);

        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertEmpty($items);
    }

    /**
     * Test the results of the query match expectations for a course learning item.
     */
    public function test_resolve_learning_item_course() {
        $users = $this->create_faux_users();
        $courses = $this->create_faux_courses($users);

        // Check that user 0 has one learning item as expected.
        $this->setUser($users[0]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(1, $items);

        // Do some checks on the item to make sure it's what we are expecting.
        $item = array_pop($items);
        $this->assertInstanceOf('\totara_core\user_learning\item', $item);
        $this->assertEquals('core_course', $item->get_component());
        $this->assertEquals($courses[0]->id, $item->id);
        $this->assertEquals($courses[0]->fullname, $item->fullname);
        $this->assertEquals($courses[0]->shortname, $item->shortname);
        $this->assertEquals($courses[0]->summary, $item->description);

        // Check that user 1 has two learning items as expected.
        $this->setUser($users[1]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(2, $items);

        // Check that user 2 has three learning items as expected.
        $this->setUser($users[2]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(0, $items);
    }

    /**
     * Test the results of the query when a user has no current learning items.
     */
    public function test_resolve_learning_item_program() {
        $users = $this->create_faux_users();
        $programs = $this->create_faux_programs($users);

        // Check that user 0 has one learning item as expected.
        $this->setUser($users[0]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(1, $items);

        // Do some checks on the item to make sure it's what we are expecting.
        $item = array_pop($items);
        $this->assertInstanceOf('\totara_core\user_learning\item', $item);
        $this->assertEquals('totara_program', $item->get_component());
        $this->assertEquals($programs[0]->id, $item->id);
        $this->assertEquals($programs[0]->fullname, $item->fullname);
        $this->assertEquals($programs[0]->shortname, $item->shortname);
        $this->assertEquals($programs[0]->summary, $item->description);

        // Check that user 1 has two learning items as expected.
        $this->setUser($users[1]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(2, $items);

        // Check that user 2 has three learning items as expected.
        $this->setUser($users[2]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(0, $items);
    }

    /**
     * Test the results of the query when a user has no current learning items.
     */
    public function test_resolve_learning_item_certification() {
        $users = $this->create_faux_users();
        $certifications = $this->create_faux_certifications($users);

        // Check that user 0 has one learning item as expected.
        $this->setUser($users[0]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(1, $items);

        // Do some checks on the item to make sure it's what we are expecting.
        $item = array_pop($items);
        $this->assertInstanceOf('\totara_core\user_learning\item', $item);
        $this->assertEquals('totara_certification', $item->get_component());
        $this->assertEquals($certifications[0]->id, $item->id);
        $this->assertEquals($certifications[0]->fullname, $item->fullname);
        $this->assertEquals($certifications[0]->shortname, $item->shortname);
        $this->assertEquals($certifications[0]->summary, $item->description);

        // Check that user 1 has two learning items as expected.
        $this->setUser($users[1]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(2, $items);

        // Check that user 2 has three learning items as expected.
        $this->setUser($users[2]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(0, $items);
    }

    /**
     * Test the results of the query when a user has no current learning items.
     */
    public function test_resolve_learning_item_mixed() {
        $users = $this->create_faux_users();
        $items = $this->create_faux_learning_items($users);

        // Check that user 0 has one learning item as expected.
        $this->setUser($users[0]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(3, $items);

        // Just a quick check that they're all learning items.
        foreach ($items as $item) {
            $this->assertInstanceOf('\totara_core\user_learning\item', $item);
        }

        // Check that user 1 has two learning items as expected.
        $this->setUser($users[1]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(6, $items);

        // Check that user 2 has three learning items as expected.
        $this->setUser($users[2]);
        $items = $this->resolve_graphql_query('totara_core_my_current_learning', []);
        $this->assertCount(0, $items);
    }

    /**
     * Test the results of the AJAX query through the GraphQL stack.
     */
    public function test_ajax_query() {
        $users = $this->create_faux_users();
        $items = $this->create_faux_learning_items($users);

        $this->setUser($users[0]);
        $result = $this->execute_graphql_operation('totara_core_my_current_learning', []);
        $data = $result->toArray()['data'];
        $this->assertCount(3, $data['totara_core_my_current_learning']);

        // Note: This relies on the alphabetical ordering.
        $expected = [
            'totara_core_my_current_learning' => [
                0 => [
                    'id' => "{$items['certifications'][0]->id}",
                    'itemtype' => 'certification',
                    'itemcomponent' => 'totara_certification',
                    'shortname' => $items['certifications'][0]->shortname,
                    'fullname' => $items['certifications'][0]->fullname,
                    'description' => $items['certifications'][0]->summary,
                    'description_format' => 'HTML',
                    'progress' => 0.0,
                    'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['certifications'][0]->id}",
                    'duedate' => null,
                    'duedate_state' => null,
                    'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_certification/1/defaultimage'
                ],
                1 => [
                    'id' => "{$items['courses'][0]->id}",
                    'itemtype' => "course",
                    'itemcomponent' => "core_course",
                    'shortname' => $items['courses'][0]->shortname,
                    'fullname' => $items['courses'][0]->fullname,
                    // summaryformat is FORMAT_MOODLE, means text_to_html
                    'description' => '<div class="text_to_html">'.$items['courses'][0]->summary.'</div>',
                    'description_format' => 'HTML',
                    'progress' => null,
                    'url_view' => "https://www.example.com/moodle/course/view.php?id={$items['courses'][0]->id}",
                    'duedate' => null,
                    'duedate_state' => null,
                    'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage'
                ],
                2 => [
                    'id' => "{$items['programs'][0]->id}",
                    'itemtype' => "program",
                    'itemcomponent' => "totara_program",
                    'shortname' => $items['programs'][0]->shortname,
                    'fullname' => $items['programs'][0]->fullname,
                    'description' => $items['programs'][0]->summary,
                    'description_format' => 'HTML',
                    'progress' => 0.0,
                    'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['programs'][0]->id}",
                    'duedate' => null,
                    'duedate_state' => null,
                    'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_program/1/defaultimage'
                ]
            ]
        ];
        $this->assertSame($expected, $data);

        $this->setUser($users[1]);
        $result = \totara_webapi\graphql::execute_operation(
            \core\webapi\execution_context::create('ajax', 'totara_core_my_current_learning'), []
        );
        $data = $result->toArray()['data'];
        $this->assertCount(6, $data['totara_core_my_current_learning']);

        $expected = [
            'totara_core_my_current_learning' => [
                0 => [
                    'id' => "{$items['certifications'][0]->id}",
                    'itemtype' => 'certification',
                    'itemcomponent' => 'totara_certification',
                    'shortname' => 'c1',
                    'fullname' => 'cert1',
                    'description' => 'first cert',
                    'description_format' => 'HTML',
                    'progress' => 0.0,
                    'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['certifications'][0]->id}",
                    'duedate' => null,
                    'duedate_state' => null,
                    'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_certification/1/defaultimage'
                ],
                1 => [
                    'id' => "{$items['certifications'][1]->id}",
                    'itemtype' => 'certification',
                    'itemcomponent' => 'totara_certification',
                    'shortname' => 'c2',
                    'fullname' => 'cert2',
                    'description' => 'second cert',
                    'description_format' => 'HTML',
                    'progress' => 0.0,
                    'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['certifications'][1]->id}",
                    'duedate' => null,
                    'duedate_state' => null,
                    'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_certification/1/defaultimage'
                ],
                2 => [
                    'id' => "{$items['courses'][0]->id}",
                    'itemtype' => "course",
                    'itemcomponent' => "core_course",
                    'shortname' => "c1",
                    'fullname' => "course1",
                    'description' => "<div class=\"text_to_html\">first course</div>",
                    'description_format' => 'HTML',
                    'progress' => null,
                    'url_view' => "https://www.example.com/moodle/course/view.php?id={$items['courses'][0]->id}",
                    'duedate' => null,
                    'duedate_state' => null,
                    'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage'
                ],
                3 => [
                    'id' => "{$items['courses'][1]->id}",
                    'itemtype' => "course",
                    'itemcomponent' => "core_course",
                    'shortname' => "c2",
                    'fullname' => "course2",
                    'description' => "<div class=\"text_to_html\">second course</div>",
                    'description_format' => 'HTML',
                    'progress' => null,
                    'url_view' => "https://www.example.com/moodle/course/view.php?id={$items['courses'][1]->id}",
                    'duedate' => null,
                    'duedate_state' => null,
                    'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage'
                ],
                4 => [
                    'id' => "{$items['programs'][0]->id}",
                    'itemtype' => "program",
                    'itemcomponent' => "totara_program",
                    'shortname' => "p1",
                    'fullname' => "prog1",
                    'description' => "first prog",
                    'description_format' => 'HTML',
                    'progress' => 0.0,
                    'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['programs'][0]->id}",
                    'duedate' => null,
                    'duedate_state' => null,
                    'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_program/1/defaultimage'
                ],
                5 => [
                    'id' => "{$items['programs'][1]->id}",
                    'itemtype' => "program",
                    'itemcomponent' => "totara_program",
                    'shortname' => "p2",
                    'fullname' => "prog2",
                    'description' => "second prog",
                    'description_format' => 'HTML',
                    'progress' => 0.0,
                    'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['programs'][1]->id}",
                    'duedate' => null,
                    'duedate_state' => null,
                    'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_program/1/defaultimage'
                ]
            ]
        ];
        $this->assertSame($expected, $data);

        $this->setUser($users[2]);
        $result = \totara_webapi\graphql::execute_operation(
            \core\webapi\execution_context::create('ajax', 'totara_core_my_current_learning'), []
        );
        $data = $result->toArray()['data'];
        $this->assertCount(0, $data['totara_core_my_current_learning']);

        $expected = [
            'totara_core_my_current_learning' => []
        ];
        $this->assertSame($expected, $data);
    }

    /**
     * Test presence of duedate state
     */
    public function test_duedate_state() {
        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program1 = $prog_gen->create_program(['shortname' => 'p1', 'fullname' => 'prog1', 'summary' => 'first prog']);
        $prog_gen->add_courses_and_courseset_to_program($program1, [[$course1]], CERTIFPATH_STD);
        $prog_gen->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id,
            array('completiontime' => date('d/m/Y', strtotime('+5 days'))), true);

        $this->setUser($user1);
        $result = \totara_webapi\graphql::execute_operation(
            \core\webapi\execution_context::create('ajax', 'totara_core_my_current_learning'), []
        );
        $data = $result->toArray()['data'];
        $record = $data['totara_core_my_current_learning'][0];
        $this->assertEquals(strtotime(date('Y-m-d', strtotime('+5 days'))), $record['duedate']);
        $this->assertEquals('danger', $record['duedate_state']);
    }

}
