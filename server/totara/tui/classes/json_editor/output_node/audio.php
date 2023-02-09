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

use core\json_editor\node\audio as audio_node;
use totara_tui\output\component;

/**
 * For rendering {@see audio_node} into nice vue component.
 */
final class audio extends output_node {
    /**
     * audio constructor.
     * @param audio_node $node
     */
    public function __construct(audio_node $node) {
        parent::__construct($node);
    }

    /**
     * @return string
     */
    public function render_tui_component_content(): string {
        /** @var audio_node $audio_node */
        $audio_node = $this->node;
        $parameters = [
            'filename' => $audio_node->get_filename(),
            'url' => $audio_node->get_file_url()->out(false),
            'mime-type' => $audio_node->get_mime_type()
        ];

        $transcript = $audio_node->get_extra_linked_file();
        if (null !== $transcript) {
            $parameters['transcript-url'] = $transcript->get_file_url()->out(false);
        }

        $tui = new component('tui/components/json_editor/nodes/AudioBlock', $parameters);
        return $tui->out_html();
    }

    /**
     * @return string
     */
    public static function get_node_type(): string {
        return audio_node::get_type();
    }
}