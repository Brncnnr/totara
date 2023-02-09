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
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\node\paragraph;
use core\json_editor\node\audio;
use core\json_editor\node\video;
use totara_comment\comment_helper;
use totara_comment\comment;

class totara_comment_create_comment_with_extra_files_testcase extends advanced_testcase {
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
    public function test_create_comment_with_video_and_subtitle(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $context_user = context_user::instance($user_one->id);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment_generator->add_context_for_default_resolver($context_user);

        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();

        $video_record = new stdClass();
        $video_record->component = 'user';
        $video_record->filearea = 'draft';
        $video_record->itemid = $draft_id;
        $video_record->contextid = $context_user->id;
        $video_record->filepath = '/';
        $video_record->filename = 'file.mp4';

        $video_file = $fs->create_file_from_string($video_record, 'video');

        $subtitle_record = new stdClass();
        $subtitle_record->component = 'user';
        $subtitle_record->filearea = 'draft';
        $subtitle_record->itemid = $draft_id;
        $subtitle_record->contextid = $context_user->id;
        $subtitle_record->filepath = '/';
        $subtitle_record->filename = 'file.vtt';

        $subtitle_file = $fs->create_file_from_string($subtitle_record, 'sub');
        $comment_content = core_json_editor_sample_documents::create_json_document_from_nodes([
            paragraph::create_json_node_from_text('PUBG ! wohoo'),
            video::create_raw_node($video_file, $subtitle_file)
        ]);

        $comment = comment_helper::create_comment(
            'totara_comment',
            comment::COMMENT_AREA,
            42,
            $comment_content,
            FORMAT_JSON_EDITOR,
            $draft_id
        );

        self::assertTrue(
            $fs->file_exists(
                $context_user->id,
                'totara_comment',
                comment::COMMENT_AREA,
                $comment->get_id(),
                $video_record->filepath,
                $video_record->filename
            )
        );

        self::assertTrue(
            $fs->file_exists(
                $context_user->id,
                'totara_comment',
                comment::COMMENT_AREA,
                $comment->get_id(),
                $subtitle_record->filepath,
                $subtitle_record->filename
            )
        );

        // Test create reply
        $reply = comment_helper::create_reply(
            $comment->get_id(),
            $comment_content,
            $draft_id
        );

        self::assertTrue(
            $fs->file_exists(
                $context_user->id,
                'totara_comment',
                comment::REPLY_AREA,
                $reply->get_id(),
                $video_record->filepath,
                $video_record->filename
            )
        );

        self::assertTrue(
            $fs->file_exists(
                $context_user->id,
                'totara_comment',
                comment::REPLY_AREA,
                $reply->get_id(),
                $subtitle_record->filepath,
                $subtitle_record->filename
            )
        );
    }

    /**
     * @return void
     */
    public function test_create_comment_with_audio_and_transcript(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $context_user = context_user::instance($user_one->id);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment_generator->add_context_for_default_resolver($context_user);

        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();

        $audio_record = new stdClass();
        $audio_record->component = 'user';
        $audio_record->filearea = 'draft';
        $audio_record->contextid = $context_user->id;
        $audio_record->itemid = $draft_id;
        $audio_record->filepath = '/';
        $audio_record->filename = 'audi.mp3';

        $audio_file = $fs->create_file_from_string($audio_record, 'd');

        $transcript_record = new stdClass();
        $transcript_record->component = 'user';
        $transcript_record->filearea = 'draft';
        $transcript_record->contextid = $context_user->id;
        $transcript_record->itemid = $draft_id;
        $transcript_record->filepath = '/';
        $transcript_record->filename = 'transcript.txt';

        $transcript_file = $fs->create_file_from_string($transcript_record, 'dc');
        $comment_content = core_json_editor_sample_documents::create_json_document_from_nodes([
            paragraph::create_json_node_from_text('wooohoo, these are the files'),
            audio::create_raw_node($audio_file, $transcript_file)
        ]);

        $comment = comment_helper::create_comment(
            'totara_comment',
            comment::COMMENT_AREA,
            42,
            $comment_content,
            FORMAT_JSON_EDITOR,
            $draft_id
        );

        self::assertTrue(
            $fs->file_exists(
                $context_user->id,
                'totara_comment',
                comment::COMMENT_AREA,
                $comment->get_id(),
                $audio_record->filepath,
                $audio_record->filename
            )
        );

        self::assertTrue(
            $fs->file_exists(
                $context_user->id,
                'totara_comment',
                comment::COMMENT_AREA,
                $comment->get_id(),
                $transcript_record->filepath,
                $transcript_record->filename
            )
        );

        // Test create reply
        $reply = comment_helper::create_reply(
            $comment->get_id(),
            $comment_content,
            $draft_id
        );

        self::assertTrue(
            $fs->file_exists(
                $context_user->id,
                'totara_comment',
                comment::REPLY_AREA,
                $reply->get_id(),
                $audio_record->filepath,
                $audio_record->filename
            )
        );

        self::assertTrue(
            $fs->file_exists(
                $context_user->id,
                'totara_comment',
                comment::REPLY_AREA,
                $reply->get_id(),
                $transcript_record->filepath,
                $transcript_record->filename
            )
        );
    }
}