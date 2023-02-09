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
 * @package core
 */
namespace core\json_editor\node;

use core\json_editor\formatter\formatter;
use core\json_editor\helper\node_helper;
use core\json_editor\schema;
use core\json_editor\node\abstraction\block_node;
use html_writer;

/**
 * A node type for the collection of the attachment.
 */
final class attachments extends node implements block_node {
    /**
     * @var attachment[]
     */
    private $attachments;

    /**
     * @param array $node
     * @return node
     */
    public static function from_node(array $node): node {
        /** @var attachments $innernode */
        $innernode = parent::from_node($node);
        $innernode->attachments = [];

        if (!array_key_exists('content', $node) || !is_array($node['content'])) {
            return $innernode;
        }

        $schema = schema::instance();
        $parent = static::get_type();

        foreach ($node['content'] as $single) {
            $attachment = $schema->get_node($single['type'], $single);
            if (!($attachment instanceof attachment)) {
                debugging("Invalid child node within the parent node '{$parent}'", DEBUG_DEVELOPER);
                continue;
            }

            $innernode->attachments[] = $attachment;
        }

        return $innernode;
    }

    /**
     * @param array $raw_node
     * @return bool
     */
    public static function validate_schema(array $raw_node): bool {
        if (array_key_exists('content', $raw_node)) {
            if (!is_array($raw_node['content'])) {
                return false;
            }

            // Check if all the nodes are actually attachment node.
            $contents = $raw_node['content'];
            $attachment_type = attachment::get_type();

            foreach ($contents as $raw_node_content) {
                if (!isset($raw_node_content['type'])) {
                    // The child node within this collection node is invalid.
                    return false;
                }

                if ($raw_node_content['type'] !== $attachment_type) {
                    return false;
                }

                if (!attachment::validate_schema($raw_node_content)) {
                    // Run it thru actual children.
                    return false;
                }
            }
        }

        return node_helper::check_keys_match_against_data($raw_node, ['type'], ['content']);
    }

    /**
     * @param array $raw_node
     * @return array|null
     */
    public static function clean_raw_node(array $raw_node): ?array {
        $cleaned_raw_node = parent::clean_raw_node($raw_node);
        if ($cleaned_raw_node === null) {
            return null;
        }

        if (!empty($cleaned_raw_node['content']) && is_array($cleaned_raw_node['content'])) {
            // Reset to numeric keys - just in case.
            $cleaned_raw_node['content'] = array_values($cleaned_raw_node['content']);

            $contents = $cleaned_raw_node['content'];
            $attachment_type = attachment::get_type();

            foreach ($contents as $i => $raw_node_content) {
                if ($raw_node_content['type'] !== $attachment_type) {
                    throw new \coding_exception("Invalid node structure for attachments");
                }

                $cleaned_raw_node_content = attachment::clean_raw_node($raw_node_content);
                if ($cleaned_raw_node_content === null) {
                    // Something is wrong - we skip the whole process
                    return null;
                }

                $cleaned_raw_node['content'][$i] = $cleaned_raw_node_content;
            }
        } else {
            // no content, remove
            unset($cleaned_raw_node['content']);
        }

        return $cleaned_raw_node;
    }

    /**
     * @return attachment[]
     */
    public function get_attachments(): array {
        return $this->attachments;
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_html(formatter $formatter): string {
       $content = "";
       foreach ($this->attachments as $attachment) {
           $content .= html_writer::tag('li', $attachment->to_html($formatter));
       }

       return html_writer::tag('ul', $content);
    }

    /**
     * @param formatter $formatter
     * @return string
     */
    public function to_text(formatter $formatter): string {
        $content = '';

        if (!empty($this->attachments)) {
            foreach ($this->attachments as $attachment) {
                $content .= $attachment->to_text($formatter);
            }
        }

        return $content;
    }

    /**
     * @return string
     */
    protected static function do_get_type(): string {
        return 'attachments';
    }

    /**
     * Given the array of stored files, this function will try to create a json node
     * for the collection.
     *
     * @param \stored_file[] $stored_files
     * @return array
     */
    public static function create_raw_node_from_list(array $stored_files): array {
        global $CFG;

        if (empty($stored_files)) {
            throw new \coding_exception("Cannot create a json node when the list of stored files is empty");
        }

        $type = static::get_type();
        $node = [
            'type' => $type,
            'content' => []
        ];

        // This is needed, because the attachment is actually need the \stored_file class.
        require_once("{$CFG->dirroot}/lib/filelib.php");

        foreach ($stored_files as $stored_file) {
            $node['content'][] = attachment::create_raw_node($stored_file);
        }

        return $node;
    }
}