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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

use core\json_editor\helper\document_helper;

/**
 * Helpers for testing weka functionality within perform.
 */
abstract class mod_perform_weka_testcase extends advanced_testcase {

    /**
     * @param int $draft_id
     * @param context_user $user_context
     * @param bool $encoded
     * @return string|array If encoded, returns string. Otherwise returns an array.
     */
    protected function create_weka_document_with_file(int $draft_id, context_user $user_context, bool $encoded = true) {
        // Create a draft image.
        $draft = new \stdClass();
        $draft->filename = "test_file.png";
        $draft->filepath = '/';
        $draft->component = 'user';
        $draft->filearea = 'draft';
        $draft->itemid = $draft_id;
        $draft->contextid = $user_context->id;

        $fs = get_file_storage();
        $file = $fs->create_file_from_string($draft, 'blah blah');
        $url = \moodle_url::make_draftfile_url(
            $draft_id,
            $draft->filepath,
            $draft->filename
        )->out(false);

        $weka_doc = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'attachments',
                    'content' => [
                        [
                            'type' => 'attachment',
                            'attrs' => [
                                'url' => $url,
                                'filename' => $draft->filename,
                                'size' => $file->get_filesize(),
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($encoded) {
            $weka_doc = document_helper::json_encode_document($weka_doc);
        }

        return $weka_doc;
    }

    /**
     * @param bool $encoded
     * @param string $text
     * @return string|array If encoded, returns string. Otherwise returns an array.
     */
    protected function create_weka_document_with_text(bool $encoded = true, string $text = 'Test') {
        $weka_doc = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $text,
                        ],
                    ],
                ],
            ],
        ];

        if ($encoded) {
            $weka_doc = document_helper::json_encode_document($weka_doc);
        }

        return $weka_doc;
    }

    /**
     * @return \mod_perform\testing\generator
     */
    protected function perform_generator() {
        return \mod_perform\testing\generator::instance();
    }
}
