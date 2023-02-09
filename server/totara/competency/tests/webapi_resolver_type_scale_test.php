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
use totara_competency\models\scale as scale_model;
use totara_hierarchy\entity\scale as scale_entity;
use totara_hierarchy\entity\scale_value;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_scale_testcase extends testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_scale';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Please pass a scale value model');

        $this->resolve_graphql_type(self::QUERY_TYPE, 'id', new stdClass());
    }

    public function test_resolve_successful() {
        $data = $this->create_data();

        // resolve id
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'id', $data->scale);
        $this->assertEquals($data->scale->id, $result);

        // resolve timemodified
        $result = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'timemodified', $data->scale, ['format' => date_format::FORMAT_TIMESTAMP]
        );
        $this->assertEquals($data->scale->timemodified, $result);

        $result = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'usermodified', $data->scale, ['format' => date_format::FORMAT_TIMESTAMP]
        );
        $this->assertEquals($data->scale->usermodified, $result);

        // resolve defaultid
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'defaultid', $data->scale);
        $this->assertEquals($data->scale->defaultid, $result);

        // resolve values
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'values', $data->scale);
        $this->assertCount(5, $result);
        $this->assertContainsOnlyInstancesOf(scale_value::class, $result);
    }

    public function test_resolve_name() {
        $data = $this->create_data();
        $this->setUser($data->user);
        // without capability
        $this->assertEquals(
            'Test scale', $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $data->scale, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            null, $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $data->scale, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'Test scale', $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $data->scale, ['format' => format::FORMAT_PLAIN])
        );
        // with capability
        $this->assign_cap('totara/hierarchy:viewcompetencyscale', $data->user->id);
        $this->assertEquals(
            '<p>Test scale</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $data->scale, ['format' => format::FORMAT_RAW])
        );
    }

    public function test_resolve_description() {
        $data = $this->create_data();
        $this->setUser($data->user);
        // without capability
        $this->assertEquals(
            'description',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->scale, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            null, $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->scale, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'description',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->scale, ['format' => format::FORMAT_PLAIN])
        );
        // with capability
        $this->assign_cap('totara/hierarchy:viewcompetencyscale', $data->user->id);
        $this->assertEquals(
            '<p>description</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data->scale, ['format' => format::FORMAT_RAW])
        );
    }

    private function create_data() {
        $data = new class {
            /** @var scale_entity */
            public $scale;
            /** @var stdClass */
            public $user;
        };

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator =  $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $scale = $hierarchy_generator->create_scale(
            'comp',
            ['name' => '<p>Test scale</p>', 'description' => '<p>description</p>'],
            [
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );

        /** @var scale_entity $scale */
        $data->scale = scale_model::load_by_id($scale->id);

        $data->user = $this->getDataGenerator()->create_user();

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
