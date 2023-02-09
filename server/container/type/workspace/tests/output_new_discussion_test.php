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
use container_workspace\output\create_new_discussion;
use core\json_editor\node\image;
use core\json_editor\node\paragraph;

class container_workspace_output_new_discussion_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_template_rendering(): void {
        global $OUTPUT, $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();

        $draft_id = file_get_unused_draft_itemid();

        $file_record = new stdClass();
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->itemid = $draft_id;
        $file_record->contextid = context_user::instance($user_one->id)->id;
        $file_record->filename = "test-file.png";
        $file_record->filepath = '/';

        $stored_file = $fs->create_file_from_string($file_record, "This is the file");

        $json_document = [
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('This is a test discussion with a file'),
                image::create_raw_node_from_image($stored_file)
            ],
        ];

        $discussion = discussion_helper::create_discussion(
            $workspace,
            json_encode($json_document),
            $draft_id,
            FORMAT_JSON_EDITOR
        );

        $template = create_new_discussion::create($discussion, $workspace->fullname);
        $rendered_content = $OUTPUT->render($template);

        $this->assertStringContainsString('This is a test discussion with a file', $rendered_content);
        $this->assertStringContainsString('test-file.png', $rendered_content);
        $this->assertStringContainsString($CFG->wwwroot, $rendered_content);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $rendered_content);
        $this->assertStringContainsString($discussion->get_url()->out(), $rendered_content);

        $workspace = $discussion->get_workspace();
        $this->assertStringContainsString($workspace->get_name(), $rendered_content);
    }
}