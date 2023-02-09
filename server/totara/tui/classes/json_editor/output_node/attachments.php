<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_tui
 */
namespace totara_tui\json_editor\output_node;

use core\json_editor\node\attachments as attachment_collection_node;
use totara_tui\output\component;

/**
 * For rendering {@see attachment_collection_node} into a nice tui component.
 */
final class attachments extends output_node {
    /**
     * attachments constructor.
     * @param attachment_collection_node $node
     */
    public function __construct(attachment_collection_node $node) {
        parent::__construct($node);
    }

    /**
     * @return string
     */
    public function render_tui_component_content(): string {
        $files = [];
        $attachments = $this->node->get_attachments();

        foreach ($attachments as $attachment_node) {
            $output_node = new attachment($attachment_node);
            $files[] = $output_node->get_props_for_collection();
        }

        $tui = new component(
            'tui/components/json_editor/nodes/AttachmentNodeCollection',
            ['files' => $files]
        );

        return $tui->out_html();
    }

    /**
     * @return string
     */
    public static function get_node_type(): string {
        return attachment_collection_node::get_type();
    }
}