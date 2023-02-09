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

use ScssPhp\ScssPhp\Formatter\OutputBlock;
use totara_tui\local\scss\transforms\transform;

/**
 * SCSS compiler implementation.
 *
 * Extends \ScssPhp\ScssPhp\Compiler with support for webpack-style loader
 * transforms, otherwise works identically.
 *
 * To use transforms, first register the transform with register_transform(),
 * then prefix the import with the transform name and a !:
 * `@import 'definitions_only!examplestyle`
 *
 * In the case of multiple transforms, they are executed from right to left,
 * with the output of each transform being passed to the next.
 */
class scss_compiler_implementation extends \ScssPhp\ScssPhp\Compiler {
    /**
     * @var array Map of transform names to instances
     */
    private $transforms = [];

    /**
     * Check if a transform with the specified name is registered
     *
     * @param string $name
     * @return bool
     */
    public function has_transform(string $name): bool {
        return isset($this->transforms[$name]);
    }

    /**
     * Register a transform with a particular name
     *
     * @param string $name
     * @param transform $transform
     */
    public function register_transform(string $name, transform $transform) {
        $this->transforms[$name] = $transform;
    }

    /**
     * {@inheritdoc}
     */
    public function findImport($url) {
        // handle transforms in url - resolve just path part and readd transforms after
        $pos = strrpos($url, '!');
        if ($pos !== false) {
            $transforms = substr($url, 0, $pos);
            $path = substr($url, $pos + 1);
            $result = $this->baseFindImport($path);
            if ($result === null) {
                $this->throwError("Cannot find import $path");
            }
            return "$transforms!$result";
        }

        $result = $this->baseFindImport($url);
        if ($result === null) {
            $this->throwError("Cannot find import $url");
        }
        return $result;
    }

    /**
     * Return the file path for an import url if it exists
     *
     * @param string $url
     * @return string|null
     */
    protected function baseFindImport($url) {
        // copied from lib/scssphp/Compiler.php findImport()
        // changes:
        // * updated to use $this->is_file() instead of is_file()

        $urls = [];

        // for "normal" scss imports (ignore vanilla css and external requests)
        if (! preg_match('~\.css$|^https?://~', $url)) {
            // try both normal and the _partial filename
            $urls = [$url, preg_replace('~[^/]+$~', '_\0', $url)];
        }

        $has_extension = preg_match('/[.]s?css$/', $url);

        foreach ($this->importPaths as $dir) {
            if (is_string($dir)) {
                // check urls for normal import paths
                foreach ($urls as $full) {
                    $separator = (
                        ! empty($dir) &&
                        substr($dir, -1) !== '/' &&
                        substr($full, 0, 1) !== '/'
                    ) ? '/' : '';
                    $full = $dir . $separator . $full;

                    if ($this->is_file($file = $full . '.scss') ||
                        ($has_extension && $this->is_file($file = $full))
                    ) {
                        return $file;
                    }
                }
            } else if (is_callable($dir)) {
                // check custom callback for import path
                $file = call_user_func($dir, $url);

                if (! is_null($file)) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function importFile($path, OutputBlock $out) {
        $request = $this->parse_import_request($path);

        $realpath = realpath($request->path);
        if ($realpath === false) {
            $realpath = $path;
        }

        $cache_key = ($request->transforms ? implode('!', $request->transforms) : '') . $realpath;

        if (isset($this->importCache[$cache_key])) {
            $this->handleImportLoop($realpath);

            $tree = $this->importCache[$cache_key];
        } else {
            $code = $this->load_file($request->path);
            $code = $this->filter_loaded_file($request->path, $code);
            $resource = new transform_resource(
                $request->path,
                function ($code, $path) {
                    $parser = $this->parserFactory($path);
                    return $parser->parse($code);
                }
            );
            $resource->set_code($code);
            // run any requested transforms on source
            $this->execute_transforms($request->transforms, $resource);
            $tree = $resource->get_ast();
            $this->importCache[$cache_key] = $tree;
        }

        $pi = pathinfo($request->path);
        array_unshift($this->importPaths, $pi['dirname']);
        $this->compileChildrenNoReturn($tree->children, $out);
        array_shift($this->importPaths);
    }

    /**
     * Parse import request, e.g. 'foo' or 'transform1!transform2!foo'.
     *
     * @param string $request
     * @return object Object with 'path' and 'transforms' fields.
     */
    protected function parse_import_request(string $request): object {
        $pos = strrpos($request, '!');
        $transforms = [];
        if ($pos === false) {
            $path = $request;
        } else {
            $path = substr($request, $pos + 1);
            if ($pos !== 0) {
                $transforms = explode('!', substr($request, 0, $pos));
            }
        }

        return (object)[
            'path' => $path,
            'transforms' => $transforms,
        ];
    }

    /**
     * Check if the provided file exists and is a file.
     *
     * @param string $path
     * @return bool
     */
    protected function is_file(string $path): bool {
        return is_file($path);
    }

    /**
     * Load the provided file from the filesystem.
     *
     * @param string $path
     * @return string|null
     */
    protected function load_file(string $path): ?string {
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }
        return $content;
    }

    /**
     * Apply filters to raw file content before passing to the parser.
     *
     * @param string $path
     * @param string $content
     */
    private function filter_loaded_file($path, $content) {
        // PHP versions below 7.4.0 suffer from poor performance when unicode flag is passed.
        // scssphp detects non-ASCII chars and conditionally passes the unicode flag.
        if (version_compare(PHP_VERSION, '7.4.0') < 0) {
            // Strip non-ASCII characters from comments.
            $content = preg_replace_callback('#/\*.*?\*/|//.*$#ms', function (array $matches): string {
                return preg_replace('/[^\x00-\x7F]/', '?', $matches[0]);
            }, $content);

            // to prevent the error which the db connection was not exist when the user do a fresh installation
            $allow_unperformant = !during_initial_install() && get_config('totara_tui', 'pre_php74_allow_unperformant_css');

            if (!$allow_unperformant && strlen($content) > 100000 && preg_match('/[^\x00-\x7F]/', $content)) {
                // a 100kb file takes around 5 seconds to compile. however, a 200kb file takes 20 seconds to compile as the time as
                // the time taken is proportional to the length squared.
                throw new \coding_exception(
                    "The file at \"$path\" contains non-ASCII characters and your PHP version is " . PHP_VERSION .
                        ". Versions of PHP before 7.4 suffer from a bug that prevents large non-ASCII files from being parsed performantly. " .
                        "Please ensure the file is free of non-ASCII characters or you are using PHP 7.4 or higher."
                );
            }
        }
        return $content;
    }

    /**
     * Execute the provided transforms on the provided resource.
     *
     * @param string[] $names Transform names
     * @param transform_resource $resource Resource to transform.
     */
    protected function execute_transforms(array $names, transform_resource $resource) {
        // transforms execute from right to left (like webpack)
        for ($i = count($names) - 1; $i >= 0; $i--) {
            $this->execute_transform($names[$i], $resource);
        }
    }

    /**
     * Execute the provided transform on the provided resource.
     *
     * @param string $name Transform name
     * @param transform_resource $resource Resource to transform.
     */
    protected function execute_transform(string $name, transform_resource $resource) {
        if (!$this->has_transform($name)) {
            throw new \coding_exception("Unknown transform \"$name\"");
        }
        $transform = $this->transforms[$name];
        $resource->set_current_transform_name($name);
        $transform->execute($resource);
        $resource->set_current_transform_name(null);
    }
}
