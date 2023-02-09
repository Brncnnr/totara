<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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

use container_workspace\discussion\discussion_helper;
use core\json_editor\node\paragraph;
use totara_comment\comment_helper;
use container_workspace\workspace;
use container_workspace\discussion\discussion;
use container_workspace\output\comment_on_discussion;

class container_workspace_output_comment_on_discussion_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_template_rendering(): void {
        global $OUTPUT;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $discussion = discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('This is the json content for discussion')]
            ]),
            null,
            FORMAT_JSON_EDITOR
        );

        $comment = comment_helper::create_comment(
            workspace::get_type(),
            discussion::AREA,
            $discussion->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('This is the comment')]
            ]),
            FORMAT_JSON_EDITOR
        );

        $template = comment_on_discussion::create($discussion, $comment);
        $rendered_content = $OUTPUT->render($template);

        $this->assertStringContainsString(fullname($comment->get_user()), $rendered_content);
        $this->assertStringContainsString($discussion->get_url()->out(), $rendered_content);

        $workspace = $discussion->get_workspace();
        $this->assertStringContainsString($workspace->get_name(), $rendered_content);
    }
}