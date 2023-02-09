<?php
/**
 * This file is part of Totara LMS
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
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use core_phpunit\testcase;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara user learning query resolver
 */
class totara_core_webapi_resolver_query_user_learning_testcase extends testcase {

    use webapi_phpunit_helper;

    /**
     * Create some users for testing.
     *
     * @return array
     */
    private function create_users() {
        $users = [];
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        return $users;
    }

    /**
     * Create some courses and assign some users for testing.
     *
     * @return array
     */
    private function create_courses(array $users = []) {
        if (empty($users)) {
            $users = $this->create_users();
        }

        $courses = [];
        $courses[] = $this->getDataGenerator()->create_course([
            'shortname' => 'c1',
            'fullname' => 'course1',
            'summary' => 'first course',
            'summaryformat' => FORMAT_HTML,
        ]);
        $courses[] = $this->getDataGenerator()->create_course([
            'shortname' => 'c2',
            'fullname' => 'course2',
            'summary' => 'second course',
            'summaryformat' => FORMAT_HTML,
        ]);
        $courses[] = $this->getDataGenerator()->create_course([
            'shortname' => 'c3',
            'fullname' => 'course3',
            'summary' => 'third course',
            'summaryformat' => FORMAT_HTML,
        ]);

        $this->getDataGenerator()->enrol_user($users[0]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[1]->id, 'student', 'manual');

        return $courses;
    }

    /**
     *
     * Create some programs and assign some users for testing.
     *
     * @return array
     */
    private function create_programs(array $users = []) {
        if (empty($users)) {
            $users = $this->create_users();
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
     *
     * @return array
     */
    private function create_certifications(array $users = []) {
        if (empty($users)) {
            $users = $this->create_users();
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
     *
     * @return array
     */
    private function create_learning_items(array $users = []) {
        if (empty($users)) {
            $users = $this->create_users();
        }

        $items = [];
        $items['courses'] = $this->create_courses($users);
        $items['programs'] = $this->create_programs($users);
        $items['certifications'] = $this->create_certifications($users);

        return $items;
    }

    /**
     * @param $result
     */
    private function assert_result($result, int $count): void {
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('next_cursor', $result);
        $this->assertCount($count, $result['items']);
        $this->assertEquals($count, $result['total']);
        $this->assertEquals('', $result['next_cursor']);
    }

    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        try {
            $this->resolve_graphql_query('totara_core_user_learning_items', []);
            $this->fail('Expected exception');
        } catch (Exception $e) {
            $this->assertEquals(
                'Course or activity not accessible. (You are not logged in)',
                $e->getMessage()
            );
        }
    }

    /**
     * Test the results of the query when the current user is logged in as the guest user.
     */
    public function test_resolve_guest_user() {
        $this->setGuestUser();

        $result = $this->resolve_graphql_query('totara_core_user_learning_items', []);
        $this->assert_result($result, 0);
    }

    /**
     * Test the results of the query when the current user is the site administrator.
     */
    public function test_resolve_admin_user() {
        $this->setAdminUser();

        $result = $this->resolve_graphql_query('totara_core_user_learning_items', []);
        $this->assert_result($result, 0);
    }

    /**
     * Test the results of the query when a user has no current learning items.
     */
    public function test_resolve_no_user_learning() {
        $users = $this->create_users();
        $user = array_pop($users);
        $this->setUser($user);

        $result = $this->resolve_graphql_query('totara_core_user_learning_items', []);
        $this->assert_result($result, 0);
    }

    /**
     * Test the results of the query match expectations for a course learning item.
     */
    public function test_resolve_learning_item_course() {
        $users = $this->create_users();
        $courses = $this->create_courses($users);

        // Check that user 0 has 1 learning item as expected.
        $this->setUser($users[0]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items', []);
        $this->assert_result($result, 1);

        // Do some checks on the item to make sure it's what we are expecting.
        $item = array_pop($result['items']);
        $this->assertInstanceOf('\totara_core\user_learning\item', $item);
        $this->assertEquals('core_course', $item->get_component());
        $this->assertEquals($courses[0]->id, $item->id);
        $this->assertEquals($courses[0]->fullname, $item->fullname);
        $this->assertEquals($courses[0]->shortname, $item->shortname);
        $this->assertEquals($courses[0]->summary, $item->description);

        // Check that user 1 has 2 learning items as expected.
        $this->setUser($users[1]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items', []);
        $this->assert_result($result, 2);

        // Check that user 2 has 0 learning items as expected.
        $this->setUser($users[2]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items', []);
        $this->assert_result($result, 0);
    }

    /**
     * Test the results of the query when a user has no current learning items.
     */
    public function test_resolve_learning_item_program() {
        $users = $this->create_users();
        $programs = $this->create_programs($users);

        // Check that user 0 has one learning item as expected.
        $this->setUser($users[0]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items', [
            'input' => [
                'filters' => [
                    'type' => 'PROGRAM'
                ],
            ]
        ]);
        $this->assert_result($result, 1);

        // Do some checks on the item to make sure it's what we are expecting.
        $item = array_pop($result['items']);
        $this->assertInstanceOf('\totara_core\user_learning\item', $item);
        $this->assertEquals('totara_program', $item->get_component());
        $this->assertEquals($programs[0]->id, $item->id);
        $this->assertEquals($programs[0]->fullname, $item->fullname);
        $this->assertEquals($programs[0]->shortname, $item->shortname);
        $this->assertEquals($programs[0]->summary, $item->description);

        // Check that user 1 has two learning items as expected.
        $this->setUser($users[1]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items', [
            'input' => [
                'filters' => [
                    'type' => 'PROGRAM'
                ],
            ]
        ]);
        $this->assert_result($result, 2);

        // Check that user 2 has three learning items as expected.
        $this->setUser($users[2]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items', [
            'input' => [
                'filters' => [
                    'type' => 'PROGRAM'
                ],
            ]
        ]);
        $this->assert_result($result, 0);
    }

    /**
     * Test the results of the query when a user has no current learning items.
     */
    public function test_resolve_learning_item_certification() {
        $users = $this->create_users();
        $certifications = $this->create_certifications($users);

        // Check that user 0 has one learning item as expected.
        $this->setUser($users[0]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items', [
            'input' => [
                'filters' => [
                    'type' => 'CERTIFICATION'
                ],
            ]
        ]);
        $this->assert_result($result, 1);

        // Do some checks on the item to make sure it's what we are expecting.
        $item = array_pop($result['items']);
        $this->assertInstanceOf('\totara_core\user_learning\item', $item);
        $this->assertEquals('totara_certification', $item->get_component());
        $this->assertEquals($certifications[0]->id, $item->id);
        $this->assertEquals($certifications[0]->fullname, $item->fullname);
        $this->assertEquals($certifications[0]->shortname, $item->shortname);
        $this->assertEquals($certifications[0]->summary, $item->description);

        // Check that user 1 has 2 learning items as expected.
        $this->setUser($users[1]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items', [
            'input' => [
                'filters' => [
                    'type' => 'CERTIFICATION'
                ],
            ]
        ]);
        $this->assert_result($result, 2);

        // Check that user 2 has 0 learning items as expected.
        $this->setUser($users[2]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items', [
            'input' => [
                'filters' => [
                    'type' => 'CERTIFICATION'
                ],
            ]
        ]);
        $this->assert_result($result, 0);
    }

