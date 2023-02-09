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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package core
 */

namespace core\json_editor\helper;

use coding_exception;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use moodle_url;

/**
 * Text processor.
 */
final class text_helper {
    /**
     * Add a paragraph to the document.
     *
     * @param string|array $document
     * @param string|array $paragraph
     * @param boolean $as_string
     * @return string|false document as JSON string
     */
    public static function append_paragraph($document, $paragraph, bool $as_string = true) {
        // Preprocess.
        if (!is_array($document)) {
            $document = document_helper::parse_document((string)$document);
            if ($document === false) {
                return false;
            }
            return self::append_paragraph($document, $paragraph);
        }

        if (!document_helper::is_valid_document($document)) {
            return false;
        }

        // The real work begins here.
        if (is_array($paragraph)) {
            if (isset($paragraph['type'])) {
                if ($paragraph['type'] !== paragraph::get_type()) {
                    throw new coding_exception('type is not ' . paragraph::get_type());
                }
            } else {
                $paragraph = [
                    'type' => paragraph::get_type(),
                    'content' => $paragraph
                ];
            }
        } else {
            $paragraph = paragraph::create_json_node_from_text((string)$paragraph);
        }

        // Deal with an "empty" document i.e. {"type":"doc","content":[{"type":"paragraph"}]}
        if (document_helper::is_document_empty($document)) {
            $document['content'] = [$paragraph];
        } else {
            $document['content'][] = $paragraph;
        }

        if ($as_string) {
            return document_helper::json_encode_document($document);
        } else {
            return $document;
        }
    }

    /**
     * Add a pre-formatted paragraph containing a hyper link to the document.
     *
     * @param string|array $document
     * @param string $link_label
     * @param string|moodle_url $url
     * @param callable $formatter the callback function with the following signature, to format the text:
     *                           `string formatter(string $encoded_link)`
     *                            the function should not return HTML tags
     * @return string|false document as JSON string
     */
    public static function append_formatted_paragraph_with_link($document, string $link_label, $url, callable $formatter) {
        // Enclose the link node with CDATA, assuming $callback does not return another CDATA.
        $link = '<![CDATA[' .
            json_encode(
                text::create_json_node_from_link(
                    $link_label,
                    $url
                ),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) .
            ']]>';
        $text = $formatter($link);
        // Now that $text should be "before <![CDATA[ link ]]> after"
        if (preg_match('/<!\[CDATA\[(.*)\]\]>/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            // Yes, break apart the text, turn before/after text into plain text nodes and append them.
            $before = substr($text, 0, $matches[0][1]);
            $link = $matches[1][0];
            $after = substr($text, $matches[0][1] + strlen($matches[0][0]));
            $content = [
                text::create_json_node_from_text($before),
                json_decode($link),
                text::create_json_node_from_text($after),
            ];
            return self::append_paragraph($document, $content);
        } else {
            // No? The link node is not found.
            return self::append_paragraph($document, $text);
        }
    }
}
