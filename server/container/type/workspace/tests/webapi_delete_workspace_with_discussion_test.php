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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\webapi\resolver\mutation\delete;

class container_workspace_webapi_delete_workspace_with_discussion_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @var stdClass|null
     */
    private $user_one;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();
        $this->user_one = $generator->create_user();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->user_one = null;
    }

    /**
     * @return \container_workspace\testing\generator
     */
    private function get_workspace_generator(): \container_workspace\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        return $workspace_generator;
    }

    /**
     * @return void
     */
    public function test_delete_public_workspace_of_other_by_admin(): void {
        global $DB;

        $this->setUser($this->user_one);
        $workspace_generator = $this->get_workspace_generator();

        $workspace = $workspace_generator->create_workspace();

        // Create a discussion for the workspace.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        $this->setAdminUser();
        $this->resolve_graphql_mutation(
            $this->get_graphql_name(delete::class),
            ['workspace_id' => $workspace->get_id()]
        );

        // Clear the user in session, in order to make sure that the task does not depending on the session user.
        $this->setUser(null);
        $this->executeAdhocTasks();

        self::assertFalse($DB->record_exists('course', ['id' => $workspace->get_id()]));
        self::assertFalse($DB->record_exists('workspace_discussion', ['id' => $discussion->get_id()]));
    }

    /**
     * @return void
     */
    public function test_delete_private_workspace_of_other_by_admin(): void {
        global $DB;

        $this->setUser($this->user_one);
        $workspace_generator = $this->get_workspace_generator();

        $workspace = $workspace_generator->create_private_workspace();
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        $this->setAdminUser();
        $this->resolve_graphql_mutation(
            $this->get_graphql_name(delete::class),
            ['workspace_id' => $workspace->get_id()]
        );

        // Clear the user in session, in order to make sure that the task does not depending on the session user.
        $this->setUser(null);
        $this->executeAdhocTasks();

        self::assertFalse($DB->record_exists('course', ['id' => $workspace->get_id()]));
        self::assertFalse($DB->record_exists('workspace_discussion', ['id' => $discussion->get_id()]));
    }

    /**
     * @return void
     */
    public function test_delete_hidden_workspace_of_other_by_admin(): void {
        global $DB;

        $this->setUser($this->user_one);
        $workspace_generator = $this->get_workspace_generator();

        $workspace = $workspace_generator->create_hidden_workspace();
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        $this->setAdminUser();

        // Delete the workspace.
        $this->resolve_graphql_mutation(
            $this->get_graphql_name(delete::class),
            ['workspace_id' => $workspace->get_id()]
        );

        $this->setUser(null);
        $this->executeAdhocTasks();

        self::assertFalse($DB->record_exists('course', ['id' => $workspace->get_id()]));
        self::assertFalse($DB->record_exists('workspace_discussion', ['id' => $discussion->get_id()]));
    }
}