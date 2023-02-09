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

use ScssPhp\ScssPhp\Block;

/**
 * Represents an SCSS file being transformed.
 */
class transform_resource {
    /**
     * @var callable Function that takes SCSS code and returns an AST.
     */
    private $parse;

    /**
     * @var string Original file path.
     */
    private $path;

    /**
     * @var string SCSS code.
     */
    private $code;

    /**
     * @var \ScssPhp\ScssPhp\Block Abstract syntax tree.
     */
    private $ast;

    /**
     * @var string Name of the current transform. Only used for debugging.
     */
    private $current_transform_name;

    /**
     * @var string Name of the transform that switched to AST-only.
     */
    private $ast_only_transform_name;

    /**
     * Construct a new resource.
     *
     * @param string $path Original path of resource.
     * @param callable $parse Function of signature ($code, $path) which turns code into an AST.
     */
    public function __construct(string $path, callable $parse) {
        $this->path = $path;
        $this->parse = $parse;
    }

    /**
     * Get the path of the file we're transforming.
     *
     * @return string
     */
    public function get_path(): ?string {
        return $this->path;
    }

    /**
     * Get the code we're transforming. Will fail if $this->set_ast() has been called.
     *
     * @throws \coding_exception if resource is AST-only.
     * @return string
     */
    public function get_code(): string {
        if ($this->is_ast_only()) {
            throw new \coding_exception(
                "{$this->current_transform_name}: Code is no longer available for this resource " .
                "because the \"{$this->ast_only_transform_name}\" transform switched it to being AST-only."
            );
        }
        return $this->code;
    }

    /**
     * Update the code after changing it.
     *
     * @param string $code
     */
    public function set_code(string $code) {
        $this->code = $code;
        $this->ast = null;
    }

    /**
     * Get the AST for this resource. Will parse from $code if not in AST-only mode.
     *
     * @return \ScssPhp\ScssPhp\Block
     */
    public function get_ast(): Block {
        if (!isset($this->ast)) {
            if (!isset($this->code)) {
                throw new \coding_exception("AST and source code are unavailable for this resource");
            }
            $this->ast = ($this->parse)($this->code, $this->path);
        }
        return $this->ast;
    }

    /**
     * Update the AST. Will also switch to AST-only mode.
     *
     * @param \ScssPhp\ScssPhp\Block $ast
     */
    public function set_ast(Block $ast) {
        $this->ast = $ast;
        $this->mark_ast_modified();
    }

    /**
     * Flag the AST as having been modified. Switches to AST-only mode, as we cannot generate SCSS code from the AST.
     */
    public function mark_ast_modified() {
        if ($this->code !== null) {
            $this->ast_only_transform_name = $this->current_transform_name;
        }
        $this->code = null;
    }

    /**
     * Get whether the resource is AST-only or not.
     *
     * @return bool
     */
    public function is_ast_only(): bool {
        return isset($this->ast) && !isset($this->code);
    }

    /**
     * Set the current transform name. Only used for debugging.
     *
     * @param string $transform_name
     */
    public function set_current_transform_name(?string $transform_name) {
        $this->current_transform_name = $transform_name;
    }
}
