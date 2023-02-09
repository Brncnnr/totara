<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package container_workspace
 */

use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\enrol\manager;

/**
 * @group container_workspace
 */
class container_workspace_webapi_query_audiences_testcase extends testcase {

    use webapi_phpunit_helper;

    /**
     * Workspace id.
     *
     * @var int
     */
    private $workspace_id;

    /**
     * Name of query.
     *
     * @var string
     */
    private const QUERY_NAME = "container_workspace_audiences";

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();
        $this->workspace_id = $workspace->id;
        $enrol = manager::from_workspace($workspace);

        $audience_names = [
            "Architecture" => 5,
            "Finance" => 7,
            "Health" => 4,
            "Business" => 2,
        ];

        foreach ($audience_names as $audience_name => $no_of_users) {
            $audience = $generator->create_cohort([
                'name' => $audience_name,
                'idnumber' => $audience_name,
            ]);
            for ($i = 0; $i <= $no_of_users; $i++) {
                $user = $generator->create_user();
                cohort_add_member($audience->id, $user->id);
            }
            $enrol->enrol_audiences([$audience->id]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void {
        $this->workspace_id = null;
    }

    /**
     * Test query successfully executes.
     *
     * @return void
     */
    public function test_successful_query(): void {
        $this->setAdminUser();

        $result = $this->resolve_graphql_query(
            self::QUERY_NAME,
            [
                'input' => [
                    'workspace_id' => $this->workspace_id,
                ]
            ]
        );
        $this->assertArrayHasKey('next_cursor', $result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * Test query fails when an invalid workspace id is provided.
     *
     * @return void
     */
    public function test_invalid_workspace_id(): void {
        $this->setAdminUser();

        $this->expectException(moodle_exception::class);
        $this->resolve_graphql_query(
            self::QUERY_NAME,
            [
                'input' => [
                    'workspace_id' => 0,
                ]
            ]
        );
    }

    /**
     * Test query fails when the workspace feature is disabled.
     *
     * @return void
     */
    public function test_container_workspace_feature_disabled(): void {
        advanced_feature::disable('container_workspace');
        $this->setAdminUser();

        $this->expectException(feature_not_available_exception::class);
        $this->resolve_graphql_query(
            self::QUERY_NAME,
            [
                'input' => [
                    'workspace_id' => 0,
                ]
            ]
        );
    }

    /**
     * Test query fails when user does not have the capability to manage workspace audiences.
     *
     * @return void
     */
    public function test_user_without_capability_to_view_workspace_audiences(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user);
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("Invalid workspace");

        $this->resolve_graphql_query(
            self::QUERY_NAME,
            [
                'input' => [
                    'workspace_id' => $this->workspace_id,
                ]
            ]
        );
    }
}
