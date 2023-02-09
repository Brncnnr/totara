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

use core\orm\query\builder;
use totara_competency\entity\assignment;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;

abstract class scale_query_resolver_test extends advanced_testcase {

    protected const ID_ARG_EXCEPTION_MESSAGE = '/Please provide either scale id OR competency id/';

    /**
     * Return query name
     * @return string
     */
    abstract protected function get_query_name(): string;

    use webapi_phpunit_helper;

    public function test_no_capability() {
        $data = $this->create_data();

        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);

        $args = [
            'id' => $data->scale->id,
        ];

        $user_role = builder::get_db()->get_record('role', ['shortname' => 'user']);
        unassign_capability('totara/hierarchy:viewcompetency', $user_role->id, context_system::instance()->id);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches('/permissions/');
        $this->resolve_graphql_query($this->get_query_name(), $args);
    }

    public function test_no_parameter() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $args = [];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches(static::ID_ARG_EXCEPTION_MESSAGE);
        $this->resolve_graphql_query($this->get_query_name(), $args);
    }

    public function test_more_than_one_parameter() {
        $data = $this->create_data();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $args = [
            'id' => $data->scale->id,
            'competency_id' => $data->comp1->id,
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches(static::ID_ARG_EXCEPTION_MESSAGE);
        $this->resolve_graphql_query($this->get_query_name(), $args);
    }

    protected function create_data() {
        $this->setAdminUser();
        $assign_generator = $this->getDataGenerator()
            ->get_plugin_generator('totara_competency')
            ->assignment_generator();

        $data = new class() {
            public $scale;
            public $fw1;
            public $comp1;
            public $assignment1;
        };

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $data->scale = $hierarchy_generator->create_scale('comp');

        $data->fw1 = $hierarchy_generator->create_comp_frame(['scale' => $data->scale->id]);

        $data->comp1 = $this->getDataGenerator()->get_plugin_generator('totara_competency')->create_competency(
            null, $data->fw1->id, [
                'shortname' => 'c-chef',
                'fullname' => 'Chef proficiency',
                'description' => 'Bossing around',
                'idnumber' => 'cook-chef-c',
            ]
        );

        $data->assignment1 = $assign_generator->create_user_assignment(
            $data->comp1->id,
            null,
            ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]
        );

        return $data;
    }

}