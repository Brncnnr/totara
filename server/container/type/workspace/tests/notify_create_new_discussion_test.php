<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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

use container_workspace\discussion\discussion_helper;
use container_workspace\member\member;
use core\json_editor\node\emoji;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use core_phpunit\language_pack_faker_trait;

class container_workspace_notify_create_new_discussion_testcase extends advanced_testcase {

    use language_pack_faker_trait;

    /**
     * @return void
     */
    public function test_new_discussion_created(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user(['firstname' => 'First', 'lastname' => 'Last']);
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('Workspace');

        // Clear the adhoc tasks.
        $this->executeAdhocTasks();

        // Add the second user to the workspace.
        $member = member::added_to_workspace($workspace, $user_two->id);

        $this->executeAdhocTasks();

        // Create a discussion in the workspace which
        $discussion = discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text("Discussion 101"),
                    [
                        'type' => emoji::get_type(),
                        'attrs' => [
                            'shortcode' => '1F60A'
                        ]
                    ]
                ],
            ]),
            null,
            FORMAT_JSON_EDITOR,
            $user_one->id
        );

        // Clear the adhoc tasks.
        $this->executeAdhocTasks();

        $message = builder::table('notifications')
            ->where('useridto', $user_two->id)
            ->where_like_starts_with('subject', 'New discussion by')
            ->one(true);

        $this->assertStringContainsString(
            'New discussion by '.fullname($user_one).' in '.$workspace->fullname,
            $message->subject
        );
        $this->assertStringContainsString('Discussion 101', $message->fullmessage);
        $this->assertStringContainsString('😊', $message->fullmessage);
        $this->assertStringContainsString('Discussion 101', $message->fullmessagehtml);
        $this->assertStringContainsString('😊', $message->fullmessagehtml);
    }


}