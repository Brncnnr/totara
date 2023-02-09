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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use core_phpunit\testcase;

class totara_notification_format_text_email_testcase extends testcase {
    /**
     * @return void
     */
    public function test_format_text_that_has_div(): void {
        $document = document_helper::json_encode_document(
            document_helper::create_document_from_content_nodes([
                paragraph::create_json_node_with_content_nodes([
                    text::create_json_node_from_text(/** @lang text */ '<div>This is the text</div>'),
                ]),
            ])
        );

        $text = format_text_email($document, FORMAT_JSON_EDITOR);
        self::assertEquals(/** @lang text */ '<div>This is the text</div>', trim($text));
    }

    /**
     * @return void
     */
    public function test_format_text_that_has_single_quote(): void {
        $document = document_helper::json_encode_document(
            document_helper::create_document_from_content_nodes([
                paragraph::create_json_node_with_content_nodes([
                    text::create_json_node_from_text('This is the text\'s'),
                ]),
            ])
        );

        $text = format_text_email($document, FORMAT_JSON_EDITOR);
        self::assertEquals('This is the text\'s', trim($text));
    }

    /**
     * We are just making sure that the function format_text_email does not strip the
     * tag '<script/>' as it should be done by the cleaning. Not when rendering, but
     * because we are rendering it to as a plain text hence the '<script/>'
     * tag will not be encoded
     *
     * @return void
     */
    public function test_format_text_that_has_script_tag(): void {
        $document = document_helper::json_encode_document(
            document_helper::create_document_from_content_nodes([
                paragraph::create_json_node_with_content_nodes([
                    text::create_json_node_from_text(/** @lang text */ '<script>alert("doom")</script>'),
                ]),
            ])
        );

        $text = format_text_email($document, FORMAT_JSON_EDITOR);
        self::assertEquals(/** @lang text */ '<script>alert("doom")</script>', trim($text));
    }

    /**
     * @return void
     */
    public function test_format_text_that_has_encoded_script_tag(): void {
        $document = document_helper::json_encode_document(
            document_helper::create_document_from_content_nodes([
                paragraph::create_json_node_with_content_nodes([
                    text::create_json_node_from_text('&lt;script>&gt;alert("doom")&lt;/script&gt;'),
                ]),
            ])
        );

        $text = format_text_email($document, FORMAT_JSON_EDITOR);
        self::assertEquals('&lt;script>&gt;alert("doom")&lt;/script&gt;', trim($text));
    }
}