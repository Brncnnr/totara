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

use container_workspace\member\member_request;
use container_workspace\output\accept_notification;

class container_workspace_accept_notification_output_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_render_template(): void {
        global $OUTPUT;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as user two and create a request.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $member_request = member_request::create($workspace->get_id(), $user_two->id);

        $template = accept_notification::create($member_request);
        $content = $OUTPUT->render($template);

        $this->assertStringContainsString(
            get_string(
                'approved_request_message',
                'container_workspace',
                $workspace->get_name()
            ),
            $content
        );

        $this->assertStringContainsString(
            $workspace->get_workspace_url()->out(),
            $content
        );

        $this->assertStringContainsString(
            get_string('view_workspace', 'container_workspace'),
            $content
        );
    }
}