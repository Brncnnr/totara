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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package container_workspace
 */

defined('MOODLE_INTERNAL') || die();

use container_workspace\webapi\resolver\mutation\add_bulk_audience_members;
use core\orm\query\builder;
use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_add_bulk_audience_members_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot.'/cohort/lib.php');
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

    public function test_add_bulk_audience_members_without_capability(): void {
        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $cohort1 = $generator->create_cohort();
        cohort_add_member($cohort1->id, $user2->id);

        $this->setUser($user1);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        // This user is the owner of the workspace but does not have the capability to view cohorts
        try {
            $graphql_name = $this->get_graphql_name(add_bulk_audience_members::class);
            $this->resolve_graphql_mutation(
                $graphql_name,
                [
                    'input' => [
                        'workspace_id' => $workspace->id,
                        'audience_ids' => [$cohort1->id]
                    ]
                ]
            );
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf(moodle_exception::class, $exception);
            $this->assertEquals('Invalid workspace', $exception->getMessage());
        }
        $this->assertDebuggingCalledCount(1);
    }

    public function test_add_bulk_audience_members_by_different_user_without_manage_capability(): void {
        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $cohort1 = $generator->create_cohort();
        cohort_add_member($cohort1->id, $user2->id);

        $this->setUser($user1);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        // Even if the other user has the capability he does not have manage capabilities

        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $user_role->id, SYSCONTEXTID);

        $this->setUser($user2);

        try {
            $graphql_name = $this->get_graphql_name(add_bulk_audience_members::class);
            $this->resolve_graphql_mutation(
                $graphql_name,
                [
                    'input' => [
                        'workspace_id' => $workspace->id,
                        'audience_ids' => [$cohort1->id]
                    ]
                ]
            );
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf(moodle_exception::class, $exception);
            $this->assertEquals('Invalid workspace', $exception->getMessage());
        }
        $this->assertDebuggingCalledCount(1);
    }

    public function test_add_bulk_audience_members_with_empty_audience_ids_array(): void {
        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $cohort1 = $generator->create_cohort();
        cohort_add_member($cohort1->id, $user2->id);

        $this->setUser($user1);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        try {
            $graphql_name = $this->get_graphql_name(add_bulk_audience_members::class);
            $this->resolve_graphql_mutation(
                $graphql_name,
                [
                    'input' => [
                        'workspace_id' => $workspace->id,
                        'audience_ids' => null
                    ]
                ]
            );
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf(moodle_exception::class, $exception);
            $this->assertEquals('Invalid workspace', $exception->getMessage());
        }
        $this->assertDebuggingCalledCount(1);
    }

    public function test_add_bulk_audience_members_for_non_workspace_container(): void {
        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $cohort1 = $generator->create_cohort();
        cohort_add_member($cohort1->id, $user2->id);

        $this->setAdminUser();

        $workspace = $generator->create_course();

        try {
            $graphql_name = $this->get_graphql_name(add_bulk_audience_members::class);
            $this->resolve_graphql_mutation(
                $graphql_name,
                [
                    'input' => [
                        'workspace_id' => $workspace->id,
                        'audience_ids' => [$cohort1->id]
                    ]
                ]
            );
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf(moodle_exception::class, $exception);
            $this->assertEquals('Invalid workspace', $exception->getMessage());
        }
        $this->assertDebuggingCalledCount(1);
    }

    public function test_add_bulk_audience_members(): void {
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $user_role->id, SYSCONTEXTID);

        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();
        $user6 = $generator->create_user();

        $cohort1 = $generator->create_cohort();
        $cohort2 = $generator->create_cohort();

        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort1->id, $user3->id);

        cohort_add_member($cohort2->id, $user4->id);
        cohort_add_member($cohort2->id, $user5->id);

        $this->setUser($user1);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member($workspace, $user4->id);
        $workspace_generator->add_member($workspace, $user6->id);

        $graphql_name = $this->get_graphql_name(add_bulk_audience_members::class);
        $result = $this->resolve_graphql_mutation(
            $graphql_name,
            [
                'input' => [
                    'workspace_id' => $workspace->id,
                    'audience_ids' => [$cohort1->id, $cohort2->id]
                ]
            ]
        );

        $this->assertArrayHasKey('workspace', $result);
        $this->assertInstanceOf(\container_workspace\workspace::class, $result['workspace']);
        $this->assertEquals($workspace->id, $result['workspace']->id);

        $has_task_scheduled = builder::table('task_adhoc')
            ->where('component', 'container_workspace')
            ->where_like_ends_with('classname', '\container_workspace\task\bulk_add_workspace_members_adhoc_task')
            ->exists();

        $this->assertTrue($has_task_scheduled);
        $this->assertDebuggingCalledCount(2);
    }

}