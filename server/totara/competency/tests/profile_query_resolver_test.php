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

use totara_competency\expand_task;
use totara_competency\models\assignment_actions;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

abstract class profile_query_resolver_test extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * Return query name
     * @return string
     */
    abstract protected function get_query_name(): string;

    public function test_no_capability() {
        $data = $this->create_data();
        $invalid_user = $this->getDataGenerator()->create_user();

        $this->setUser($invalid_user);
        $args = [
            'user_id' => $data->user->id,
            'competency_id' => $data->comp->id,
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches('/permissions/');
        $this->resolve_graphql_query($this->get_query_name(), $args);
    }

    public function test_should_fail_without_user_id() {
        $data = $this->create_data();
        $this->setUser($data->user);

        $args = [
            'competency_id' => $data->comp->id,
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('/User id is required/');
        $this->resolve_graphql_query($this->get_query_name(), $args);
    }

    protected function create_data() {
        global $DB;

        $data = new class() {
            public $user;
            public $manager;
            public $comp;
            public $assignment;
        };

        $this->setAdminUser();
        $data->user = $this->getDataGenerator()->create_user();
        $data->manager = $this->getDataGenerator()->create_user();
        // assign manager to user
        $managerja = job_assignment::create_default($data->manager->id);
        job_assignment::create_default($data->user->id, ['managerjaid' => $managerja->id]);

        /** @var \totara_hierarchy\testing\generator $totara_hierarchy_generator */
        $totara_hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $totara_hierarchy_generator->create_comp_frame([]);
        $data->comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        /** @var \totara_competency\testing\assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();
        $data->assignment = $assignment_generator->create_user_assignment($data->comp->id, $data->user->id);

        $model = new assignment_actions();
        $model->activate([$data->assignment->id]);

        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        return $data;
    }
}