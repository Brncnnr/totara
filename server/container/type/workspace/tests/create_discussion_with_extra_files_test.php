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

use container_workspace\discussion\discussion;
use container_workspace\workspace;
use core\json_editor\node\audio;
use core\json_editor\node\paragraph;
use core\json_editor\node\video;

defined('MOODLE_INTERNAL') || die();

class container_workspace_discussion_with_extra_files_testcase extends advanced_testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");
        require_once("{$CFG->dirroot}/lib/tests/fixtures/json_editor/sample_documents.php");
    }

    /**
     * @return void
     */
    public function test_create_discussion_with_video_and_subtitle(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $context_user = context_user::instance($user_one->id);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create video files and subtitle files.
        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();

        $video_record = new stdClass();
        $video_record->component = 'user';
        $video_record->filearea = 'draft';
        $video_record->itemid = $draft_id;
        $video_record->filename = 'video.mp4';
        $video_record->contextid = $context_user->id;
        $video_record->filepath = '/';

        $video_file = $fs->create_file_from_string($video_record, 'file one');

        $subtitle_record = new stdClass();
        $subtitle_record->component = 'user';
        $subtitle_record->filearea = 'draft';
        $subtitle_record->itemid = $draft_id;
        $subtitle_record->filename = 'subtitle.vtt';
        $subtitle_record->contextid = $context_user->id;
        $subtitle_record->filepath = '/';

        $subtitle_file = $fs->create_file_from_string($subtitle_record, 'file two');
        $discussion_content = core_json_editor_sample_documents::create_json_document_from_nodes([
            paragraph::create_json_node_from_text('some files'),
            video::create_raw_node($video_file, $subtitle_file)
        ]);

        $discussion = discussion::create(
            $discussion_content,
            $workspace->get_id(),
            $draft_id,
            FORMAT_JSON_EDITOR
        );

        self::assertCount(2, $discussion->get_files());

        // The files above will exists within the area of discussion.
        self::assertTrue(
            $fs->file_exists(
                $workspace->get_context()->id,
                workspace::get_type(),
                discussion::AREA,
                $discussion->get_id(),
                '/',
                $subtitle_record->filename
            )
        );

        self::assertTrue(
            $fs->file_exists(
                $workspace->get_context()->id,
                workspace::get_type(),
                discussion::AREA,
                $discussion->get_id(),
                '/',
                $video_record->filename
            )
        );
    }

    /**
     * @return void
     */
    public function test_create_discussion_with_audio_and_transcript(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $context_user = context_user::instance($user_one->id);
        $draft_id = file_get_unused_draft_itemid();

        $audio_record = new stdClass();
        $audio_record->itemid = $draft_id;
        $audio_record->component = 'user';
        $audio_record->filearea = 'draft';
        $audio_record->contextid = $context_user->id;
        $audio_record->filename = 'boom.mp3';
        $audio_record->filepath = '/';

        $fs = get_file_storage();
        $audio_file = $fs->create_file_from_string($audio_record, 'boom');

        $transcript_record = new stdClass();
        $transcript_record->itemid = $draft_id;
        $transcript_record->filearea = 'draft';
        $transcript_record->component = 'user';
        $transcript_record->contextid = $context_user->id;
        $transcript_record->filename = 'tt.txt';
        $transcript_record->filepath = '/';

        $transcript_file = $fs->create_file_from_string($transcript_record, 'transcript');
        $discussion_content = core_json_editor_sample_documents::create_json_document_from_nodes([
            paragraph::create_json_node_from_text('These are files'),
            audio::create_raw_node($audio_file, $transcript_file)
        ]);

        $discussion = discussion::create(
            $discussion_content,
            $workspace->get_id(),
            $draft_id,
            FORMAT_JSON_EDITOR
        );

        self::assertCount(2, $discussion->get_files());
        self::assertTrue(
            $fs->file_exists(
                $workspace->get_context()->id,
                workspace::get_type(),
                discussion::AREA,
                $discussion->get_id(),
                $audio_record->filepath,
                $audio_record->filename
            )
        );

        self::assertTrue(
            $fs->file_exists(
                $workspace->get_context()->id,
                workspace::get_type(),
                discussion::AREA,
                $discussion->get_id(),
                $transcript_record->filepath,
                $transcript_record->filename
            )
        );
    }
}