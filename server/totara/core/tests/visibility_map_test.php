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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test Totara visibility mapper
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_core_visibility_map_testcase
 *
 */
class totara_core_visibility_map_testcase extends advanced_testcase {

    public function test_course_generate_map() {
        global $DB;

        $cat = $this->getDataGenerator()->create_category();
        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $c4 = $this->getDataGenerator()->create_course(['category' => $cat->id]);

        $map = \totara_core\visibility_controller::course()->map();
        $roleid = (string)$this->getDataGenerator()->create_role();
        assign_capability($map->get_view_hidden_capability(), CAP_ALLOW, $roleid, \context_course::instance($c2->id));
        assign_capability($map->get_view_hidden_capability(), CAP_ALLOW, $roleid, \context_coursecat::instance($cat->id));

        self::assertSame(0, $DB->count_records('totara_core_course_vis_map'));

        $map->recalculate_complete_map();

        $managerid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $coursecreatorid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
        $editingtrainerid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $teacherid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);

        $actual = function() {
            global $DB;
            $actual = [];
            $records = $DB->get_records('totara_core_course_vis_map');
            foreach ($records as $record) {
                $actual[$record->courseid][] = $record->roleid;
            }
            ksort($actual);
            return $actual;
        };

        $expected = [
            SITEID => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
            $c1->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
            $c2->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c3->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c4->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
        ];

        self::assertSame($expected, $actual());

        $c5 = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $c6 = $this->getDataGenerator()->create_course([]);

        self::assertSame($expected, $actual());

        $map->recalculate_map_for_instance($c6->id);

        $expected = [
            SITEID => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
            $c1->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
            $c2->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c3->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c4->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c6->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
        ];

        self::assertSame($expected, $actual());

        $map->recalculate_map_for_role($roleid);

        $expected = [
            SITEID => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
            $c1->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
            $c2->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c3->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c4->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c5->id => [$roleid],
            $c6->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
        ];
        self::assertSame($expected, $actual());

        delete_course($c3->id, false);

        $expected = [
            SITEID => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
            $c1->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
            $c2->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c3->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c4->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c5->id => [$roleid],
            $c6->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
        ];
        self::assertSame($expected, $actual());

        $map->recalculate_complete_map();

