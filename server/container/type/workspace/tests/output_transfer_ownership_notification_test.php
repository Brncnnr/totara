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

use container_workspace\output\transfer_ownership_notification;

class container_workspace_output_transfer_ownership_notification_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_rendering(): void {
        global $OUTPUT;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $admin_user = get_admin();
        $template = transfer_ownership_notification::create($workspace, $admin_user->id);

        $rendered_content = $OUTPUT->render($template);
        $this->assertStringContainsString(
            fullname($admin_user),
            $rendered_content
        );

        $this->assertStringContainsString(
            $workspace->get_name(),
            $rendered_content
        );

        $this->assertStringContainsString(
            get_string(
                'transfer_ownership_message',
                'container_workspace',
                [
                    'author' => fullname($admin_user),
                    'workspace' => $workspace->get_name()
                ]
            ),
            $rendered_content
        );

        $this->assertStringContainsString(
            $workspace->get_workspace_url()->out(),
            $rendered_content
        );
    }
}