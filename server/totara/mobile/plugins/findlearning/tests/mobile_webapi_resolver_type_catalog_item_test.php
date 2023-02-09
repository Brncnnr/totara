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

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\access\access;
use totara_catalog\task\refresh_catalog_data;
use mobile_findlearning\catalog as mobile_catalog;

/**
 * Note: This also tests the catalog_item resolver since that's contained within the page.
 */
class mobile_findlearning_webapi_resolver_type_catalog_item_testcase extends \core_phpunit\testcase {

    use webapi_phpunit_helper;

    /**
     * Run the catalog resolver on an object and return the results.
     * @return mixed
     */
    private function resolve($field, $item, array $args = []) {
        return $this->resolve_graphql_type('mobile_findlearning_catalog_item', $field, $item, $args);
    }

    /**
     * Create some users and various learning items to be fetched in the catalog.
     * @return []
     */
    private function create_faux_catalog_items($format = 'html'): array {
        global $CFG;


        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // A dummy pluginfile URL to check trasformation to mobile plugin file URLS.
        $file_url = $CFG->wwwroot . '/pluginfile.php/163/course/summary/summary_image.png';

        // Create some courses.
        $this->getDataGenerator()->create_course([
            'shortname' => 'alpha',
            'fullname' => 'Alpha course',
            'summary' => 'Alphabetical courses 1'
        ]);

        $this->getDataGenerator()->create_course([
            'shortname' => 'beta',
            'fullname' => 'Beta course',
            'summary' => 'Alphabetical courses 2 ' . $file_url
        ]);

        $this->getDataGenerator()->create_course([
            'shortname' => 'charlie',
            'fullname' => 'Charlie course',
            'summary' => 'Alphabetical courses 3 http://www.externaltest.com'
        ]);

        // Add some extra courses as prog/cert content.
        $c1 = $this->getDataGenerator()->create_course(['fullname' => 'Prog content 1']);
        $c2 = $this->getDataGenerator()->create_course(['fullname' => 'Prog content 2']);
        $c3 = $this->getDataGenerator()->create_course(['fullname' => 'Prog content 3']);

        // Create a single program expected at the top of sort.
        $program = $prog_gen->create_program([
            'shortname' => 'prg',
            'fullname' => 'Alpha program',
            'summary' => 'first program' . $file_url
        ]);

        // Create a single certification expected at the top of sort.
        $certification = $prog_gen->create_certification([
            'shortname' => 'crt',
            'fullname' => 'Alpha certification',
            'summary' => 'first certification'
        ]);

        // Create a playlist to test.
        $playlistgen = $this->getDataGenerator()->get_plugin_generator('totara_playlist');

        $params = [
            'name' => 'Alpha playlist 1',
            'userid' => $user1->id,
            'contextid' => \context_user::instance($user1->id)->id,
            'access' => access::PRIVATE,
            'summary' => 'Playlist 1 description'
        ];
        $playlistgen->create_playlist($params);

        // Create an article to test.
        $articlegen = $this->getDataGenerator()->get_plugin_generator('engage_article');
        $params = [
            'name' => 'Alpha article 1',
            'content' => 'this article is about the first alpha',
            'userid' => $user1->id,
            'access' => access::PRIVATE
        ];
        $articlegen->create_article($params);

        $task = new refresh_catalog_data();
        $task->execute();

        return ['u1' => $user1, 'u2' => $user2];
    }

