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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

namespace totara_tui\local\mediation;

use totara_core\path;

defined('MOODLE_INTERNAL') || die();

/**
 * Mediation file class
 *
 * Used to make it easier to mediate files without any modification.
 */
final class file {

    /**
     * @var path The path to the file.
     */
    private $path;

    /**
     * File constructor.
     * @param string|path $absolute_path Absolute path to the file.
     */
    public function __construct($absolute_path) {
        $this->path = new path($absolute_path);
    }

    /**
     * Returns the file path.
     * @return path
     */
    public function get_path(): path {
        return new path($this->path);
    }

    /**
     * Returns the file path.
     * @return string
     */
    public function __toString() {
        return $this->path->to_string();
    }

    /**
     * Returns true if the file exists.
     * @return bool
     */
    public function exists() {
        return $this->path->exists();
    }

}
