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

/**
 * SCSS compiler.
 *
 * Provides a compiler with support for sourcemaps, import resolvers, and
 * variables_only/output_only transforms.
 */
class compiler {
    /**
     * @var scss_compiler_implementation
     */
    private $compiler;

    /**
     * @var bool
     */
    private $sourcemap_enabled = false;

    /**
     * @var array Array of strings and functions to resolve imports
     */
    private $import_paths;

    /**
     * @var bool
     */
    private $minify;

    public function __construct() {
        $this->compiler = new scss_compiler_implementation();

        $this->compiler->register_transform('definitions_only', new transforms\definitions_only());
        $this->compiler->register_transform('output_only', new transforms\output_only());
    }

    /**
     * Get whether to generate a source map.
     *
     * @return bool
     */
    public function get_sourcemap_enabled(): bool {
        return $this->sourcemap_enabled;
    }

    /**
     * Set whether to generate a source map.
     *
     * @param bool $sourcemap_enabled
     */
    public function set_sourcemap_enabled(bool $sourcemap_enabled) {
        $this->sourcemap_enabled = $sourcemap_enabled;
    }

    /**
     * Get array to use to resolve import requests to a file path.
     *
     * @return array Array of strings (path) and functions.
     */
    public function get_import_paths(): array {
        return $this->import_paths;
    }

    /**
     * Set array to use to resolve import requests to a file path.
     *
     * @param array $import_paths Array of strings (path) and functions.
     */
    public function set_import_paths(array $import_paths) {
        $this->import_paths = $import_paths;
    }

    /**
     * Indicate that the CSS should be minified or not.
     *
     * @param bool $minify
     */
    public function set_minify(bool $minify): void {
        $this->minify = $minify;
    }

    /**
     * Compile the provided SCSS
     *
     * @param string $scss
     * @return string
     */
    public function compile(string $scss): string {
        global $CFG;

        // Raise memory/time limits, this might take a while.
        raise_memory_limit(MEMORY_EXTRA);
        \core_php_time_limit::raise(300);

        $this->compiler->setImportPaths($this->import_paths);

        if ($this->sourcemap_enabled) {
            $this->compiler->setSourceMap(scss_compiler_implementation::SOURCE_MAP_INLINE);
            $this->compiler->setSourceMapOptions([
                'sourceMapBasepath' => $CFG->dirroot,
                'outputSourceFiles' => true,
                'excludeSourceFiles' => ['(stdin)'],
                'sourceMapApplyInline' => true,
            ]);
        } else {
            $this->compiler->setSourceMap(scss_compiler_implementation::SOURCE_MAP_NONE);
        }

        $this->compiler->setFormatter(
            $this->minify
                ? 'ScssPhp\ScssPhp\Formatter\Compressed'
                : 'ScssPhp\ScssPhp\Formatter\Nested'
        );

        $compiled = $this->compiler->compile($scss);

        return $compiled;
    }
}