    /**
     * Test mobile_item::create failure
     */
    public function test_resolve_invalid_object(): void {
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1->id);
    }

    public function resolve_invalid_items_data_provider(): array {
        return [
            [null],
            ['totara_program'],
            ['totara_certification'],
            ['crazystuff'],
        ];
    }

    /**
     * Test resolving prog/cert failure
     *
     * @dataProvider resolve_invalid_items_data_provider
     */
    public function test_resolve_invalid_items($object_type): void {
        $users = $this->create_faux_catalog_items();
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $item = array_shift($page->objects); // Get a valid item to mess up.

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Only mobile_item catalog objects are accepted: object');

        $item = new \stdClass();
        if ($object_type) {
            $item->objecttype = $object_type;
        }
        $this->resolve('id', $item);
    }

    /**
     * Test that the catalog items id is resolved as expected.
     */
    public function test_resolve_id(): void {
        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $objects = $page->objects;

        foreach ($objects as $object) {
            $this->assertSame($object->id, $this->resolve('id', $object));
        }
    }

    /**
     * Test that the catalog items itemid is resolved as expected.
     */
    public function test_resolve_itemid(): void {
        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $objects = $page->objects;

        foreach ($objects as $object) {
            $this->assertSame($object->objectid, $this->resolve('itemid', $object));
        }
    }

    /**
     * Test that the catalog items type is resolved as expected.
     */
    public function test_resolve_item_type(): void {
        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $objects = $page->objects;

        foreach ($objects as $object) {
            $this->assertSame($object->objecttype, $this->resolve('item_type', $object));
        }
    }

    /**
     * Test that the catalog items title is resolved as expected.
     */
    public function test_resolve_title(): void {
        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $objects = $page->objects;

        foreach ($objects as $object) {
            $expected = null;
            foreach ($object->data as $data) {
                // We have to take into account the several fields that different itemtypes use.
                if (!is_array($data)) {
                    continue;
                } else if (array_key_exists('name', $data)) {
                    $expected = $data['name'];
                    break;
                } else if (array_key_exists('fullname', $data)) {
                    $expected = $data['fullname'];
                    break;
                }
            }

            if (empty($expected)) {
                $this->fail('Data object missing required field: name');
            } else {
                $this->assertSame($expected, $this->resolve('title', $object, ['format' => format::FORMAT_PLAIN]));
            }
        }
    }

    /**
     * Test that the catalog items summary is resolved as expected.
     */
    public function test_resolve_summary(): void {
        global $CFG;

        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();

        // Resolve the items via the page so the summary data is loaded properly.
        $objects = $this->resolve_graphql_type('mobile_findlearning_catalog_page', 'items', $page, []);

        // Articles don't have a summary.
        $object = array_shift($objects);
        $this->assertSame('engage_article', $object->objecttype);
        $this->assertEmpty($this->resolve('summary', $object, ['format' => format::FORMAT_PLAIN]));

        // This one's just a basic course summary.
        $object = array_shift($objects);
        $this->assertSame('course', $object->objecttype);
        $summary = $this->resolve('summary', $object, ['format' => format::FORMAT_PLAIN]);
        $expected = 'Alphabetical courses 1';
        $this->assertSame($expected, $summary);

        // Playlists don't have a summary.
        $object = array_shift($objects);
        $this->assertSame('playlist', $object->objecttype);
        $this->assertEmpty($this->resolve('summary', $object, ['format' => format::FORMAT_PLAIN]));

        // Here's the main test, pluginfile.php should become totara/mobile/pluginfile.php
        $object = array_shift($objects);
        $this->assertSame('course', $object->objecttype);
        $summary = $this->resolve('summary', $object, ['format' => format::FORMAT_PLAIN]);
        $file_url = $CFG->wwwroot . '/totara/mobile/pluginfile.php/163/course/summary/summary_image.png';
        $expected = 'Alphabetical courses 2 ' . $file_url;
        $this->assertSame($expected, $summary);

        // External URLs shouldnt change.
        $object = array_shift($objects);
        $this->assertSame('course', $object->objecttype);
        $summary = $this->resolve('summary', $object, ['format' => format::FORMAT_PLAIN]);
        $expected = 'Alphabetical courses 3 http://www.externaltest.com';
        $this->assertSame($expected, $summary);
    }

    /**
     * Test that the catalog items image enabled is resolved as expected.
     */
    public function test_resolve_image_enabled(): void {
        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $objects = $page->objects;

        foreach ($objects as $object) {
            $this->assertTrue($this->resolve('image_enabled', $object)); // Hardcoded atm.
        }
    }

    /**
     * Test that the catalog items image url is resolved as expected.
     */
    public function test_resolve_image_url(): void {
        global $CFG;

        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $objects = $page->objects;

        foreach ($objects as $object) {
            $expected = null;
            foreach ($object->data as $data) {
                if (is_array($data) && array_key_exists('image', $data)) {
                    $imageurl = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $data['image']->url);
                }
            }

            if (empty($imageurl)) {
                $this->fail('Data object missing required field: image');
            } else {
                // Final clean up for image URLs, remove the arguments.
                $key = "~\?.*=.*~";
                $expected = preg_replace($key, '', $imageurl);

                $this->assertSame($expected, $this->resolve('image_url', $object));
            }
        }
    }

    public function test_resolve_view_url(): void {
        global $CFG;

        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $objects = $page->objects;

        $expected = [
            'course' => "{$CFG->wwwroot}/course/view.php?id=",
            'playlist' => "{$CFG->wwwroot}/totara/playlist/index.php?id=",
            'engage_article' => "{$CFG->wwwroot}/totara/engage/resources/article/index.php?id="
        ];

        foreach ($objects as $object) {
            $size = strlen($expected[$object->objecttype]);
            $viewurl = substr($this->resolve('view_url', $object), 0, $size);
            $this->assertSame($expected[$object->objecttype], $viewurl);
        }
    }

    /**
     * Test that the catalog items image alt is resolved as expected.
     */
    public function test_resolve_image_alt(): void {
        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $objects = $page->objects;

        foreach ($objects as $object) {
            $expected = null;
            foreach ($object->data as $data) {
                if (is_array($data) && array_key_exists('image', $data)) {
                    $expected = $data['image']->alt;
                }
            }

            if (empty($expected)) {
                $this->fail('Data object missing required field: name');
            } else {
                $this->assertSame($expected, $this->resolve('image_alt', $object, ['format' => format::FORMAT_PLAIN]));
            }
        }
    }

    /**
     * Test that the catalog items description enabled is resolved as expected.
     */
    public function test_resolve_description_enabled(): void {
        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $objects = $page->objects;

        foreach ($objects as $object) {
            $this->assertTrue($this->resolve('description_enabled', $object)); // Hardcoded atm.
        }
    }

    /**
     * Test that the catalog items description is resolved as expected.
     */
    public function test_resolve_description(): void {
        $users = $this->create_faux_catalog_items();

        // get the catalog objects for user 1 since it will have a range of objects.
        $this->setUser($users['u1']->id);
        $page = mobile_catalog::load_catalog_page_objects();
        $objects = $page->objects;

        foreach ($objects as $object) {
            $this->assertTrue($this->resolve('description_enabled', $object)); // Hardcoded atm.
        }
    }
}
