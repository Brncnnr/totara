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

use core\format;
use totara_core\user_learning\item_base;
use totara_core\user_learning\item_helper;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara core learning item type resolver.
 */
class totara_core_webapi_resolver_type_learning_item_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $learning_item, array $args = []) {
        return $this->resolve_graphql_type('totara_core_learning_item', $field, $learning_item, $args);
    }

    /**
     * Create some users and various learning items.
     * @return []
     */
    private function create_faux_learning_items() {
        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $user = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(['shortname' => 'crs1', 'fullname' => 'course1', 'summary' => 'first course']);

        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student', 'manual');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();

        $program = $prog_gen->create_program(['shortname' => 'prg1', 'fullname' => 'program1', 'summary' => 'first program']);
        $prog_gen->add_courses_and_courseset_to_program($program, [[$c1, $c2], [$c3]], CERTIFPATH_STD);
        $prog_gen->assign_program($program->id, [$user->id]);

        $certification = $prog_gen->create_certification(['shortname' => 'crt1', 'fullname' => 'certification1', 'summary' => 'first certification']);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1, $c2], [$c3]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1], [$c3]], CERTIFPATH_RECERT);
        $prog_gen->assign_program($certification->id, [$user->id]);

        return [$user, $course, $program, $certification];
    }

    /**
     * Mimic the query by getting the learning items and making sure all the raw data is in the object.
     * @return []
     */
    private function get_learning_items(int $userid) {
        $items = item_helper::get_users_current_learning_items($userid);
        $items = item_helper::expand_learning_item_specialisations($items);
        \core_collator::asort_objects_by_property($items, 'fullname', \core_collator::SORT_NATURAL);
        $items = item_helper::filter_collective_learning_items($userid, $items);

        // Loop through to add component, any other transformations/pre-formatting can happen here.
        foreach ($items as $item) {
            // totara_certification, totara_program, core_course
            $item->itemtype = $item->get_type();
            $item->itemcomponent = $item->get_component();

            // Make sure we have the due date, this is for prog/cert.
            if ($item->item_has_duedate()) {
                $item->ensure_duedate_loaded();
            }

            // Make sure we have the percentage in the progress.
            if (method_exists($item, 'get_progress_percentage')) {
                $item->progress = $item->get_progress_percentage();
            }
        }

        return $items;
    }

    /**
     * Check that this only works for learning items.
     */
    public function test_resolve_learning_item_only() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);

        try {
            $this->resolve('id', 7);
            $this->fail('Only learning_item instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only learning_item objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            $this->resolve('id', ['id' => 7]);
            $this->fail('Only learning_item instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only learning_item objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            $this->resolve('id', $course);
            $this->fail('Only learning_item instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only learning_item objects are accepted: object',
                $ex->getMessage()
            );
        }

        // Check that each core instance of learning item gets resolved.
        $items = $this->get_learning_items($user->id);
        $item = array_pop($items);
        try {
            $value = $this->resolve('id', $item);
            $this->assertEquals($program->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }

        $item = array_pop($items);
        try {
            $value = $this->resolve('id', $item);
            $this->assertEquals($course->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }

        $item = array_pop($items);
        try {
            $value = $this->resolve('id', $item);
            $this->assertEquals($certification->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the learning item type resolver for the id field
     */
    public function test_resolve_id() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);
        $items = $this->get_learning_items($user->id);


        // Check that each core instance of learning item gets resolved correctly.
        $item = array_pop($items);
        $value = $this->resolve('id', $item);
        $this->assertEquals($program->id, $value);
        $this->assertTrue(is_string($value));

        $item = array_pop($items);
        $value = $this->resolve('id', $item);
        $this->assertEquals($course->id, $value);
        $this->assertTrue(is_string($value));

        $item = array_pop($items);
        $value = $this->resolve('id', $item);
        $this->assertEquals($certification->id, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the learning item type resolver for the itemtype field
     */
    public function test_resolve_itemtype() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);
        $items = $this->get_learning_items($user->id);

        // Check that each core instance of learning item gets resolved correctly.
        $item = array_pop($items);
        $value = $this->resolve('itemtype', $item);
        $this->assertEquals('program', $value);
        $this->assertTrue(is_string($value));

        $item = array_pop($items);
        $value = $this->resolve('itemtype', $item);
        $this->assertEquals('course', $value);
        $this->assertTrue(is_string($value));

        $item = array_pop($items);
        $value = $this->resolve('itemtype', $item);
        $this->assertEquals('certification', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the learning item type resolver for the itemcomponent field
     */
    public function test_resolve_itemcomponent() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);
        $items = $this->get_learning_items($user->id);

        // Check that each core instance of learning item gets resolved correctly.
        $item = array_pop($items);
        $value = $this->resolve('itemcomponent', $item);
        $this->assertEquals('totara_program', $value);
        $this->assertTrue(is_string($value));

        $item = array_pop($items);
        $value = $this->resolve('itemcomponent', $item);
        $this->assertEquals('core_course', $value);
        $this->assertTrue(is_string($value));

        $item = array_pop($items);
        $value = $this->resolve('itemcomponent', $item);
        $this->assertEquals('totara_certification', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the learning item type resolver for the shortname field
     */
    public function test_resolve_shortname() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);
        $items = $this->get_learning_items($user->id);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        // Check that each core instance of learning item gets resolved correctly.
        $item = array_pop($items);

        try {
            $value = $this->resolve('shortname', $item);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('shortname', $item, ['format' => $format]);
            $this->assertEquals('prg1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $this->assert_raw_format_not_authorized_exception('shortname', $item);

        $this->setAdminUser();
        $value = $this->resolve('shortname', $item, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('prg1', $value);

        // Get the course next.
        $this->setUser($user);
        $item = array_pop($items);

        try {
            $value = $this->resolve('shortname', $item);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('shortname', $item, ['format' => $format]);
            $this->assertEquals('crs1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $this->assert_raw_format_not_authorized_exception('shortname', $item);

        $this->setAdminUser();
        $value = $this->resolve('shortname', $item, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('crs1', $value);

        // Finally the certification.
        $this->setUser($user);
        $item = array_pop($items);

        try {
            $value = $this->resolve('shortname', $item);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('shortname', $item, ['format' => $format]);
            $this->assertEquals('crt1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $this->assert_raw_format_not_authorized_exception('shortname', $item);

        $this->setAdminUser();
        $value = $this->resolve('shortname', $item, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('crt1', $value);
    }

    /**
     * Test the learning item type resolver for the fullname field
     */
    public function test_resolve_fullname() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);
        $items = $this->get_learning_items($user->id);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        // Check that each core instance of learning item gets resolved correctly.
        $item = array_pop($items);

        try {
            $value = $this->resolve('fullname', $item);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('fullname', $item, ['format' => $format]);
            $this->assertEquals('program1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $this->assert_raw_format_not_authorized_exception('fullname', $item);

        $this->setAdminUser();
        $value = $this->resolve('fullname', $item, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('program1', $value);

        // Get the course next.
        $this->setUser($user);
        $item = array_pop($items);

        try {
            $value = $this->resolve('fullname', $item);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('fullname', $item, ['format' => $format]);
            $this->assertEquals('course1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $this->assert_raw_format_not_authorized_exception('fullname', $item);

        $this->setAdminUser();
        $value = $this->resolve('fullname', $item, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('course1', $value);

        // Finally the certification.
        $this->setUser($user);
        $item = array_pop($items);

        try {
            $value = $this->resolve('fullname', $item);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('fullname', $item, ['format' => $format]);
            $this->assertEquals('certification1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $this->assert_raw_format_not_authorized_exception('fullname', $item);

        $this->setAdminUser();
        $value = $this->resolve('fullname', $item, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('certification1', $value);
    }

    /**
     * Test the learning item type resolver for the description field
     */
    public function test_resolve_description() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);
        $items = $this->get_learning_items($user->id);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        // Check that each core instance of learning item gets resolved correctly.
        $item = array_pop($items);

        try {
            $value = $this->resolve('description', $item);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('description', $item, ['format' => $format]);
            $this->assertEquals('first program', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $this->assert_raw_format_not_authorized_exception('description', $item);

        $this->setAdminUser();
        $value = $this->resolve('description', $item, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('first program', $value);

        // Get the course next.
        $this->setUser($user);
        $item = array_pop($items);

        try {
            $value = $this->resolve('description', $item);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('description', $item, ['format' => $format]);
            if ($format == format::FORMAT_PLAIN) {
                $this->assertEquals('first course', $value);
            }
            if ($format == format::FORMAT_HTML) {
                $this->assertEquals('<div class="text_to_html">first course</div>', $value);
            }
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $this->assert_raw_format_not_authorized_exception('description', $item);

        $this->setAdminUser();
        $value = $this->resolve('description', $item, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('first course', $value);


        // Finally the certification.
        $this->setUser($user);
        $item = array_pop($items);

        try {
            $value = $this->resolve('description', $item);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('description', $item, ['format' => $format]);
            $this->assertEquals('first certification', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $this->assert_raw_format_not_authorized_exception('description', $item);

        $this->setAdminUser();
        $value = $this->resolve('description', $item, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('first certification', $value);
    }

    /**
     * Test the learning item type resolver for the description_format field
     */
    public function test_resolve_description_format() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);
        $items = $this->get_learning_items($user->id);
        $formats = [ // Note: HTML is default so not included here, and RAW is not a saved format.
            FORMAT_PLAIN => format::FORMAT_PLAIN,
            FORMAT_MARKDOWN => format::FORMAT_MARKDOWN,
            FORMAT_JSON_EDITOR => format::FORMAT_JSON_EDITOR,
        ];

        // Check that each core instance of learning item gets resolved correctly.
        $item = array_pop($items);
        $value = $this->resolve('description_format', $item);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));

        // Also check all non default values.
        foreach ($formats as $format => $expected) {
            $item->description_format = $format;
            $value = $this->resolve('description_format', $item);
            $this->assertTrue(is_string($value));
            $this->assertEquals($expected, $value);
        }

        $item = array_pop($items);
        $value = $this->resolve('description_format', $item);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));

        foreach ($formats as $format => $expected) {
            $item->description_format = $format;
            $value = $this->resolve('description_format', $item);
            $this->assertTrue(is_string($value));
            $this->assertEquals($expected, $value);
        }

        $item = array_pop($items);
        $value = $this->resolve('description_format', $item);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));

        foreach ($formats as $format => $expected) {
            $item->description_format = $format;
            $value = $this->resolve('description_format', $item);
            $this->assertTrue(is_string($value));
            $this->assertEquals($expected, $value);
        }
    }

    /**
     * Test the learning item type resolver for the progress field
     */
    public function test_resolve_progress() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);
        $items = $this->get_learning_items($user->id);

        // Check that each core instance of learning item gets resolved correctly.
        $item = array_pop($items);
        $value = $this->resolve('progress', $item);
        $progress_summary = $this->resolve('progress_summary', $item);
        $this->assertEquals(0, $value);
        $this->assertEquals('Not complete', $progress_summary);
        $this->assertTrue(is_float($value));

        $item = array_pop($items);
        $value = $this->resolve('progress', $item);
        $progress_summary = $this->resolve('progress_summary', $item);
        $this->assertEquals(null, $value);
        $this->assertEquals('Not tracked', $progress_summary);
        // Note: This course doesn't have completions set up.

        $item = array_pop($items);
        $value = $this->resolve('progress', $item);
        $progress_summary = $this->resolve('progress_summary', $item);
        $this->assertEquals(0, $value);
        $this->assertEquals('Not complete', $progress_summary);
        $this->assertTrue(is_float($value));
    }

    /**
     * Test the learning item type resolver for the url_view field
     */
    public function test_resolve_url_view() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);
        $items = $this->get_learning_items($user->id);

        // Check that each core instance of learning item gets resolved correctly.
        $item = array_pop($items);
        $value = $this->resolve('url_view', $item);
        $this->assertEquals('https://www.example.com/moodle/totara/program/view.php?id=' . $program->id, $value);
        $this->assertTrue(is_string($value));

        $item = array_pop($items);
        $value = $this->resolve('url_view', $item);
        $this->assertEquals('https://www.example.com/moodle/course/view.php?id=' . $course->id, $value);
        $this->assertTrue(is_string($value));

        $item = array_pop($items);
        $value = $this->resolve('url_view', $item);
        $this->assertEquals('https://www.example.com/moodle/totara/program/view.php?id=' . $certification->id, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the learning item type resolver for the duedate field
     */
    public function test_resolve_duedate() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);
        $items = $this->get_learning_items($user->id);

        // Check that each core instance of learning item gets resolved correctly.
        $item = array_pop($items);
        $value = $this->resolve('duedate', $item, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals(null, $value);
        $this->assertTrue(is_null($value));

        $item = array_pop($items);
        $value = $this->resolve('duedate', $item, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals(null, $value);

        $item = array_pop($items);
        $value = $this->resolve('duedate', $item, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals(null, $value);
        $this->assertTrue(is_null($value));
    }

    /**
     * @param string $field
     * @param item_base $item
     */
    private function assert_raw_format_not_authorized_exception(string $field, item_base $item) {
        try {
            $this->resolve($field, $item, ['format' => format::FORMAT_RAW]);
            $this->fail('Expected failure on RAW $format');
        } catch (\coding_exception $ex) {
            // 'description' gets transformed to 'summary' in the resolver.
            $auth_field = ($field === 'description') ? 'summary' : $field;
            $this->assertStringContainsString(
                "Not authorized to request this format for '{$auth_field}'",
                $ex->getMessage()
            );
        }
    }

}
