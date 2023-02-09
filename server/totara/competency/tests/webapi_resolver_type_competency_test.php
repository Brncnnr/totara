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

use core\date_format;
use core\format;
use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_hierarchy\entity\competency as competency_entity;
use totara_hierarchy\entity\competency_framework;
use totara_hierarchy\entity\scale;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_competency_testcase extends testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_competency';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Accepting only entities.');

        $this->resolve_graphql_type(self::QUERY_TYPE, 'id', new stdClass());
    }

    public function test_resolve_successful() {
        $data = $this->create_data();

        // resolve id
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'id', $data->competency);
        $this->assertEquals($data->competency->id, $result);

        // resolve idnumber
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'idnumber', $data->competency);
        $this->assertEquals($data->competency->idnumber, $result);

        // resolve display_name
        $this->assertEquals(
            'Comp B',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'display_name', $data->competency, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            '<p>Comp B</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'display_name', $data->competency, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'Comp B',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'display_name', $data->competency, ['format' => format::FORMAT_PLAIN])
        );

        // resolve timemodified
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'timemodified', $data->competency,['format' => date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals($data->competency->timemodified, $result);

        // resolve timecreated
        $result = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'timecreated', $data->competency, ['format' => date_format::FORMAT_TIMESTAMP]
        );
        $this->assertEquals($data->competency->timecreated, $result);

        // resolve framework
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'framework', $data->competency);
        $this->assertInstanceOf(competency_framework::class, $result);

        // resolve path
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'path', $data->competency);
        $this->assertEquals($data->competency->path, $result);

        // resolve parent
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'parent', $data->competency);
        $this->assertInstanceOf(competency_entity::class, $result);

        // resolve visible
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'visible', $data->competency);
        $this->assertEquals($data->competency->visible, $result);

        // resolve children
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'children', $data->competency);
        $this->assertEquals(count($data->competency->children), count($result));

        // resolve type
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'type', $data->competency);
        $this->assertEquals($data->competency->type, $result);

        // resolve typeid
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'typeid', $data->competency);
        $this->assertEquals($data->competency->typeid, $result);

        // resolve display_custom_fields
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'display_custom_fields', $data->competency);
        $this->assertEquals($data->competency->display_custom_fields, $result);
    }

    public function test_resolve_shortname() {
        $data = $this->create_data();

        $this->setUser($data->user);
        // without capability
        $this->assertEquals(
            'test short name',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->competency, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            null, $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->competency, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'test short name',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->competency, ['format' => format::FORMAT_PLAIN])
        );

        // with capability 'totara/hierarchy:updatecompetency'
        $this->assign_cap('totara/hierarchy:updatecompetency', $data->user->id);
        $this->assert_shortname_with_correct_capability($data);

        // with capability 'totara/hierarchy:updatecompetency' and 'totara/hierarchy:createcompetency'
        $this->assign_cap('totara/hierarchy:createcompetency', $data->user->id);
        $this->assert_shortname_with_correct_capability($data);

        // with capability 'totara/hierarchy:createcompetency'
        $this->assign_cap('totara/hierarchy:updatecompetency', $data->user->id, true);
        $this->assert_shortname_with_correct_capability($data);
    }

    public function test_resolve_fullname() {
        $data = $this->create_data();
        $this->setUser($data->user->id);
        // without capability
        $this->assertEquals(
            'Comp B',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->competency, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            null, $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->competency, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'Comp B',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->competency, ['format' => format::FORMAT_PLAIN])
        );

        // with capability 'totara/hierarchy:updatecompetency'
        $this->assign_cap('totara/hierarchy:updatecompetency', $data->user->id);
        $this->assert_fullname_with_correct_capability($data);

        // with capability 'totara/hierarchy:updatecompetency' and 'totara/hierarchy:createcompetency'
        $this->assign_cap('totara/hierarchy:createcompetency', $data->user->id);
        $this->assert_fullname_with_correct_capability($data);

        // with capability 'totara/hierarchy:createcompetency'
        $this->assign_cap('totara/hierarchy:updatecompetency', $data->user->id);
        $this->assert_fullname_with_correct_capability($data);
    }

    public function test_resolve_description() {
        $data = $this->create_data();
        $this->setUser($data->user);
        // without capability
        $this->assertEquals(
            '<p>Description</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->competency, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            null, $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->competency, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            "Description\n",
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->competency, ['format' => format::FORMAT_PLAIN])
        );


        // with capability 'totara/hierarchy:updatecompetency'
        $this->assign_cap("totara/hierarchy:updatecompetency", $data->user->id);
        $this->assert_description_with_correct_capability($data);

        // with capability 'totara/hierarchy:updatecompetency' and 'totara/hierarchy:createcompetency'
        $this->assign_cap("totara/hierarchy:createcompetency", $data->user->id);
        $this->assert_description_with_correct_capability($data);

        // with capability 'totara/hierarchy:createcompetency'
        $this->assign_cap("totara/hierarchy:updatecompetency", $data->user->id, true);
        $this->assert_description_with_correct_capability($data);
    }

    public function test_resolve_frameworkid() {
        $data = $this->create_data();
        // without capability
        $this->setUser($data->user);
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'frameworkid', $data->competency);
        $this->assertNull($result);
        // with capability
        $this->assign_cap('totara/hierarchy:viewcompetencyframeworks', $data->user->id);
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'frameworkid', $data->competency);
        $this->assertEquals($data->framework->id, $result);
    }

    public function test_resolve_parentid() {
        $data = $this->create_data();
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'parentid', $data->competency);

        $this->assertEquals($data->competency_parent->id, $result);
    }

    public function test_resolve_assign_availability() {
        $data = $this->create_data();

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'assign_availability', $data->competency);
        $this->assertEquals($data->competency->assign_availability, $result);

        advanced_feature::disable('competency_assignment');
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'assign_availability', $data->competency);
        $this->assertNull($result);
    }

    public function test_resolve_aggregationmethod() {
        $data = $this->create_data();

        advanced_feature::disable('competency_assignment');
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'aggregationmethod', $data->competency);
        $this->assertEquals($data->competency->aggregationmethod, $result);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'aggregation_method', $data->competency);
        $this->assertEquals($data->competency->aggregationmethod, $result);

        advanced_feature::enable('competency_assignment');
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'aggregationmethod', $data->competency);
        $this->assertNull($result);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'aggregation_method', $data->competency);
        $this->assertNull($result);
    }

    private function create_data() {
        $this->setAdminUser();
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $data = new class() {
            public $competency;
            public $competency_parent;
            public $courses = [];
            public $scale;
            public $framework;
            public $scalevalues = [];
            public $user;
            public $pathway;
        };

        $scale = $generator->create_scale(
            'Test scale',
            'Test scale',
            [
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );

        $data->user = $this->getDataGenerator()->create_user();
        $data->scale = new scale($scale->id);
        $data->scalevalues = $data->scale
            ->sorted_values_high_to_low
            ->key_by('sortorder')
            ->all(true);

        $data->framework = $generator->create_framework($data->scale);
        $data->competency_parent = $generator->create_competency('Comp A');
        $data->competency = $generator->create_competency(
            '<p>Comp B</p>', $data->framework->id,
            [
                'parentid'    => $data->competency_parent->id,
                'shortname'   => "<p>test short name</p>",
                'description' => "<p>Description</p><script>alert('This shouldn\'t be here\')</script>",
            ]
        );

        global $DB;
        $DB->insert_record(
            'comp_assign_availability',
            ['comp_id' => $data->competency->id, 'availability' => competency_entity::ASSIGNMENT_CREATE_SELF]
        );

        return $data;
    }

    /**
     * assign capability to user
     *
     * @param string $capability
     * @param int $user_id
     * @param bool|null $unassign
     * @throws coding_exception
     * @throws dml_exception
     */
    private function assign_cap(string $capability, int $user_id, ?bool $unassign = false) {
        $roleid = $this->getDataGenerator()->create_role();
        $syscontext = context_system::instance();
        if ($unassign) {
            unassign_capability($capability, $roleid, $syscontext);
        } else {
            assign_capability($capability, CAP_ALLOW, $roleid, $syscontext);
        }
        role_assign($roleid, $user_id, $syscontext);
    }

    /**
     * @param object $data
     */
    private function assert_description_with_correct_capability(object $data): void {
        $this->assertEquals(
            "<p>Description</p>", $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->competency, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            "<p>Description</p><script>alert('This shouldn\'t be here\')</script>",
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->competency, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals("Description\n", $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->competency, ['format' => format::FORMAT_PLAIN]));
    }

    /**
     * @param object $data
     */
    private function assert_shortname_with_correct_capability(object $data): void {
        $this->assertEquals('test short name', $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->competency, ['format' => format::FORMAT_HTML]));
        $this->assertEquals(
            '<p>test short name</p>', $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->competency, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals('test short name', $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->competency, ['format' => format::FORMAT_PLAIN]));
    }

    /**
     * @param object $data
     */
    private function assert_fullname_with_correct_capability(object $data): void {
        $this->assertEquals('Comp B', $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->competency, ['format' => format::FORMAT_HTML]));
        $this->assertEquals(
            '<p>Comp B</p>', $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->competency, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals('Comp B', $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->competency, ['format' => format::FORMAT_PLAIN]));
    }
}