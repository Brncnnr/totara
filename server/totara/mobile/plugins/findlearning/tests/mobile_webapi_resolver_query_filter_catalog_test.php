<?php
/*
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_findlearning
 */

defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;
use mobile_findlearning\filter_handler;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_catalog\task\refresh_catalog_data;
use totara_engage\access\access;

/**
 * Note: This also tests the catalog_item resolver since that's contained within the page.
 */
class mobile_findlearning_webapi_resolver_query_filter_catalog_testcase extends \core_phpunit\testcase {

    use webapi_phpunit_helper;

    protected function tearDown(): void {
        // Make sure to clear the filter caches when we're done.
        filter_handler::phpunit_reset();
    }

    /**
     * Create some users and various learning items to be fetched in the catalog.
     * @return []
     */
    private function create_faux_catalog_items($format = 'html') {
        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(['shortname' => 'crs1', 'fullname' => 'Course 1', 'summary' => 'The course a user is enrolled in']);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student', 'manual');

        // Create some additional padding for the catalog
        $this->getDataGenerator()->create_course(['shortname' => 'alpha', 'fullname' => 'Alpha course', 'summary' => 'Alphabetical courses 1']);
        $this->getDataGenerator()->create_course(['shortname' => 'beta', 'fullname' => 'Beta course', 'summary' => 'Alphabetical courses 1']);
        $this->getDataGenerator()->create_course(['shortname' => 'charlie', 'fullname' => 'Charlie course', 'summary' => 'Alphabetical courses 1']);
        $this->getDataGenerator()->create_course(['shortname' => 'delta', 'fullname' => 'Delta course', 'summary' => 'Alphabetical courses 1']);
        $this->getDataGenerator()->create_course(['shortname' => 'echo', 'fullname' => 'Echo course', 'summary' => 'Alphabetical courses 1']);
        $this->getDataGenerator()->create_course(['shortname' => 'foxtrot', 'fullname' => 'Foxtrot course', 'summary' => 'Alphabetical courses 1']);
        $this->getDataGenerator()->create_course(['shortname' => 'golf', 'fullname' => 'Golf course', 'summary' => 'Alphabetical courses 1']);
        $this->getDataGenerator()->create_course(['shortname' => 'hotel', 'fullname' => 'Hotel course', 'summary' => 'Alphabetical courses 1']);

        // Add some extra courses as prog/cert content.
        $c1 = $this->getDataGenerator()->create_course(['fullname' => 'Prog content 1']);
        $c2 = $this->getDataGenerator()->create_course(['fullname' => 'Prog content 2']);
        $c3 = $this->getDataGenerator()->create_course(['fullname' => 'Prog content 3']);

        // Create a single program expected at the top of sort.
        $program = $prog_gen->create_program(['shortname' => 'prg', 'fullname' => 'Alpha program', 'summary' => 'first program']);
        $prog_gen->add_courses_and_courseset_to_program($program, [[$c1, $c2], [$c3]], CERTIFPATH_STD);
        $prog_gen->assign_program($program->id, [$user1->id]);

        // Create a single certification expected at the top of sort.
        $certification = $prog_gen->create_certification(['shortname' => 'crt', 'fullname' => 'Alpha certification', 'summary' => 'first certification']);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1, $c2], [$c3]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1], [$c3]], CERTIFPATH_RECERT);
        $prog_gen->assign_program($certification->id, [$user1->id]);

        // Create 3 playlists to test visibility and ordering.
        $playlistgen = $this->getDataGenerator()->get_plugin_generator('totara_playlist');

        $params = [
            'name' => 'Alpha playlist 1',
            'userid' => $user1->id,
            'contextid' => \context_user::instance($user1->id)->id,
            'access' => access::PRIVATE,
            'summary' => 'Playlist 1 description'
        ];
        $playlistgen->create_playlist($params);

        $params = [
            'name' => 'Alpha playlist 2',
            'userid' => $user2->id,
            'contextid' => \context_user::instance($user2->id)->id,
            'access' => access::PRIVATE,
            'summary' => 'Playlist 2 description'
        ];
        $playlistgen->create_playlist($params);

        $params = [
            'name' => 'Beta playlist',
            'userid' => $user1->id,
            'contextid' => \context_user::instance($user1->id)->id,
            'access' => access::PUBLIC,
            'summary' => 'Playlist 1 description'
        ];
        $playlistgen->create_playlist($params);

        // Create 3 articles to test visibility and ordering.
        $articlegen = $this->getDataGenerator()->get_plugin_generator('engage_article');
        $params = [
            'name' => 'Alpha article 1',
            'content' => 'this article is about the first alpha',
            'userid' => $user1->id,
            'access' => access::PRIVATE
        ];
        $articlegen->create_article($params);

        $params = [
            'name' => 'Alpha article 2',
            'content' => 'this article is about the second alpha',
            'userid' => $user2->id,
            'access' => access::PRIVATE
        ];
        $articlegen->create_article($params);

        $params = [
            'name' => 'Beta article',
            'content' => 'this article is about the only beta',
            'userid' => $user1->id,
            'access' => access::PUBLIC
        ];
        $articlegen->create_article($params);

        $task = new refresh_catalog_data();
        $task->execute();

        $this->wait_for_mssql_fts_index(20);

        return ['u1' => $user1, 'u2' => $user2];
    }

    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        $users = $this->create_faux_catalog_items();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $result = $this->resolve_graphql_query(
            'mobile_findlearning_filter_catalog',
            [
                "filter_data" => []
            ]
        );
    }

    /**
     * Test the results of the query when required arguments are omitted.
     */
    public function test_resolve_missing_arguments() {
        $users = $this->create_faux_catalog_items();
        $this->setGuestUser();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('missing required arguments for query');

        $result = $this->resolve_graphql_query(
            'mobile_findlearning_filter_catalog',
            []
        );
    }

    /**
     * Test the results of the query when the current user is logged in as the guest user.
     */
    public function test_resolve_guest_user() {
        $users = $this->create_faux_catalog_items();
        $this->setGuestUser();

        // Guests can view cataog items but it won't contain any articles or playlists
        $result = $this->resolve_graphql_query(
            'mobile_findlearning_filter_catalog',
            [
                'filter_data' => []
            ]
        );

        // Check the page contains the expected data / ordering.
        $expected = [
            0 => ['type' => 'course', 'name' => 'Alpha course'],
            1 => ['type' => 'course', 'name' => 'Beta course'],
            2 => ['type' => 'course', 'name' => 'Charlie course'],
            3 => ['type' => 'course', 'name' => 'Course 1'],
            4 => ['type' => 'course', 'name' => 'Delta course'],
            5 => ['type' => 'course', 'name' => 'Echo course'],
            6 => ['type' => 'course', 'name' => 'Foxtrot course'],
            7 => ['type' => 'course', 'name' => 'Golf course'],
            8 => ['type' => 'course', 'name' => 'Hotel course'],
            9 => ['type' => 'course', 'name' => 'Prog content 1']
        ];

        $this->assertEquals('12', $result->maxcount); // The total number of unchecked records.
        $this->assertEquals('18', $result->limitfrom); // The number of checked records.
        $this->assertFalse($result->endofrecords); // Whether this page is the last.

        foreach ($result->objects as $item) {
            $expect = array_shift($expected);
            $this->assertSame($expect['type'], $item->objecttype);
            $this->assertSame($expect['name'], $item->sorttext);
        }

        filter_handler::phpunit_reset();

        // Check the second/final page.
        $limit = $result->limitfrom;
        $result = $this->resolve_graphql_query(
            'mobile_findlearning_filter_catalog',
            [
                'limit_from' => $limit,
                'filter_data' => []
            ]
        );
        $expected = [
            0 => ['type' => 'course', 'name' => 'Prog content 2'],
            1 => ['type' => 'course', 'name' => 'Prog content 3']
        ];

        foreach ($result->objects as $item) {
            $expect = array_shift($expected);
            $this->assertSame($expect['type'], $item->objecttype);
            $this->assertSame($expect['name'], $item->sorttext);
        }

        $this->assertEquals('20', $result->maxcount); // The total number of unchecked records.
        $this->assertEquals('20', $result->limitfrom); // The number of checked records.
        $this->assertTrue($result->endofrecords); // Whether this page is the last.

        filter_handler::phpunit_reset();

        // Now check the results when filtering the data.
        $result = $this->resolve_graphql_query(
            'mobile_findlearning_filter_catalog',
            [
                'limit_from' => 0,
                'filter_data' => [
                    'catalog_fts' => 'beta'
                ]
            ]
        );

        $expected = [
            0 => ['type' => 'course', 'name' => 'Beta course'],
        ];

        $this->assertEquals('1', $result->maxcount); // The total number of unchecked records.
        $this->assertEquals('3', $result->limitfrom); // The number of checked records, theres 2 we couldn't see.
        $this->assertTrue($result->endofrecords); // Whether this page is the last.

        foreach ($result->objects as $item) {
            $expect = array_shift($expected);
            $this->assertSame($expect['type'], $item->objecttype);
            $this->assertSame($expect['name'], $item->sorttext);
        }
    }

    /**
     * Test the results of the query when the current user is logged in as user one.
     */
    public function test_resolve_regular_user() {
        $users = $this->create_faux_catalog_items();
        $u1 = $users['u1'];
        $this->setUser($u1->id);

        // Get the results for the regular user u1.
        $result = $this->resolve_graphql_query(
            'mobile_findlearning_filter_catalog',
            [
                'filter_data' => []
            ]
        );

        // Check the page contains the expected data / ordering.
        $expected = [
            0 => ['type' => 'engage_article', 'name' => 'Alpha article 1'],
            1 => ['type' => 'course', 'name' => 'Alpha course'],
            2 => ['type' => 'playlist', 'name' => 'Alpha playlist 1'],
            3 => ['type' => 'engage_article', 'name' => 'Beta article'],
            4 => ['type' => 'course', 'name' => 'Beta course'],
            5 => ['type' => 'playlist', 'name' => 'Beta playlist'],
            6 => ['type' => 'course', 'name' => 'Charlie course'],
            7 => ['type' => 'course', 'name' => 'Course 1'],
            8 => ['type' => 'course', 'name' => 'Delta course'],
            9 => ['type' => 'course', 'name' => 'Echo course']
        ];

        foreach ($result->objects as $item) {
            $expect = array_shift($expected);
            $this->assertSame($expect['type'], $item->objecttype);
            $this->assertSame($expect['name'], $item->sorttext);
        }

        filter_handler::phpunit_reset();

        // Now check the results when filtering the data.
        $result = $this->resolve_graphql_query(
            'mobile_findlearning_filter_catalog',
            [
                'limit_from' => 0,
                'filter_data' => [
                    'catalog_fts' => 'beta'
                ]
            ]
        );

        $expected = [
            0 => ['type' => 'engage_article', 'name' => 'Beta article'],
            1 => ['type' => 'course', 'name' => 'Beta course'],
            2 => ['type' => 'playlist', 'name' => 'Beta playlist'],
        ];

        $this->assertEquals('3', $result->maxcount); // The total number of unchecked records.
        $this->assertEquals('3', $result->limitfrom); // The number of checked records, theres 2 we couldn't see.
        $this->assertTrue($result->endofrecords); // Whether this page is the last.

        $this->assert_has_expected_result($expected, $result->objects);
    }

    /**
     * Test the results of the query when the current user is the site administrator.
     */
    public function test_resolve_admin_user() {
        $users = $this->create_faux_catalog_items();
        $this->setAdminUser();


        // Get the results for the regular user u1.
        $result = $this->resolve_graphql_query(
            'mobile_findlearning_filter_catalog',
            [
                'filter_data' => []
            ]
        );

        // Check the page contains the expected data / ordering.
        $expected = [
            0 => ['type' => 'engage_article', 'name' => 'Alpha article 1'],
            1 => ['type' => 'engage_article', 'name' => 'Alpha article 2'],
            2 => ['type' => 'course', 'name' => 'Alpha course'],
            3 => ['type' => 'playlist', 'name' => 'Alpha playlist 1'],
            4 => ['type' => 'playlist', 'name' => 'Alpha playlist 2'],
            5 => ['type' => 'engage_article', 'name' => 'Beta article'],
            6 => ['type' => 'course', 'name' => 'Beta course'],
            7 => ['type' => 'playlist', 'name' => 'Beta playlist'],
            8 => ['type' => 'course', 'name' => 'Charlie course'],
            9 => ['type' => 'course', 'name' => 'Course 1'],
        ];

        foreach ($result->objects as $item) {
            $expect = array_shift($expected);
            $this->assertSame($expect['type'], $item->objecttype);
            $this->assertSame($expect['name'], $item->sorttext);
        }

        $this->assertEquals('18', $result->maxcount); // The total number of unchecked records.
        $this->assertEquals('12', $result->limitfrom); // The number of checked records.
        $this->assertFalse($result->endofrecords); // Whether this page is the last.

        filter_handler::phpunit_reset();

        // Now check the results when filtering the data.
        $result = $this->resolve_graphql_query(
            'mobile_findlearning_filter_catalog',
            [
                'limit_from' => 0,
                'filter_data' => [
                    'catalog_fts' => 'beta'
                ]
            ]
        );

        $expected = [
            0 => ['type' => 'engage_article', 'name' => 'Beta article'],
            1 => ['type' => 'course', 'name' => 'Beta course'],
            2 => ['type' => 'playlist', 'name' => 'Beta playlist'],
        ];

        $this->assertEquals('3', $result->maxcount); // The total number of unchecked records.
        $this->assertEquals('3', $result->limitfrom); // The number of checked records, theres 2 we couldn't see.
        $this->assertTrue($result->endofrecords); // Whether this page is the last.

        $this->assert_has_expected_result($expected, $result->objects);
    }

    /**
     * Test the results of the embedded mobile query through the GraphQL stack.
     */
    public function test_embedded_query() {
        $users = $this->create_faux_catalog_items();
        $this->setUser($users['u1']->id);

        try {
            $result = \totara_webapi\graphql::execute_operation(
                \core\webapi\execution_context::create('mobile', 'mobile_findlearning_filter_catalog'),
                [
                    'limit_from' => 0,
                    'filter_data' => []
                ]
            );

            $data = $result->toArray()['data'];

            $this->assertNotEmpty($data['catalogPage']);
            $page = $data['catalogPage'];

            $expected = [
                0 => ['type' => 'engage_article', 'name' => 'Alpha article 1'],
                1 => ['type' => 'course', 'name' => 'Alpha course'],
                2 => ['type' => 'playlist', 'name' => 'Alpha playlist 1'],
                3 => ['type' => 'engage_article', 'name' => 'Beta article'],
                4 => ['type' => 'course', 'name' => 'Beta course'],
                5 => ['type' => 'playlist', 'name' => 'Beta playlist'],
                6 => ['type' => 'course', 'name' => 'Charlie course'],
                7 => ['type' => 'course', 'name' => 'Course 1'],
                8 => ['type' => 'course', 'name' => 'Delta course'],
                9 => ['type' => 'course', 'name' => 'Echo course']
            ];

            foreach ($page['items'] as $item) {
                $expect = array_shift($expected);
                $this->assertSame($expect['type'], $item['itemType']);
                $this->assertSame($expect['name'], $item['title']);
                $this->assertMatchesRegularExpression('|^https://www\.example\.com/.*|', $item['mobileImage']);
            }

            $this->assertEquals('16', $page['maxCount']); // The total number of unchecked records.
            $this->assertEquals('14', $page['pointer']); // The number of checked records.
            $this->assertFalse($page['finalPage']); // Whether this page is the last.
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }

        // Now again but with a filter.
        try {
            filter_handler::phpunit_reset();

            $result = \totara_webapi\graphql::execute_operation(
                \core\webapi\execution_context::create('mobile', 'mobile_findlearning_filter_catalog'),
                [
                    'limit_from' => 0,
                    'filter_data' => [
                        'catalog_fts' => 'beta'
                    ]
                ]
            );

            $data = $result->toArray()['data'];

            $this->assertNotEmpty($data['catalogPage']);
            $page = $data['catalogPage'];

            $expected = [
                0 => ['type' => 'engage_article', 'name' => 'Beta article'],
                1 => ['type' => 'course', 'name' => 'Beta course'],
                2 => ['type' => 'playlist', 'name' => 'Beta playlist'],
            ];

            $this->assertSameSize($expected, $page['items']);

            // As the order of the items can vary between different database FTS implementations
            // we do this in an order agnostic way
            foreach ($page['items'] as $item) {
                foreach ($expected as $key => $expected_item) {
                    if ($expected_item['type'] == $item['itemType']
                        && $expected_item['name'] == $item['title']) {
                        unset($expected[$key]);
                    }
                }
                $this->assertMatchesRegularExpression('|^https://www\.example\.com/.*|', $item['mobileImage']);
            }

            $this->assertEmpty($expected, 'The actual result differs from the expected result');

            $this->assertEquals('3', $page['maxCount']); // The total number of unchecked records.
            $this->assertEquals('3', $page['pointer']); // The number of checked records.
            $this->assertTrue($page['finalPage']); // Whether this page is the last.
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Assert that the results contains the expected items
     *
     * @param array $expected
     * @param array $actual
     * @return void
     */
    private function assert_has_expected_result(array $expected, array $actual): void {
        $this->assertSameSize($expected, $actual);

        // As the order of the items can vary between different database FTS implementations
        // we do this in an order agnostic way
        foreach ($actual as $item) {
            foreach ($expected as $key => $expected_item) {
                if ($expected_item['type'] == $item->objecttype
                    && $expected_item['name'] == $item->sorttext) {
                    unset($expected[$key]);
                }
            }
        }

        $this->assertEmpty($expected, 'The actual result differs from the expected result');
    }

    /**
     * As Mssql full text search index generation is slow we need to make sure
     * we wait until it's ready
     *
     * @param int $expected_count
     * @return void
     * @throws dml_exception
     */
    private function wait_for_mssql_fts_index(int $expected_count): void {
        if (builder::get_db()->get_dbfamily() != 'mssql') {
            return;
        }

        $sql = "
            SELECT MAX(FULLTEXTCATALOGPROPERTY(cat.name, 'ItemCount')) AS item_count
            FROM sys.fulltext_catalogs AS cat
        ";

        $current_count = 0;
        while ($current_count < $expected_count) {
            $current_count = builder::get_db()->get_field_sql($sql);
            $this->waitForSecond();
        }
    }
}
