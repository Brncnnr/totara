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

namespace totara_tui\local\scss;

use core\cssvars;

/**
 * Options for {@see scss}
 */
class scss_options {
    /**
     * @var string[] Active theme chain
     */
    private $themes = [];

    /**
     * @var bool Legacy browser mode
     */
    private $legacy = false;

    /**
     * @var bool Generate sourcemaps
     */
    private $sourcemap = false;

    /**
     * @var compiler SCSS compiler
     */
    private $compiler;

    /**
     * @var cssvars CSS vars polyfill
     */
    private $cssvars;

    /**
     * @var bool Skip SCSS compilation.
     */
    private $skip_compile = false;

    /**
     * @var \core\theme\settings Theme settings instance.
     */
    private $theme_settings;

    /**
     * @var bool Minify CSS.
     */
    private $minify = false;

    public function __construct() {
        $this->compiler = new compiler();
        $this->cssvars = new cssvars();
    }

    /**
     * Get the list of themes to use during compilation.
     *
     * @return string[] List of theme names (not including theme_ prefix).
     */
    public function get_themes(): array {
        return $this->themes;
    }

    /**
     * Set the list of themes to use during compilation.
     *
     * @param string[] $themes List of theme names (not including theme_ prefix).
     */
    public function set_themes(array $themes) {
        $this->themes = $themes;
    }

    /**
     * Get whether to compile in legacy mode (IE 11 compatibility).
     *
     * @return bool
     */
    public function get_legacy(): bool {
        return $this->legacy;
    }

    /**
     * Set whether to compile in legacy mode (IE 11 compatibility).
     *
     * @param bool $legacy
     */
    public function set_legacy(bool $legacy) {
        $this->legacy = $legacy;
    }

    /**
     * Set whether to generate a source map.
     *
     * @return bool
     */
    public function get_sourcemap_enabled(): bool {
        return $this->sourcemap;
    }

    /**
     * Set whether to generate a source map.
     *
     * @param bool $sourcemap
     */
    public function set_sourcemap_enabled(bool $sourcemap) {
        $this->sourcemap = $sourcemap;
    }

    /**
     * Get SCSS compiler instance.
     *
     * @return compiler
     */
    public function get_compiler(): compiler {
        return $this->compiler;
    }

    /**
     * Set SCSS compiler instance.
     *
     * @param compiler $compiler
     */
    public function set_compiler(compiler $compiler) {
        $this->compiler = $compiler;
    }

    /**
     * Get cssvars instance.
     *
     * @return \core\cssvars
     */
    public function get_cssvars(): cssvars {
        return $this->cssvars;
    }

    /**
     * Set cssvars instance.
     *
     * @param \core\cssvars $cssvars
     */
    public function set_cssvars(cssvars $cssvars) {
        $this->cssvars = $cssvars;
    }

    /**
     * Sets whether SCSS compilation should be skipped.
     * @param bool $newvalue
     */
    public function set_skip_compile(bool $newvalue) {
        $this->skip_compile = $newvalue;
    }

    /**
     * Returns true if SCSS compilation should be skipped.
     * @return bool
     */
    public function get_skip_compile() {
        return $this->skip_compile;
    }

    /**
     * Set theme settings instance.
     */
    public function set_theme_settings(\core\theme\settings $theme_settings) {
        $this->theme_settings = $theme_settings;
    }

    /**
     * Get theme settings instance.
     */
    public function get_theme_settings(): ?\core\theme\settings {
        return $this->theme_settings;
    }

    /**
     * @param bool $minify
     */
    public function set_minify(bool $minify) {
        $this->minify = $minify;
    }

    /**
     * @return bool
     */
    public function get_minify(): bool {
        return $this->minify;
    }
}
