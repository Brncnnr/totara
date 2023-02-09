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

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\access\access;
use totara_catalog\task\refresh_catalog_data;
use mobile_findlearning\catalog as mobile_catalog;

/**
 * Note: This also tests the catalog_item resolver since that's contained within the page.
 */
class mobile_findlearning_webapi_resolver_type_catalog_page_testcase extends \core_phpunit\testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $item, array $args = []) {
        return $this->resolve_graphql_type('mobile_findlearning_catalog_page', $field, $item, $args);
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
        $article = $articlegen->create_article($params); // Our expected first item.

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

        return ['u1' => $user1, 'u2' => $user2, 'article' => $article];
    }

    public function test_resolve_max_count() {
        $faux = $this->create_faux_catalog_items();

        // First for no user.
        try {
            $page = mobile_catalog::load_catalog_page_objects();
            $value = $this->resolve('max_count', $page);
            $this->fail('Expected exception when not logged in');
        } catch (\dml_missing_record_exception $e) {
            $this->assertMatchesRegularExpression('|^Can not find data record in database.|', $e->getMessage());
        }

        // Now test for user1.
        $this->setUser($faux['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $value = $this->resolve('max_count', $page);
        $this->assertSame(16, $value);

        // Now test for user2.
        $this->setUser($faux['u2']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $value = $this->resolve('max_count', $page);
        $this->assertSame(16, $value);

        // Now test for the admin user.
        $this->setAdminUser();
        $page = mobile_catalog::load_catalog_page_objects();
        $value = $this->resolve('max_count', $page);
        $this->assertSame(18, $value);
    }

    public function test_resolve_limit_from() {
        $faux = $this->create_faux_catalog_items();

        // First for no user.
        try {
            $page = mobile_catalog::load_catalog_page_objects();
            $value = $this->resolve('limit_from', $page);
            $this->fail('Expected exception when not logged in');
        } catch (\dml_missing_record_exception $e) {
            $this->assertMatchesRegularExpression('|^Can not find data record in database.|', $e->getMessage());
        }

        // Now test for user1.
        $this->setUser($faux['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $value = $this->resolve('limit_from', $page);
        $this->assertSame(14, $value);

        // Now test for user2.
        $this->setUser($faux['u2']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $value = $this->resolve('limit_from', $page);
        $this->assertSame(14, $value);

        // Now test for the admin user.
        $this->setAdminUser();
        $page = mobile_catalog::load_catalog_page_objects();
        $value = $this->resolve('limit_from', $page);
        $this->assertSame(12, $value);
    }

    public function test_resolve_final_records() {
        $faux = $this->create_faux_catalog_items();

        // First for no user.
        try {
            $page = mobile_catalog::load_catalog_page_objects();
            $value = $this->resolve('final_records', $page);
            $this->fail('Expected exception when not logged in');
        } catch (\dml_missing_record_exception $e) {
            $this->assertMatchesRegularExpression('|^Can not find data record in database.|', $e->getMessage());
        }

        // Now test both states for user 1.
        $this->setUser($faux['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects(); // Page 1.
        $value = $this->resolve('final_records', $page);
        $this->assertFalse($value);
        $page = mobile_catalog::load_catalog_page_objects($page->limitfrom); // Page 2.
        $value = $this->resolve('final_records', $page);
        $this->assertTrue($value);

        // Now test both states for user 2.
        $this->setUser($faux['u2']->id);
        $page = mobile_catalog::load_catalog_page_objects(); // Page 1.
        $value = $this->resolve('final_records', $page);
        $this->assertFalse($value);
        $page = mobile_catalog::load_catalog_page_objects($page->limitfrom); // Page 2.
        $value = $this->resolve('final_records', $page);
        $this->assertTrue($value);

        // Now test both states for the admin user.
        $this->setAdminUser();
        $page = mobile_catalog::load_catalog_page_objects(); // Page 1.
        $value = $this->resolve('final_records', $page);
        $this->assertFalse($value);
        $page = mobile_catalog::load_catalog_page_objects($page->limitfrom); // Page 2.
        $value = $this->resolve('final_records', $page);
        $this->assertTrue($value);
    }

    public function test_resolve_items() {
        global $DB;

        $faux = $this->create_faux_catalog_items();

        // First for no user.
        try {
            $page = mobile_catalog::load_catalog_page_objects();
            $value = $this->resolve('final_records', $page);
            $this->fail('Expected exception when not logged in');
        } catch (\dml_missing_record_exception $e) {
            $this->assertMatchesRegularExpression('|^Can not find data record in database.|', $e->getMessage());
        }

        // Now test for user1, results returned are tested by query, just need to test formatting here.
        $uid = $faux['u1']->id;
        $this->setUser($uid);
        $page = mobile_catalog::load_catalog_page_objects();
        $value = $this->resolve('items', $page);

        $this->assertCount(10, $value);

        $item = array_shift($value);
        $article = $faux['article']; // Should be our top item.

        // Check the top level info is what we are expecting, further checks will happen in the item resolver tests.
        $this->assertSame($item->id, $DB->get_field('catalog', 'id', ['objecttype' => 'engage_article', 'objectid' => $article->get_articleid()]));
        $this->assertSame('engage_article', $item->objecttype);
        $this->assertSame($article->get_articleid(), (int) $item->objectid);
        $this->assertSame($article->get_name(), $item->sorttext);
        $this->assertSame(\context_user::instance($uid)->id, (int) $item->contextid);

        // Now quickly check the rest of the objects.
        $expected = [
            'Alpha course',
            'Alpha playlist 1',
            'Beta article',
            'Beta course',
            'Beta playlist',
            'Charlie course',
            'Course 1',
            'Delta course',
            'Echo course'
        ];

        foreach ($value as $item) {
            $expect = array_shift($expected);
            $this->assertSame($expect, $item->sorttext); // Easiest way to check.
        }
    }
}
