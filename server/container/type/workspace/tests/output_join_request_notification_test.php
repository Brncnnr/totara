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

use container_workspace\member\member_request;
use container_workspace\output\join_request_notification;

class container_workspace_output_join_request_notification_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_render_notification(): void {
        global $OUTPUT;

        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as second user and request to join the workspace.
        $this->setUser($user_two);
        $member_request = member_request::create($workspace->get_id());

        $template = join_request_notification::create($member_request);
        $rendered_content = $OUTPUT->render($template);

        $user_two_fullname = fullname($user_two);

        $this->assertStringContainsString($user_two_fullname, $rendered_content);
        $this->assertStringContainsString($workspace->get_name(), $rendered_content);

        $a = new stdClass();
        $a->workspace_name = $workspace->get_name();
        $a->user = $user_two_fullname;

        $this->assertStringContainsString(
            get_string('member_request_message', 'container_workspace', $a),
            $rendered_content
        );
    }
}