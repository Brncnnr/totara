<?php
/*
 * This file is part of Totara Learn
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 */


global $CFG;

use core\collection;
use totara_competency\external;

require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * @group totara_competency
 */
class pathway_competency_services_testcase extends advanced_testcase {

    private function setup_data() {
        $data = new class() {
            public $comp;
            public $courses;
            public $user;
        };

        $this->setAdminUser();
        $GLOBALS['USER']->ignoresesskey = true;

        $generator = $this->getDataGenerator();
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $data->comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        $data->courses = collection::new(range(0, 4))->map(
            function (int $i) use ($generator): stdclass {
                $details = $i % 3 === 0 ? ['visible' => false] : [];
                return $generator->create_course($details);
            }
        )->key_by('id');

        $roleid = $generator->create_role();
        assign_capability('totara/hierarchy:updatecompetency', CAP_ALLOW, $roleid, context_system::instance());

        $data->user = $generator->create_user();
        $generator->role_assign($roleid, $data->user->id);

        return $data;
    }

    public function test_totara_competency_get_courses() {
        $data = $this->setup_data();

        $this->setUser($data->user->id);
        $GLOBALS['USER']->ignoresesskey = true;

        $res = \external_api::call_external_function(
            'totara_competency_get_courses',
            [
                'filters' => [
                    'ids' => $data->courses->keys()
                ],
                'page' => 0,
                'order' => 'fullname',
                'direction' => 'asc',
            ]
        );

        $result = $res['data'] ?? null;
        $error = $res['error'] ?? null;
        $this->assertEquals(false, $error);

        $expected_courses = $data->courses->filter(
            function (stdclass $course): bool {
                return $course->visible;
            }
        );

        $actual_courses = $result['items'] ?? [];
        $this->assertCount($expected_courses->count(), $actual_courses);

        foreach ($actual_courses as $course) {
            $expected_course = $expected_courses->item($course['id']) ?? null;
            $this->assertNotNull($expected_course);

            $this->assertEquals($expected_course->shortname , $course['shortname']);
            $this->assertEquals($expected_course->fullname , $course['fullname']);
        }
    }

    public function test_totara_competency_get_frameworks() {
        $this->setAdminUser();

        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $frameworks = [
            $generator->create_framework(null, '<span>Framework 1</span>'),
            $generator->create_framework(null, '<span>Framework 2</span>'),
            $generator->create_framework(null, '<span>Framework 3</span>'),
        ];

        $returned_frameworks = external::get_frameworks();

        $expected_data = [];
        foreach ($frameworks as $framework) {
            $expected_data[] = [
                'id'       => $framework->id,
                'fullname' => format_string($framework->fullname),
            ];
        }

        $this->assertEqualsCanonicalizing($expected_data, $returned_frameworks['items']);
    }

    public function test_totara_competency_get_and_set_linked_courses() {
        $data = $this->setup_data();

        $this->setUser($data->user->id);
        $GLOBALS['USER']->ignoresesskey = true;

        $mandatory = false;
        $linked_courses = $data->courses->map(
            function (stdclass $course) use ($mandatory): array {
                return ['id' => $course->id, 'mandatory' => $mandatory];
            }
        );

        $result = \external_api::call_external_function(
            'totara_competency_set_linked_courses',
            [
                'competency_id' => $data->comp->id,
                'courses' => $linked_courses->all()
            ]
        );
        $this->assertEquals(false, $result['error']);

        $expected_courses = $data->courses->filter(
            function (stdclass $course): bool {
                return $course->visible;
            }
        );

        $result = \external_api::call_external_function(
            'totara_competency_get_linked_courses',
            ['competency_id' => $data->comp->id]
        );
        $this->assertEquals(false, $result['error']);

        $actual_courses = $result['data']['items'] ?? [];
        $this->assertCount($expected_courses->count(), $actual_courses);

        foreach ($actual_courses as $course) {
            $expected_course = $expected_courses->item($course['id']) ?? null;
            $this->assertNotNull($expected_course);

            $this->assertEquals($mandatory, $course['mandatory']);
            $this->assertEquals($expected_course->fullname , $course['fullname']);
        }
    }
}
