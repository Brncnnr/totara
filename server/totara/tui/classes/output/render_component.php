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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

namespace totara_tui\output;

/**
 * Helper trait to render Tui components.
 * Can be used by any renderer to allow native rendering of Tui components.
 */
trait render_component {

    /**
     * Render a TUI component.
     *
     * If this method is being called after the header has been sent, you should
     * also call `$PAGE->requires->tui_bundle('...')` before the header is sent
     * with the name of the component to ensure required bundles are sent with
     * the page.
     * If you do not call that method, the bundle may fall back to being loaded
     * at runtime by JS instead, which is a little slower.
     *
     * @param component $component Component to render.
     * @return string HTML output.
     */
    final protected function render_component(component $component): string {
        $component->register($this->page);
        return $component->out_html();
    }
}