        $expected = [
            SITEID => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
            $c1->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
            $c2->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c4->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c5->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid, $roleid],
            $c6->id => [$managerid, $coursecreatorid, $editingtrainerid, $teacherid],
        ];
        self::assertSame($expected, $actual());

    }

    public function test_program_generate_map() {
        global $DB;

        $proggen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $cat = $this->getDataGenerator()->create_category();
        $p1 = $proggen->create_program();
        $p2 = $proggen->create_program();
        $p3 = $proggen->create_program(['category' => $cat->id]);
        $p4 = $proggen->create_program(['category' => $cat->id]);

        /** @var \totara_core\local\visibility\program\map $map */
        $map = \totara_core\visibility_controller::program()->map();
        $roleid = (string)$this->getDataGenerator()->create_role();
        assign_capability($map->get_view_hidden_capability(), CAP_ALLOW, $roleid, \context_program::instance($p2->id));
        assign_capability($map->get_view_hidden_capability(), CAP_ALLOW, $roleid, \context_coursecat::instance($cat->id));

        self::assertSame(0, $DB->count_records('totara_core_program_vis_map'));

        $map->recalculate_complete_map();

        $managerid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $programcreatorid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
        $editingtrainerid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $teacherid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);

        $actual = function() {
            global $DB;
            $actual = [];
            $records = $DB->get_records('totara_core_program_vis_map');
            foreach ($records as $record) {
                $actual[$record->programid][] = $record->roleid;
            }
            ksort($actual);
            return $actual;
        };

        $expected = [
            $p1->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid],
            $p2->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p3->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p4->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
        ];

        self::assertSame($expected, $actual());

        $p5 = $proggen->create_program(['category' => $cat->id]);
        $p6 = $proggen->create_program([]);

        self::assertSame($expected, $actual());

        $map->recalculate_map_for_instance($p6->id);

        $expected = [
            $p1->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid],
            $p2->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p3->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p4->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p6->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid],
        ];

        self::assertSame($expected, $actual());

        $map->recalculate_map_for_role($roleid);

        $expected = [
            $p1->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid],
            $p2->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p3->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p4->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p5->id => [$roleid],
            $p6->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid],
        ];
        self::assertSame($expected, $actual());

        $program = new program($p3->id);
        $program->delete();

        $expected = [
            $p1->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid],
            $p2->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p3->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p4->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p5->id => [$roleid],
            $p6->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid],
        ];
        self::assertSame($expected, $actual());

        $map->recalculate_complete_map();

        $expected = [
            $p1->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid],
            $p2->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p4->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p5->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p6->id => [$managerid, $programcreatorid, $editingtrainerid, $teacherid],
        ];
        self::assertSame($expected, $actual());

    }

    public function test_certification_generate_map() {
        global $DB;

        $proggen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $cat = $this->getDataGenerator()->create_category();
        $p1 = $proggen->create_certification();
        $p2 = $proggen->create_certification();
        $p3 = $proggen->create_certification(['category' => $cat->id]);
        $p4 = $proggen->create_certification(['category' => $cat->id]);

        /** @var \totara_core\local\visibility\certification\map $map */
        $map = \totara_core\visibility_controller::certification()->map();
        $roleid = (string)$this->getDataGenerator()->create_role();
        assign_capability($map->get_view_hidden_capability(), CAP_ALLOW, $roleid, \context_program::instance($p2->id));
        assign_capability($map->get_view_hidden_capability(), CAP_ALLOW, $roleid, \context_coursecat::instance($cat->id));

        self::assertSame(0, $DB->count_records('totara_core_certification_vis_map'));

        $map->recalculate_complete_map();

        $managerid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $certificationcreatorid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
        $editingtrainerid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $teacherid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);

        $actual = function() {
            global $DB;
            $actual = [];
            $records = $DB->get_records('totara_core_certification_vis_map');
            foreach ($records as $record) {
                $actual[$record->programid][] = $record->roleid;
            }
            ksort($actual);
            return $actual;
        };

        $expected = [
            $p1->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid],
            $p2->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p3->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p4->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
        ];

        self::assertSame($expected, $actual());

        $p5 = $proggen->create_certification(['category' => $cat->id]);
        $p6 = $proggen->create_certification([]);

        self::assertSame($expected, $actual());

        $map->recalculate_map_for_instance($p6->id);

        $expected = [
            $p1->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid],
            $p2->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p3->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p4->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p6->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid],
        ];

        self::assertSame($expected, $actual());

        $map->recalculate_map_for_role($roleid);

        $expected = [
            $p1->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid],
            $p2->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p3->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p4->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p5->id => [$roleid],
            $p6->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid],
        ];
        self::assertSame($expected, $actual());

        $certification = new program($p3->id);
        $certification->delete();

        $expected = [
            $p1->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid],
            $p2->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p3->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p4->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p5->id => [$roleid],
            $p6->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid],
        ];
        self::assertSame($expected, $actual());

        $map->recalculate_complete_map();

        $expected = [
            $p1->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid],
            $p2->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p4->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p5->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid, $roleid],
            $p6->id => [$managerid, $certificationcreatorid, $editingtrainerid, $teacherid],
        ];
        self::assertSame($expected, $actual());

    }

    public function test_double_certification_generation() {
        global $DB;

        $table = new \xmldb_table('totara_core_certification_vis_map_temp');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $DB->get_manager()->create_temp_table($table);

        \totara_core\visibility_controller::certification()->map()->recalculate_complete_map();

        self::assertDebuggingCalled('Recalculation already in progress.');
    }

    public function test_double_course_generation() {
        global $DB;

        $table = new \xmldb_table('totara_core_course_vis_map_temp');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $DB->get_manager()->create_temp_table($table);

        \totara_core\visibility_controller::course()->map()->recalculate_map_for_instance(17);

        self::assertDebuggingCalled('Recalculation already in progress.');
    }

    public function test_double_program_generation() {
        global $DB;

        $table = new \xmldb_table('totara_core_program_vis_map_temp');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $DB->get_manager()->create_temp_table($table);

        \totara_core\visibility_controller::program()->map()->recalculate_map_for_role(17);

        self::assertDebuggingCalled('Recalculation already in progress.');
    }

}