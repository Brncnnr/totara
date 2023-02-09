<?php
/**
 * This file is part of Totara Learn
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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_competency
 */

use core\format;
use totara_competency\linked_courses;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_linked_course_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_linked_course';

    public function test_resolve_successful() {
        [$linked_courses, $course] = $this->create_linked_course(['fullname' => '<p>test course</p>']);
        $linked_course_one = array_pop($linked_courses);

        // resolve fullname
        $this->assertEquals(
            'test course',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $linked_course_one, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            '<p>test course</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $linked_course_one, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'test course',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $linked_course_one, ['format' => format::FORMAT_PLAIN])
        );

        // resolve course_id
        $this->assertEquals($course->id, $this->resolve_graphql_type(self::QUERY_TYPE, 'course_id', $linked_course_one));

        // resolve is_mandatory
        $this->assertEquals(
            linked_courses::LINKTYPE_MANDATORY, $this->resolve_graphql_type(self::QUERY_TYPE, 'is_mandatory', $linked_course_one)
        );
    }

    public function test_resolve_unknown_field() {
        [$linked_courses] = $this->create_linked_course();
        $linked_course = array_pop($linked_courses);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown field');

        $this->resolve_graphql_type(self::QUERY_TYPE, 'unknown_field', $linked_course);
    }

    private function create_linked_course(array $course_param = []) {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course($course_param);

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        linked_courses::set_linked_courses(
            $comp->id,
            [
                ['id' => $course->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        $linked_courses = linked_courses::get_linked_courses($comp->id);

        return [$linked_courses, $course];
    }

}