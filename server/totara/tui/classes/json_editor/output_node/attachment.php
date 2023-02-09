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

use core\json_editor\node\attachment as attachment_node;
use totara_tui\output\component;

/**
 * Class attachment
 * @package totara_tui\json_editor\output_node
 */
final class attachment extends output_node {
    /**
     * attachment constructor.
     * @param attachment_node $node
     */
    public function __construct(attachment_node $node)  {
        parent::__construct($node);
    }

    /**
     * Ideally that this function will never be reached, as rendering of this single node will be handled by the
     * collection node. However the tui component itslef is actually being used, and we still have to implement this
     * function just to be sure that if there is any changing in the future for displaying single attachment without
     * collection as a wrapper.
     *
     * @return string
     */
    public function render_tui_component_content(): string {
        $download_url = $this->node->get_file_url(true);

        $tui = new component(
            'tui/components/json_editor/nodes/AttachmentNode',
            [
                'file-size' => $this->node->get_file_size(),
                'filename' => $this->node->get_filename(),
                'download-url' => $download_url->out()
            ]
        );

        return $tui->out_html();
    }

    /**
     * @return array
     */
    public function get_props_for_collection(): array {
        return [
            'size' => $this->node->get_file_size(),
            'filename' => $this->node->get_filename(),
            'download_url' => $this->node->get_file_url(true)->out()
        ];
    }

    /**
     * @return string
     */
    static public function get_node_type(): string {
        return attachment_node::get_type();
    }
}