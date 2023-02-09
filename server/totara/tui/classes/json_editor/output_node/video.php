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

use core\json_editor\node\video as video_node;
use totara_tui\output\component;
use core_useragent;

/**
 * For rendering {@see video_node} into a nicely tui component.
 */
final class video extends output_node {
    /**
     * video constructor.
     * @param video_node $node
     */
    public function __construct(video_node $node) {
        parent::__construct($node);
    }

    /**
     * @return string
     */
    public function render_tui_component_content(): string {
        /** @var video_node $video_node */
        $video_node = $this->node;

        $mimetype = $video_node->get_mime_type();
        if ($mimetype === 'video/quicktime' && (core_useragent::is_chrome() || core_useragent::is_edge())) {
            // Fix for VideoJS/Chrome bug https://github.com/videojs/video.js/issues/423 .
            $mimetype = 'video/mp4';
        }

        $parameters = [
            'mime-type' => $mimetype,
            'url' => $video_node->get_file_url()->out(false),
            'filename' => $video_node->get_filename()
        ];

        $subtitle = $video_node->get_extra_linked_file();
        if (null !== $subtitle) {
            $parameters['subtitle-url'] = $subtitle->get_file_url()->out(false);
        }

        $tui = new component('tui/components/json_editor/nodes/VideoBlock', $parameters);
        return $tui->out_html();
    }

    /**
     * @return string
     */
    public static function get_node_type(): string {
        return video_node::get_type();
    }
}