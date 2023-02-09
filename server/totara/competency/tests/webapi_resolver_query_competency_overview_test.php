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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 * @subpackage test
 */

use core\orm\query\builder;
use core\webapi\execution_context;
use totara_competency\webapi\resolver\query\competency as competency_query;
use totara_competency\webapi\resolver\query\linked_courses;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the query to fetch competency data for the competency overview page
 *
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_competency_overview_testcase extends advanced_testcase {

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return execution_context::create($type, $operation);
    }

    /**
     * Need correct capability to query a competency
     */
    public function test_competency_without_permission() {
        $comp = $this->generator()->create_competency('Competency');
        $this->expectException(moodle_exception::class);
        competency_query::resolve(['competency_id' => $comp->id], $this->get_execution_context());
    }

    /**
     * Need correct capability to query a competency's linked courses
     */
    public function test_competency_linked_courses_without_permission() {
        $comp = $this->generator()->create_competency('Competency');
        $this->expectException(moodle_exception::class);
        linked_courses::resolve(['competency_id' => $comp->id], $this->get_execution_context());
    }

    /**
     * Query a competency with custom fields
     */
    public function test_competency() {
        $user = $this->getDataGenerator()->create_user();
        $user_role = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability('totara/hierarchy:viewcompetency', CAP_ALLOW, $user_role, context_system::instance()->id);

        $this->setUser($user);

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $comp_type = $hierarchy_generator->create_comp_type([
            'idnumber' => 'COMPTYPE',
        ]);

        $field_fullname = 'Custom Field Label';
        $field_values = ['One', 'Two', 'Three'];
        $field_type = 'menu';
        builder::table('comp_type_info_field')->insert([
            'typeid' => $comp_type,
            'shortname' => $field_type,
            'datatype' => $field_type,
            'sortorder' => 1,
            'hidden' => 0,
            'locked' => 0,
            'required' => 0,
            'forceunique' => 0,
            'param1' => implode("\n", $field_values),
            'fullname' => $field_fullname,
        ]);

        $hierarchy_generator->create_hierarchy_type_generic_menu([
            'hierarchy' => 'competency',
            'value' => 'One,Two,Three',
            'typeidnumber' => 'COMPTYPE',
        ]);

        $comp_name = 'Competency #1';
        $comp_idnumber = 'COMP1ID';
        $comp_description = '<span>Competency Description</span>';

        $comp = $this->generator()->create_competency($comp_name, null, [
            'idnumber' => $comp_idnumber,
            'description' => $comp_description,
        ]);

        customfield_save_data((object) [
            'id' => $comp->id,
            'typeid' => $comp_type,
            "customfield_$field_type" => 1,
        ], 'competency', 'comp_type');

        $result = competency_query::resolve(['competency_id' => $comp->id], $this->get_execution_context());

        $this->assertEquals($comp_name, $result->fullname);
        $this->assertEquals($comp_idnumber, $result->idnumber);
        $this->assertEquals($comp_description, $result->description);

        $this->assertEquals($field_type, $result->display_custom_fields[0]->type);
        $this->assertEquals($field_fullname, $result->display_custom_fields[0]->title);
        $this->assertEquals($field_values[1], $result->display_custom_fields[0]->value);
    }

    /**
     * Query a competency with linked courses
     */
    public function test_competency_linked_courses() {
        $user = $this->getDataGenerator()->create_user();
        $user_role = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability('totara/hierarchy:viewcompetency', CAP_ALLOW, $user_role, context_system::instance()->id);

        $this->setUser($user);

        $comp = $this->generator()->create_competency('Competency');

        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Course One', 'shortname' => 'course1']);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Course Two', 'shortname' => 'course2']);

        builder::table('comp_criteria')->insert([
            'competencyid' => $comp->id,
            'itemtype' => 'coursecompletion',
            'itemmodule' => null,
            'iteminstance' => $course1->id,
            'timecreated' => 1,
            'timemodified' => 1,
            'usermodified' => 2,
            'linktype' => 0,
        ]);
        builder::table('comp_criteria')->insert([
            'competencyid' => $comp->id,
            'itemtype' => 'coursecompletion',
            'itemmodule' => null,
            'iteminstance' => $course2->id,
            'timecreated' => 2,
            'timemodified' => 2,
            'usermodified' => 2,
            'linktype' => 1,
        ]);

        $result = linked_courses::resolve(['competency_id' => $comp->id], $this->get_execution_context());

        $this->assertEquals($course1->id, $result[0]->id);
        $this->assertEquals($course2->id, $result[1]->id);
        $this->assertEquals('Course One', $result[0]->fullname);
        $this->assertEquals('Course Two', $result[1]->fullname);
        $this->assertEquals(0, $result[0]->linktype);
        $this->assertEquals(1, $result[1]->linktype);
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }
}
