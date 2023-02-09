<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\testing;

/**
 * php_parser class.
 */
class php_parser {
    /** @var string */
    private $path;
    /** @var integer */
    private $line;
    /** @var array */
    private $tokens;
    /** @var integer */
    private $pointer;

    /**
     * @param string $path
     */
    public function __construct(string $path) {
        $code = file_get_contents($path);
        $this->path = $path;
        $this->tokens = token_get_all($code);
        $this->reset();
    }

    /**
     * @return void
     */
    private function reset(): void {
        $this->pointer = 0;
        $this->line = 0;
    }

    /**
     * @param string|integer $token_type
     * @return boolean
     */
    private function skip_until($token_type): bool {
        while (($token = $this->get()) !== null) {
            if ($token[0] === $token_type) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array|null
     */
    private function peek(): ?array {
        if ($this->pointer >= count($this->tokens)) {
            return null;
        }
        $token = $this->tokens[$this->pointer];
        if (is_string($token)) {
            return [$token, $token, $this->line];
        }
        return $token;
    }

    /**
     * @return array|null
     */
    private function get(): ?array {
        $token = $this->peek();
        if ($token !== null) {
            $this->pointer++;
            $this->line = $token[2];
        }
        return $token;
    }

    /**
     * @return void
     */
    private function unget(): void {
        if (--$this->pointer < 0) {
            $this->pointer = 0;
        }
        $token = $this->peek();
        if ($token !== null) {
            $this->line = $token[2];
        }
    }

    /**
     * @param string|integer $token_type
     * @return array|null
     */
    private function get_if($token_type): ?array {
        if (($token = $this->peek()) === null) {
            return null;
        }
        if ($token[0] !== $token_type) {
            return null;
        }
        return $this->get();
    }

    /**
     * @param string $message
     */
    private function error(string $message): void {
        throw new php_parser_error($this->path, $this->line, $message);
    }

    /**
     * @param string $class_name
     * @param string $method_name
     * @param string $message
     */
    private function error_method(string $class_name, string $method_name, string $message): void {
        $this->error("{$class_name}::{$method_name}() : {$message}");
    }

    /**
     * @param string $class_name
     * @param string $message
     */
    private function error_class(string $class_name, string $message): void {
        $this->error("{$class_name} : {$message}");
    }

    /**
     * @return void
     */
    public function run(): void {
        $this->reset();
        while (($token = $this->get()) !== null) {
            if ($token[0] === T_CLASS) {
                $this->class_signature('class');
            } else if ($token[0] === T_INTERFACE) {
                $this->class_signature('interface');
            }
        }
    }

    /**
     * @param string $class
     */
    private function class_signature(string $class): void {
        if (($token = $this->get_if(T_WHITESPACE)) !== null) {
            if ($token[1] !== ' ') {
                // 1. `class  name`
                $this->error("extra whitespace after '{$class}'");
            }
            if (($token = $this->get_if(T_STRING)) !== null) {
                $class_name = $token[1];
                if ($this->skip_until('{')) {
                    $this->unget();
                    $this->unget();
                    if (($token = $this->get_if(T_WHITESPACE)) !== null) {
                        if ($token[1] !== ' ') {
                            // 2. `class name  {`
                            $this->error_class($class_name, "extra whitespace before '{'");
                        }
                    } else {
                        // 3. `class name{`
                        $this->error_class($class_name, "missing whitespace before '{'");
                    }
                    while (($token = $this->get()) !== null) {
                        if ($token[0] === T_FUNCTION) {
                            $this->function_signature($class_name);
                        } else if ($token[0] === '}') {
                            // end of class
                            return;
                        }
                    }
                }
            } else {
                // unknown class signature
                return;
            }
        }
    }

    /**
     * @param string $class_name
     */
    private function function_signature(string $class_name): void {
        if (($token = $this->get_if(T_WHITESPACE)) !== null) {
            if ($token[1] !== ' ') {
                // 4. `function  name()`
                $this->error_class($class_name, "extra whitespace after 'function'");
            }
            if (($token = $this->get_if(T_STRING)) !== null) {
                $method_name = $token[1];
                if ($this->get_if('(') !== null) {
                    if ($this->skip_until(')')) {
                        if (($token = $this->get_if(T_WHITESPACE)) !== null) {
                            $spaces = $token[1];
                            if ($this->get_if(':')) {
                                // 5. `function name() : void`
                                $this->error_method($class_name, $method_name, "extra whitespace between ')' and ':'");
                            }
                            if ($this->get_if('{')) {
                                if ($spaces !== ' ') {
                                    // 6. `function name()  {`
                                    $this->error_method($class_name, $method_name, "extra whitespace between ')' and '{'");
                                }
                            }
                            if ($this->get_if(';')) {
                                // 7. `abstract function name() ;`
                                $this->error_method($class_name, $method_name, "extra whitespace between ')' and ';'");
                            }
                        } else if ($this->get_if(':') !== null) {
                            if (($token = $this->get_if(T_WHITESPACE)) !== null) {
                                if ($token[1] !== ' ') {
                                    // 8. `function name():  void`
                                    $this->error_method($class_name, $method_name, "extra whitespace between ':' and a return type");
                                }
                            } else {
                                // 9. `function name():void`
                                $this->error_method($class_name, $method_name, "missing whitespace between ':' and a return type");
                            }
                            if (($token = $this->get_if(T_STRING)) !== null) {
                                $retype = $token[1];
                                if (($token = $this->get_if(T_WHITESPACE)) !== null) {
                                    if ($this->get_if('{') !== null) {
                                        if ($token[1] !== ' ') {
                                            // 10. `function name(): void  {`
                                            $this->error_method($class_name, $method_name, "extra whitespace between '{$retype}' and '{'");
                                        }
                                    } else if ($this->get_if(';') !== null) {
                                        // 11. `abstract function name(): type ;`
                                        $this->error_method($class_name, $method_name, "extra whitespace between '{$retype}' and ';'");
                                    } else {
                                        // unknown function signature
                                    }
                                } else if ($this->get_if('{')) {
                                    // 12. `function name(): type{`
                                    $this->error_method($class_name, $method_name, "missing whitespace between '{$retype}' and '{'");
                                } else if ($this->get_if(';') !== null) {
                                    // `abstract function name(): type;`
                                    return;
                                }
                            }
                        } else if ($this->get_if('{')) {
                            // 13. `function name(){`
                            $this->error_method($class_name, $method_name, "missing whitespace between ')' and '{'");
                        } else {
                            // unknown function signature
                        }
                        $this->skip_function($class_name, $method_name);
                    }
                } else if ($this->get_if(T_WHITESPACE) !== null) {
                    // 14. `function name ()`
                    $this->error_method($class_name, $method_name, "extra whitespace after '{$method_name}'");
                } else {
                    // unknown function signature
                }
            }
        }
    }

    /**
     * @return void
     */
    private function skip_function(): void {
        $curls = 1;
        while ($curls > 0 && ($token = $this->get()) !== null) {
            if ($token[0] === '{') {
                $curls++;
            } else if ($token[0] === '}') {
                $curls--;
            }
        }
    }

    /**
     * @return void
     */
    private function dump_remaining_tokens(): void {
        echo "------------------\n";
        $p = $this->pointer;
        $l = $this->line;
        while ($x = $this->get()) {
            if (is_int($x[0])) {
                printf("%3d: %s %s\n", $x[2], token_name($x[0]), json_encode($x[1], JSON_UNESCAPED_SLASHES));
            } else {
                echo "     {$x[0]}\n";
            }
        }
        $this->pointer = $p;
        $this->line = $l;
        echo "------------------\n";
    }
}
