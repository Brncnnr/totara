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
use totara_hierarchy\entity\scale;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_competency_framework_testcase extends testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_competency_framework';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Accept competency framework entity only.');
        $this->resolve_graphql_type(self::QUERY_TYPE, 'id', new stdClass());
    }

    public function test_resolve_successful() {
        $data = $this->create_data();

        // resolve id
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'id', $data->framework);
        $this->assertEquals($data->framework->id, $result);

        // resolve idnumber
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'idnumber', $data->framework);
        $this->assertEquals($data->framework->idnumber, $result);

        // resolve display_name
        $this->assertEquals(
            $data->framework->display_name,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'display_name', $data->framework, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            $data->framework->display_name,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'display_name', $data->framework, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            $data->framework->display_name,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'display_name', $data->framework, ['format' => format::FORMAT_PLAIN])
        );

        // resolve timecreated
        $result = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'timecreated', $data->framework, ['format' => date_format::FORMAT_TIMESTAMP]
        );
        $this->assertEquals($data->framework->timecreated, $result);

        // resolve timemodified
        $result = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'timemodified', $data->framework, ['format' => date_format::FORMAT_TIMESTAMP]
        );
        $this->assertEquals($data->framework->timemodified, $result);

        // resolve sortorder
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'sortorder', $data->framework);
        $this->assertEquals($data->framework->sortorder, $result);

        // resolve visible
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'visible', $data->framework);
        $this->assertEquals($data->framework->visible, $result);

        // resolve usermodified
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'usermodified', $data->framework);
        $this->assertEquals($data->framework->usermodified, $result);

        // resolve hidecustomfields
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'hidecustomfields', $data->framework);
        $this->assertEquals($data->framework->hidecustomfields, $result);

        // resolve competencies
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'competencies', $data->framework);
        $this->assertCount(1, $result);
        $this->assertEquals($data->competency->id, $result->first()->id);
    }

    public function test_resolve_shortname() {
        $data = $this->create_data();
        $this->setUser($data->user);
        // without capability
        $this->assertEquals(
            $data->framework->shortname,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->framework, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            null, $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->framework, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            $data->framework->shortname,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->framework, ['format' => format::FORMAT_PLAIN])
        );
        // with capability
        $this->assign_cap("totara/hierarchy:updatecompetencyframeworks", $data->user->id);
        $this->assertEquals(
            $data->framework->shortname,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->framework, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            $data->framework->shortname,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->framework, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            $data->framework->shortname,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $data->framework, ['format' => format::FORMAT_PLAIN])
        );
    }

    public function test_resolve_fullname() {
        $data = $this->create_data();
        $this->setUser($data->user);
        // without capability
        $this->assertEquals(
            $data->framework->fullname,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->framework, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            null, $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->framework, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            $data->framework->fullname,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->framework, ['format' => format::FORMAT_PLAIN])
        );
        // with capability
        $this->assign_cap("totara/hierarchy:updatecompetencyframeworks", $data->user->id);
        $this->assertEquals(
            $data->framework->fullname,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->framework, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            $data->framework->fullname,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->framework, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            $data->framework->fullname,
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $data->framework, ['format' => format::FORMAT_PLAIN])
        );

    }

    public function test_resolve_description() {
        $data = $this->create_data();
        $this->setUser($data->user);
        // without capability
        $this->assertEquals(
            "<p>Description</p>",
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->framework, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            null, $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->framework, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            "Description\n",
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->framework, ['format' => format::FORMAT_PLAIN])
        );
        // with capability
        $this->assign_cap("totara/hierarchy:updatecompetencyframeworks", $data->user->id);
        $this->assertEquals(
            "<p>Description</p>",
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->framework, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            "<p>Description</p><script>alert('This shouldn\'t be here\')</script>",
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->framework, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            "Description\n",
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->framework, ['format' => format::FORMAT_PLAIN])
        );
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
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );

        $data->user = $this->getDataGenerator()->create_user();
        $data->scale = new scale($scale->id);
        $data->scalevalues = $data->scale
            ->sorted_values_high_to_low
            ->key_by('sortorder')
            ->all(true);

        $data->framework = $generator
            ->create_framework(
                $data->scale,
                'fw one',
                "<p>Description</p><script>alert('This shouldn\'t be here\')</script>"
            );
        $data->framework->shortname = 'test short name';
        $data->competency_parent = $generator->create_competency('Comp A');
        $data->competency = $generator->create_competency('Comp', $data->framework->id);

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

}