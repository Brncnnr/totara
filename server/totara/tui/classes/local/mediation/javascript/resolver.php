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

namespace totara_tui\local\mediation\javascript;

use totara_core\path;
use totara_tui\local\theme_config;
use totara_tui\local\mediation\file;
use totara_tui\local\locator\bundle;

/**
 * JavaScript resolver
 */
final class resolver extends \totara_tui\local\mediation\resolver {

    /**
     * @var string The component that was requested.
     */
    private $component;

    /**
     * @var string The suffix that was requested (production|development)
     */
    private $suffix;

    /**
     * @var theme_config Theme config instance, if the component is a theme.
     */
    private $theme_config;

    /**
     * JavaScript constructor.
     * @param string $mediator The class name of the mediator to use to deliver content.
     * @param string $rev The revision number for this resource request.
     * @param string $suffix The suffix that was requested (production|development)
     * @param string $component The component that was requested.
     * @throws \coding_exception If mediator is not available or of the correct type.
     */
    public function __construct(string $mediator, string $rev, string $suffix, string $component) {
        $this->suffix = $suffix;
        $this->component = $component;
        parent::__construct($mediator, $rev);
    }

    /**
     * @inheritDoc
     * @return string
     */
    protected function calculate_etag(): string {
        $content = 'tui ' . $this->get_rev() . ' ' . $this->component . ' ' . $this->suffix;
        if ($this->should_use_dev_mode() && $this->suffix !== 'p' && $this->suffix !== 'pl') {
            $content .= ' ' . $this->get_sha_for_etag_comparison();
        }
        $etag = sha1($content);
        return $etag;
    }

    /**
     * @inheritDoc
     * @return path
     */
    protected function calculate_cachefile(): path {
        global $CFG;
        return new path($CFG->localcachedir, 'totara_tui-javascript', $this->get_etag());
    }

    /**
     * Returns the file that is being requested, null if it is not known.
     * @return file|null
     */
    private function get_file(): ?file {
        if ($this->component === 'vendors') {
            $file = bundle::get_vendors_file();
        } else {
            $file = bundle::get_bundle_js_file($this->component);
        }
        if ($file) {
            return new file($file);
        }
        return null;
    }

    /**
     * Get theme_config instance for the currently requested bundle, if it is a theme bundle.
     * @return theme_config
     */
    private function get_theme_config() {
        if ($this->theme_config === null && substr($this->component, 0, 6) === 'theme_') {
            $this->theme_config = theme_config::load(substr($this->component, 6));
        }
        return $this->theme_config;
    }

    /**
     * @inheritDoc
     * @return string
     */
    protected function get_sha_for_etag_comparison(): string {
        if ($this->get_theme_config()) {
            return $this->get_theme_sha_for_etag_comparison();
        }
        $file = $this->get_file();
        if (!$file || !$file->exists()) {
            return 'unknown';
        }
        return sha1_file($file->get_path()->out(true));
    }

    /**
     * Get hash of content when component is a theme.
     * @return string
     */
    private function get_theme_sha_for_etag_comparison() {
        $chain = $this->get_theme_config()->get_tui_theme_chain();
        $shas = [];
        foreach ($chain as $theme) {
            $file = bundle::get_bundle_js_file('theme_' . $theme);
            if ($file && file_exists($file)) {
                $shas[] = sha1_file($file);
            }
        }
        if (!$shas) {
            return 'unknown';
        }
        return sha1(join('\n', $shas));
    }

    /**
     * @inheritDoc
     */
    protected function get_content_to_cache() {
        if ($this->get_theme_config()) {
            return $this->get_theme_content_to_cache();
        }
        $file = $this->get_file();
        if ($file && $file->exists()) {
            return $file;
        }
        return '/** File not found */';
    }

    /**
     * Get content when component is a theme.
     *
     * @return string
     */
    private function get_theme_content_to_cache() {
        $chain = $this->get_theme_config()->get_tui_theme_chain();
        $content = '';
        foreach ($chain as $themename) {
            $file = bundle::get_bundle_js_file('theme_'.$themename);
            if ($file && file_exists($file)) {
                $content .= "/* theme: $themename */\n".file_get_contents($file)."\n\n";
            }
        }
        if ($content == '') {
            return '/** File not found */';
        }
        return $content;
    }
}