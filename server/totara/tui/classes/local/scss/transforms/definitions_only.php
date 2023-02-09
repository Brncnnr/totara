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
 * @package totara_tui
 */

namespace totara_tui\local\scss\transforms;

use totara_tui\local\scss\transform_resource;
use totara_tui\local\scss\util;

/**
 * Filter to only emit code that results in definitions (inverse of {@see output_only}).
 *
 * In:
 * $link-color: red; a { color: $link-color }
 *
 * Out:
 * $link-color: red;
 */
class definitions_only extends base_output_filter {
    /**
     * {@inheritdoc}
     */
    public function execute(transform_resource $resource): void {
        $tree = $resource->get_ast();
        $context = (object) ['only_output' => false, 'name' => 'definitions_only'];
        util::traverse($tree, $context, \Closure::fromCallable([$this, 'output_filter_visitor']));
        $resource->mark_ast_modified();
    }
}
