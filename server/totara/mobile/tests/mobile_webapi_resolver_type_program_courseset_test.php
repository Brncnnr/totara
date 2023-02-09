<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_course\user_learning\item as course_item;

/**
 * Tests the totara core program type resolver.
 */
class totara_mobile_webapi_resolver_type_program_courseset_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $courseset, array $args = [], $ec = null) {

        $excontext = $this->get_execution_context();
        if (is_object($courseset) && !empty($courseset->programid)) {
            $pcontext = \context_program::instance($courseset->programid);
            $excontext->set_relevant_context($pcontext);
        }

        return \totara_mobile\webapi\resolver\type\program_courseset::resolve(
            $field,
            $courseset,
            $args,
            $ec ?? $excontext
        );
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    /**
     * Create some users and various learning items.
     * @return array
     */
    private function create_faux_programs() {
        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $user = $this->getDataGenerator()->create_user();

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();

        $program = $prog_gen->create_program(['shortname' => 'prg1', 'fullname' => 'program1', 'summary' => 'first program']);
        $prog_gen->add_courses_and_courseset_to_program($program, [[$c1, $c2], [$c3]], CERTIFPATH_STD);
        $prog_gen->assign_program($program->id, [$user->id]);

        return [$user, $program];
    }

    /**
     * Check that this only works for learning items.
     */
    public function test_resolve_stdclass_only() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        try {
            $this->resolve('id', 7);
            $this->fail('Only courseset records should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only program courseset objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            $this->resolve('id', ['id' => 7]);
            $this->fail('Only courseset records should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only program courseset objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            $coursesets = $program->content->get_course_sets();
            $cs1 = array_shift($coursesets);
            $value = $this->resolve('id', $cs1);
            $this->assertEquals($cs1->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the program type resolver for the id field
     */
    public function test_resolve_id() {
        list($user, $program) = $this->create_faux_programs();
        $coursesets = $program->content->get_course_sets();
        $cs1 = array_shift($coursesets);

        $this->setUser($user);

        $value = $this->resolve('id', $cs1);
        $this->assertEquals($cs1->id, $value);
        $this->assertTrue(is_numeric($value));
    }

    /**
     * Test the program type resolver for the label field
     */
    public function test_resolve_label() {
        list($user, $program) = $this->create_faux_programs();
        $coursesets = $program->content->get_course_sets();
        $cs1 = array_shift($coursesets);

        $this->setUser($user);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('label', $cs1);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('label', $cs1, ['format' => $format]);
            $this->assertEquals($cs1->label, $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('label', $cs1, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('label', $cs1, ['format' => format::FORMAT_RAW]);
        $this->assertEquals($cs1->label, $value);
    }

    public function test_resolve_nextsetoperator() {
        list($user, $program) = $this->create_faux_programs();
        $coursesets = $program->content->get_course_sets();

        $cs1 = array_shift($coursesets);
        $value = $this->resolve('nextsetoperator', $cs1);
        $this->assertEquals('THEN', $value);

        // Note: this should really be null, but the program generator sets the nextsetoperator value in bulk.
        $cs2 = array_shift($coursesets);
        $value = $this->resolve('nextsetoperator', $cs2);
        $this->assertEquals('THEN', $value);

        // It would be good to test the and/or values, but the generator seems rather limited in that regard.
    }

    public function test_resolve_courses() {
        list($user, $program) = $this->create_faux_programs();
        $coursesets = $program->content->get_course_sets();
        $cs1 = array_shift($coursesets);

        $this->setUser($user);

        $value = $this->resolve('courses', $cs1);
        $this->assertEquals('array', getType($value));
        $this->assertCount(2, $value);

        $expectedids = [];
        $expectednames = [];

        // Check course set 1 has the expected courses in it.
        $courses = $cs1->get_courses();
        foreach ($courses as $expected) {
            $expectedids[] = $expected->id;
            $expectednames[] = $expected->fullname;
        }

        foreach ($value as $course) {
            $this->assertInstanceOf(course_item::class, $course);
            $this->assertContains($course->id, $expectedids);
            $this->assertContains($course->fullname, $expectednames);
        }

        // Do a quick size check on course set 2.
        $cs2 = array_shift($coursesets);
        $val2 = $this->resolve('courses', $cs2);
        $this->assertEquals('array', getType($val2));
        $this->assertCount(1, $val2);
    }
}