    /**
     * Test the results of the query when a user has no current learning items.
     */
    public function test_resolve_learning_item_selected() {
        $users = $this->create_users();
        $items = $this->create_learning_items($users);

        $ids = [
            "{$items['courses'][0]->id}-course",
            "{$items['courses'][2]->id}-course",
            "{$items['programs'][0]->id}-program",
            "{$items['programs'][1]->id}-program",
            "{$items['certifications'][0]->id}-certification",
            "{$items['certifications'][2]->id}-certification",
        ];

        // Check that user 0 has one learning item as expected.
        $this->setUser($users[0]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items_selected', [
            'input' => [
                'filters' => [
                    'ids' => $ids
                ],
            ]
        ]);
        $this->assert_result($result, 3);

        // Just a quick check that they're all learning items.
        foreach ($result['items'] as $item) {
            $this->assertInstanceOf('\totara_core\user_learning\item', $item);
        }

        // Check that user 1 has 4 learning items as expected.
        $this->setUser($users[1]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items_selected', [
            'input' => [
                'filters' => [
                    'ids' => $ids
                ],
            ]
        ]);
        $this->assert_result($result, 4);

        // Check that user 2 has 0 learning items as expected.
        $this->setUser($users[2]);
        $result = $this->resolve_graphql_query('totara_core_user_learning_items_selected', [
            'input' => [
                'filters' => [
                    'ids' => $ids
                ],
            ]
        ]);
        $this->assert_result($result, 0);
    }

    /**
     * Test the results of the AJAX query through the GraphQL stack.
     */
    public function test_ajax_query() {
        $users = $this->create_users();
        $items = $this->create_learning_items($users);

        $ids = [
            "{$items['courses'][0]->id}-course",
            "{$items['courses'][1]->id}-course",
            "{$items['courses'][2]->id}-course",
            "{$items['programs'][0]->id}-program",
            "{$items['programs'][1]->id}-program",
            "{$items['programs'][2]->id}-program",
            "{$items['certifications'][0]->id}-certification",
            "{$items['certifications'][1]->id}-certification",
            "{$items['certifications'][2]->id}-certification",
        ];

        $this->setUser($users[0]);
        $result = $this->execute_graphql_operation('totara_core_user_learning_items_selected', [
            'input' => [
                'filters' => [
                    'ids' => $ids
                ],
            ]
        ]);
        $data = $result->toArray()['data'];
        $this->assert_result($data['totara_core_user_learning_items_selected'], 3);

        // Note: This relies on the alphabetical ordering.
        $expected = [
            'totara_core_user_learning_items_selected' => [
                'items' => [
                    0 => [
                        'id' => "{$items['courses'][0]->id}",
                        'itemtype' => "course",
                        'fullname' => $items['courses'][0]->fullname,
                        'description' => $items['courses'][0]->summary,
                        'progress' => null,
                        'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage',
                        'url_view' => "https://www.example.com/moodle/course/view.php?id={$items['courses'][0]->id}",
                        'unique_id' => "{$items['courses'][0]->id}-course",
                    ],
                    1 => [
                        'id' => "{$items['programs'][0]->id}",
                        'itemtype' => 'program',
                        'fullname' => $items['programs'][0]->fullname,
                        'description' => $items['programs'][0]->summary,
                        'progress' => 0.0,
                        'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_program/1/defaultimage',
                        'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['programs'][0]->id}",
                        'unique_id' => "{$items['programs'][0]->id}-program",
                    ],
                    2 => [
                        'id' => "{$items['certifications'][0]->id}",
                        'itemtype' => "certification",
                        'fullname' => $items['certifications'][0]->fullname,
                        'description' => $items['certifications'][0]->summary,
                        'progress' => 0.0,
                        'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_certification/1/defaultimage',
                        'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['certifications'][0]->id}",
                        'unique_id' => "{$items['certifications'][0]->id}-certification",
                    ],
                ],
                'next_cursor' => '',
                'total' => 3
            ]
        ];
        $this->assertSame($expected, $data);

        $this->setUser($users[1]);
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'totara_core_user_learning_items_selected'),
            [
                'input' => [
                    'filters' => [
                        'ids' => $ids
                    ],
                ]
            ]
        );
        $data = $result->toArray()['data'];
        $this->assert_result($data['totara_core_user_learning_items_selected'], 6);

        $expected = [
            'totara_core_user_learning_items_selected' => [
                'items' => [
                    0 => [
                        'id' => "{$items['courses'][0]->id}",
                        'itemtype' => "course",
                        'fullname' => $items['courses'][0]->fullname,
                        'description' => $items['courses'][0]->summary,
                        'progress' => null,
                        'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage',
                        'url_view' => "https://www.example.com/moodle/course/view.php?id={$items['courses'][0]->id}",
                        'unique_id' => "{$items['courses'][0]->id}-course",
                    ],
                    1 => [
                        'id' => "{$items['courses'][1]->id}",
                        'itemtype' => "course",
                        'fullname' => $items['courses'][1]->fullname,
                        'description' => $items['courses'][1]->summary,
                        'progress' => null,
                        'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage',
                        'url_view' => "https://www.example.com/moodle/course/view.php?id={$items['courses'][1]->id}",
                        'unique_id' => "{$items['courses'][1]->id}-course",
                    ],
                    2 => [
                        'id' => "{$items['programs'][0]->id}",
                        'itemtype' => 'program',
                        'fullname' => $items['programs'][0]->fullname,
                        'description' => $items['programs'][0]->summary,
                        'progress' => 0.0,
                        'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_program/1/defaultimage',
                        'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['programs'][0]->id}",
                        'unique_id' => "{$items['programs'][0]->id}-program",
                    ],
                    3 => [
                        'id' => "{$items['programs'][1]->id}",
                        'itemtype' => 'program',
                        'fullname' => $items['programs'][1]->fullname,
                        'description' => $items['programs'][1]->summary,
                        'progress' => 0.0,
                        'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_program/1/defaultimage',
                        'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['programs'][1]->id}",
                        'unique_id' => "{$items['programs'][1]->id}-program",
                    ],
                    4 => [
                        'id' => "{$items['certifications'][0]->id}",
                        'itemtype' => "certification",
                        'fullname' => $items['certifications'][0]->fullname,
                        'description' => $items['certifications'][0]->summary,
                        'progress' => 0.0,
                        'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_certification/1/defaultimage',
                        'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['certifications'][0]->id}",
                        'unique_id' => "{$items['certifications'][0]->id}-certification",
                    ],
                    5 => [
                        'id' => "{$items['certifications'][1]->id}",
                        'itemtype' => "certification",
                        'fullname' => $items['certifications'][1]->fullname,
                        'description' => $items['certifications'][1]->summary,
                        'progress' => 0.0,
                        'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/totara_certification/1/defaultimage',
                        'url_view' => "https://www.example.com/moodle/totara/program/view.php?id={$items['certifications'][1]->id}",
                        'unique_id' => "{$items['certifications'][1]->id}-certification",
                    ],
                ],
                'next_cursor' => '',
                'total' => 6
            ]
        ];
        $this->assertSame($expected, $data);

        $this->setUser($users[2]);
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'totara_core_user_learning_items_selected'),
            [
                'input' => [
                    'filters' => [
                        'ids' => $ids
                    ],
                ]
            ]
        );
        $data = $result->toArray()['data'];
        $this->assert_result($data['totara_core_user_learning_items_selected'], 0);

        $expected = [
            'totara_core_user_learning_items_selected' => [
                'items' => [],
                'next_cursor' => '',
                'total' => 0,
            ]
        ];
        $this->assertSame($expected, $data);
    }

}
