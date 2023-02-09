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

use container_workspace\member\member_handler;

class container_workspace_delete_members_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_all_members(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create 50 of members to the workspace.
        for ($i = 0; $i < 50; $i++) {
            $user = $generator->create_user();
            $workspace_generator->add_member(
                $workspace,
                $user->id,
                $user_one->id
            );
        }

        $sql = '
            SELECT COUNT(ue.userid) FROM "ttr_user_enrolments" ue
            INNER JOIN "ttr_enrol" e ON ue.enrolid = e.id
            WHERE e.courseid = :workspace_id
        ';

        $workspace_id = $workspace->get_id();

        // 51 is including the owner of the workspace.
        self::assertEquals(
            51,
            $DB->count_records_sql($sql, ['workspace_id' => $workspace_id])
        );

        $workspace->mark_to_be_deleted(true);
        // Delete the member records of this workspace should not leaving any trailing members.
        $member_handler = new member_handler($user_one->id);
        $member_handler->delete_members_of_workspace($workspace, 10);

        self::assertEquals(0, $DB->count_records('workspace_discussion', ['course_id' => $workspace_id]));
    }
}