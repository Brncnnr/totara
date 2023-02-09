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
use container_workspace\member\member;
use container_workspace\workspace;

class container_workspace_webapi_remove_members_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_remove_members_by_owner(): void {
        global $DB;
        $this->setup_user();
        $workspace = $this->create_workspace();
        $member_one = $this->getDataGenerator()->create_user();
        $member_two = $this->getDataGenerator()->create_user();

        member::added_to_workspace($workspace, $member_one->id);
        member::added_to_workspace($workspace, $member_two->id);

        $result = $this->execute_mutation([
            'workspace_id' => $workspace->get_id(),
            'user_id' => $member_one->id
        ]);
        self::assertTrue($result);

        $sql = '
            SELECT ue.*, e.enrol 
            FROM "ttr_user_enrolments" ue
            INNER JOIN "ttr_enrol" e ON e.id = ue.enrolid
            WHERE ue.userid = :user_id
            AND e.courseid = :workspace_id
        ';

        $record = $DB->get_record_sql(
            $sql,
            [
                'user_id' => $member_one->id,
                'workspace_id' => $workspace->get_id(),
            ],
            IGNORE_MISSING
        );

        self::assertEquals($record->userid, $member_one->id);
    }

    /**
     * @return void
     */
    public function test_remove_members_by_non_member(): void {
        $this->setup_user();
        $workspace = $this->create_workspace();
        $member_one = $this->getDataGenerator()->create_user();
        member::added_to_workspace($workspace, $member_one->id);

        $this->setup_user();

        $this->expectException(require_login_exception::class);
        $this->execute_mutation([
            'workspace_id' => $workspace->get_id(),
            'user_id' => $member_one->id
        ]);
    }

    /**
     * @return void
     */
    public function test_remove_members_by_admin(): void {
        $this->setup_user();
        $workspace = $this->create_workspace();
        $member_one = $this->getDataGenerator()->create_user();
        member::added_to_workspace($workspace, $member_one->id);

        $this->setAdminUser();
        $result = $this->execute_mutation([
            'workspace_id' => $workspace->get_id(),
            'user_id' => $member_one->id
        ]);
        self::assertTrue($result);
    }

    /**
     * @return void
     */
    public function test_remove_members_by_member(): void {
        $this->setup_user();
        $workspace = $this->create_workspace();
        $member_one = $this->getDataGenerator()->create_user();
        $member_two = $this->getDataGenerator()->create_user();

        member::added_to_workspace($workspace, $member_one->id);
        member::added_to_workspace($workspace, $member_two->id);

        $this->setUser($member_two);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Coding error detected, it must be fixed by a programmer: No capability to remove the member');
        $this->execute_mutation([
            'workspace_id' => $workspace->get_id(),
            'user_id' => $member_one->id
        ]);
    }

    /**
     * @param array|null $args
     * @return mixed|null
     */
    private function execute_mutation(?array $args = []) {
        return $this->resolve_graphql_mutation('container_workspace_remove_member', $args);
    }

    /**
     *  @return void
     */
    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
    }

    /**
     * @return \container_workspace\workspace
     */
    private function create_workspace(): workspace {
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');
        return $workspace_generator->create_workspace();
    }
}