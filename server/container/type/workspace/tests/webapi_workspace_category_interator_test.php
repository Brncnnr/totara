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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\workspace;

class container_workspace_webapi_workspace_category_interactor_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * It checks if user can create different kind of workspaces in category where
     * given workspace is (or default if it is not provided).
     * @return void
     */
    public function test_workspace_category_interactor(): void {
        $this->setup_user();
        $workspace = $this->create_workspace();

        $result = $this->execute_query(['workspace_id' => $workspace->get_id()]);
        self::assertNotEmpty($result);
        self::assertIsObject($result);

        // Anyone can get course_category.
        $this->setup_user();
        $result = $this->execute_query(['workspace_id' => $workspace->get_id()]);
        self::assertNotEmpty($result);
        self::assertIsObject($result);
    }

    private function execute_query(array $args = []) {
        return $this->resolve_graphql_query('container_workspace_workspace_category_interactor', $args);
    }

    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
    }

    private function create_workspace(): workspace {
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');
        return $workspace_generator->create_workspace();
    }
}