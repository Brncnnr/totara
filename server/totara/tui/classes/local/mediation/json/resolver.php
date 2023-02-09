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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

namespace totara_tui\local\mediation\json;

use totara_core\path;
use totara_tui\local\locator\bundle;
use totara_tui\local\mediation\file;

/**
 * JSON resolver
 */
final class resolver extends \totara_tui\local\mediation\resolver {

    /**
     * @var string The component that was requested.
     */
    private $component;

    /**
     * @var string The name of the json file that was requested.
     */
    private $file;

    /**
     * JSON constructor.
     * @param string $mediator The class name of the mediator to use to deliver content.
     * @param string $rev The revision number for this resource request.
     * @param string $component The component that was requested.
     * @param string $file
     * @throws \coding_exception If mediator is not available or of the correct type.
     */
    public function __construct(string $mediator, string $rev, string $component, string $file) {
        $this->component = $component;
        $this->file = $file;
        parent::__construct($mediator, $rev);
    }

    /**
     * @inheritDoc
     * @return string
     */
    protected function calculate_etag(): string {
        $content = 'tui-json ' . $this->get_rev() . ' ' . $this->component . ' ' . $this->file;
        if ($this->should_use_dev_mode()) {
            $content .= ' ' . $this->get_sha_for_etag_comparison();
        }
        $etag = sha1($content);
        return $etag;
    }

    /**
     * @inheritDoc
     * @return string
     */
    protected function calculate_cachefile(): path {
        global $CFG;
        return new path($CFG->localcachedir, 'totara_tui-json', $this->get_etag());
    }

    /**
     * Returns the file that is being requested, null if it is not known.
     * @return file|null
     */
    private function get_file(): ?file {
        switch ($this->file) {
            case 'css_variables':
                $file = bundle::get_bundle_css_json_variables_file($this->component);
                break;
            default:
                // Oh, now really!
                throw new \coding_exception('You whitelisted a json file name, and didn\'t handle it!', $this->file);
        }
        if ($file) {
            return new file($file);
        }
        return null;
    }

    /**
     * @inheritDoc
     * @return string
     */
    protected function get_sha_for_etag_comparison(): string {
        $file = $this->get_file();
        if (!$file || !$file->exists()) {
            return 'unknown';
        }
        return sha1_file($file->get_path()->out(true));
    }

    /**
     * @inheritDoc
     */
    protected function get_content_to_cache() {
        $file = $this->get_file();
        if ($file && $file->exists()) {
            return $file;
        }
        return 'null';
    }

    /**
     * Validates the given file is a whitelisted file.
     *
     * @param string $file
     * @return bool
     */
    public static function validate_requested_file(string $file): bool {
        $allowed_files = [
            'css_variables'
        ];
        // Don't come back, ya hear!
        return in_array($file, $allowed_files);
    }
}