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

use core\json_editor\node\link_media as link_media_node;
use totara_tui\output\component;

/**
 * For rendering {@see link_media_node} into a nice tui component
 */
final class link_media extends output_node {
    /**
     * link_media constructor.
     * @param link_media_node $node
     */
    public function __construct(link_media_node $node) {
        parent::__construct($node);
    }

    /**
     * @return string
     */
    public function render_tui_component_content(): string {
        $info = $this->node->get_info();
        switch ($info['type']) {
            case 'iframe':
                $tui = new component(
                    'tui/components/embeds/ResponsiveEmbedIframe',
                    [
                        'src' => $info['url'],
                        'resolution' => $this->node->get_resolution()
                    ]
                );

                return \html_writer::tag(
                    'div',
                    $tui->out_html(),
                    ['class' => 'tui-rendered__block tui-rendered__embedContainer']
                );

            case 'image':
                $tui = new component(
                    'tui/components/images/ResponsiveImage',
                    ['src' => $info['url']]
                );

                return \html_writer::tag(
                    'div',
                    $tui->out_html(),
                    ['class' => 'tui-rendered__block tui-rendered__embedContainer']
                );

            case 'audio':
                return \html_writer::tag(
                    'div',
                    \html_writer::tag('audio', null, ['src' => $this->node->get_url(), 'controls' => true]),
                    ['class' => 'tui-rendered__block']
                );

            default:
                $node_url = $this->node->get_url();
                $node_title = $this->node->get_title();
                $node_target = $this->node->get_open_in_new_window() ? '_blank': null;

                if (null !== $node_title && '' !== $node_title) {
                    $title = $node_title;
                } else {
                    $title = $node_url;
                }

                return \html_writer::tag(
                    'div',
                    \html_writer::tag('a', $title, ['href' => $node_url, 'target' => $node_target]),
                    ['class' => 'tui-rendered__block']
                );
        }
    }

    /**
     * @return string
     */
    public static function get_node_type(): string {
        return link_media_node::get_type();
    }
}