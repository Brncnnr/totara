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

namespace totara_tui\local\requirement;

use totara_core\path;
use totara_tui\local\locator\bundle;
use totara_tui\local\requirement;

/**
 * Represents a JS bundle
 */
final class js extends requirement {

    /**
     * Create a new instance of requirement_js
     *
     * @param string $component Totara component this requirement is part of, e.g. 'mod_example'
     * @param string $name Name of the bundle (e.g. tui_bundle.js)
     */
    public function __construct(string $component) {
        parent::__construct($component, 'tui_bundle.js');
    }

    /**
     * {@inheritdoc}
     */
    public function get_type(): string {
        return self::TYPE_JS;
    }

    /**
     * {@inheritdoc}
     */
    public function get_url(array $options = null): \moodle_url {
        global $CFG;

        $jsrev = bundle::get_js_rev();
        $suffix = bundle::get_js_suffix_for_url();

        if (empty($CFG->slasharguments)) {
            return new \moodle_url('/totara/tui/javascript.php', ['rev' => $jsrev, 'component' => $this->component, 'suffix' => $suffix]);
        } else {
            $returnurl = new \moodle_url('/totara/tui/javascript.php');
            $returnurl->set_slashargument('/' . $jsrev . '/' . $suffix . '/' . $this->component);
            return $returnurl;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get_required_resource(): ?path {
        return bundle::get_bundle_js_file($this->component);
    }
}
