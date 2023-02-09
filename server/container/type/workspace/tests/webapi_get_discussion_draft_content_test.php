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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\discussion\discussion;
use core\json_editor\node\paragraph;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_get_discussion_draft_content_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    public function test_draft_content_files_are_processed() {
        $discussion = $this->create_discussion_with_attachment();

        // Verify that the draft content has been converted to a 'PLUGINFILE'.
        $this->assertStringContainsString('@@PLUGINFILE@@', $discussion->get_content());
        $this->assertStringNotContainsString('draft', $discussion->get_content());

        // Run the query.
        $args = [
            'id' => $discussion->get_id(),
        ];
        $result = $this->parsed_graphql_operation('container_workspace_get_discussion_draft_content', $args);
        $this->assert_webapi_operation_successful($result);
        $discussion_result = $this->get_webapi_operation_data($result);

        // Verify that the content has been converted to a draft file with matching draft_id.
        $this->assertIsNumeric($discussion_result['draft_id']);
        $this->assertStringNotContainsString('@@PLUGINFILE@@', $discussion_result['draft_content']);
        $this->assertStringContainsString('draft', $discussion_result['draft_content']);
        $this->assertStringContainsString((string)$discussion_result['draft_id'], $discussion_result['draft_content']);
    }

    private function create_discussion_with_attachment(): discussion {
        global $CFG, $USER;

        self::setAdminUser();
        advanced_feature::enable('container_workspace');
        $usercontext = context_user::instance($USER->id);

        // Create a workspace.
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $fs = get_file_storage();

        // Create an inline draft file (an image).
        $draft_id = file_get_unused_draft_itemid();
        $filename_img = 'shouldbeanimage.txt';
        $filerecord = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draft_id,
            'filepath'  => '/',
            'filename'  => $filename_img,
        );
        $fs->create_file_from_string($filerecord, 'image contents (not really)');

        $content = 'Here is an inline image: <img src="' . $CFG->wwwroot .
            "/draftfile.php/{$usercontext->id}/user/draft/{$draft_id}/{$filename_img}" .
            '" alt="inlineimage">.';
        $content = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text($content)
            ],
        ], JSON_UNESCAPED_SLASHES);

        // Create a discussion containing the file.
        return $workspace_generator->create_discussion($workspace->get_id(), $content, $draft_id, FORMAT_JSON_EDITOR);
    }

}