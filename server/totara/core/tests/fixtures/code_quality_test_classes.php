<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\tests\docblock;

use core\orm\collection;
use DateTime;

defined('MOODLE_INTERNAL') || die();

class class_has_no_docblock {
    // Empty docblock for class
}

/******/
class class_docblock_has_only_stars {
    // Empty docblock for class
}

/*
 * Some class
 */
class class_docblock_starts_with_single_star {
    // Empty docblock for class
}

/**
 * Undocumented class
 */
class class_docblock_undocumented {
    // Undocumented docblock for class
}

/**
 * Documented class
 */
class class_docblock_is_ok {
}

class prop_has_no_docblock {
    public $foo;    // Empty docblock for $foo
}

class prop_has_invalid_var_type {
    /**
     * @var [type]
     */
    public $foo;    // Invalid @var type for $foo
}

class prop_docblock_has_only_stars {
    /******/
    public $foo;    // Empty docblock for $foo
}

class prop_docblock_starts_with_single_star {
    /*
     * @var integer
     */
    public $foo;    // Empty docblock for $foo
}

class prop_docblock_undocumented {
    /**
     * Undocumented variable
     */
    public $foo;    // Undocumented docblock for $boo
}

class prop_docblock_has_no_type {
    /**
     * Keep foo.
     */
    public $foo;    // Missing @var declaration for $foo
}

class prop_docblock_is_ok1 {
    /**
     * Keep foo.
     * @var boolean
     */
    public $foo;
}

class prop_docblock_is_ok2 {
    /**
     * @var string
     */
    public $foo;
}

abstract class prop_docblock_base {
    /**
     * Store foo.
     * @var string
     */
    public $foo;
}

class prop_docblock_inherit1 extends prop_docblock_base {
    /**
     * @inheritDoc
     */
    public $foo;
}

class prop_docblock_inherit2 extends prop_docblock_base {
    /**
     * {@inheritedDoc}
     */
    public $foo;
}

class prop_docblock_inherit3 extends prop_docblock_base {
    public $foo;
}

class prop_docblock_not_inherited extends prop_docblock_base {
    /**
     * @inheritDoc
     */
    public $bar;
}

abstract class method_docblock_base {
    /**
     * Return something.
     * @param integer $bar
     * @return integer
     */
    public function foo(int $bar): int {
        return $bar <=> rand(-100, 100);
    }
}

class method_has_no_docblock {
    public function foo() {
        // No method docblock for method_has_no_docblock::foo()
    }
}

class method_has_no_docblock_but_different_param_name extends method_docblock_base {
    public function foo(int $boo): int {
        return 0;
    }
}

class method_docblock_has_only_stars1 {
    /**
     *
     */
    public function foo(): void {
        // Empty docblock for method_docblock_has_only_stars1::foo()
    }
}

class method_docblock_has_only_stars2 {
    /** ** ** **/
    public function foo(): void {
        // Empty docblock for method_docblock_has_only_stars2::foo()
    }
}

class method_docblock_starts_with_single_star {
    /*
     * Do something.
     * @return boolean
     */
    public function foo(): bool {
        // No method docblock for method_docblock_starts_with_single_star::foo()
        return false;
    }
}

class method_docblock_has_extra_empty_docblock {
    /**
     * Do something.
     * @return void
     */
    /** */
    public function foo(): void {
        // Empty docblock for method_docblock_has_extra_empty_docblock::foo()
    }
}

class method_docblock_undocumented {
    /**
     * Undocumented function
     */
    public function foo(): void {
        // Undocumented docblock for method_docblock_undocumented::foo()
    }
}

class method_docblock_missing_param1 {
    /**
     * Do something.
     * @return integer
     */
    public function foo($bar): int {
        // Method method_docblock_missing_param1::foo() docblock missing @param declaration for $bar
        return 0;
    }
}

class method_docblock_missing_param2 {
    /**
     * @param integer $value use if $flag is true
     * @return integer
     */
    public function foo(int $value, bool $flag): int {
        // Method method_docblock_missing_param2::foo() docblock missing @param declaration for $flag
        return $flag ? $value : -1;
    }
}

class method_docblock_missing_param_type {
    /**
     * @param $bar
     * @return void
     */
    public function foo(int $bar): void {
        // Method method_docblock_invalid_param_type::foo() docblock missing @param type for $bar
    }
}

class method_docblock_invalid_param_type {
    /**
     * @param [type] $bar
     * @return void
     */
    public function foo(int $bar): void {
        // Method method_docblock_invalid_param_type::foo() docblock invalid @param type for $bar
    }
}

class method_docblock_invalid_param_type_hint1 {
    /**
     * @param string $bar
     * @return void
     */
    public function foo(int $bar): void {
        // method_docblock_invalid_param_type_hint1::foo() docblock has incorrect @param type for $bar: 'int' expected
    }
}

class method_docblock_invalid_param_type_hint2 {
    /**
     * @param mixed $bar
     * @return void
     */
    public function foo(array $bar): void {
        // method_docblock_invalid_param_type_hint2::foo() docblock has incorrect @param type for $bar: 'array' expected
    }
}

class method_docblock_missing_return_type {
    /**
     * @param integer $bar
     */
    public function foo(int $bar): method_docblock_missing_return_type {
        // Method method_docblock_missing_return_type::foo() docblock missing @return declaration
        return $this;
    }
}

class method_docblock_incorrect_return_type_hint1 {
    /**
     * @return void
     */
    public function foo(): float {
        // Method method_docblock_incorrect_return_type_hint1::foo() has incorrect @return type: 'float' expected
        return 3.1415926535897932384626433832795;
    }
}

class method_docblock_incorrect_return_type_hint2 {
    /**
     * @return integer
     */
    public function foo(): bool {
        // Method method_docblock_incorrect_return_type_hint2::foo() has incorrect @return type: 'bool' expected
        return 0 == 0;
    }
}

class method_docblock_incorrect_return_type_hint3 {
    /**
     * @return null
     */
    public function foo(): void {
        // Method method_docblock_incorrect_return_type_hint3::foo() has incorrect @return type: 'void' expected
    }
}

class method_docblock_incorrect_return_type_hint4 {
    /**
     * @return bool
     */
    public function foo(): \DateTime {
        // Method method_docblock_incorrect_return_type_hint4::foo() has incorrect @return type
        return new \DateTime();
    }
}

class method_docblock_incorrect_return_type_hint5 {
    /**
     * @return this
     */
    public function foo(): self {
        // Method method_docblock_incorrect_return_type_hint5::foo() docblock has incorrect '@return this': 'self' expected
        return $this;
    }
}

// TODO: detect complex @return type
// class method_docblock_incorrect_return_type_hint6 {
//     /**
//      * @return integer[]|string
//      */
//     public function foo(): array {
//         // Method method_docblock_incorrect_return_type_hint6::foo() docblock has invalid @return declaration
//         return [ 0 ];
//     }
// }

class method_docblock_incorrect_return_type_hint7 {
    /**
     * @return DateTime
     */
    public function foo(): collection {
        // Method method_docblock_incorrect_return_type_hint5::foo() docblock has incorrect @return type
        return new collection();
    }
}

class method_docblock_incorrect_return_type_hint8 {
    /**
     * @return DateTime[]
     */
    public function foo(): collection {
        // Method method_docblock_incorrect_return_type_hint5::foo() docblock has incorrect @return type: 'collection|DateTime[]' expected
        return new collection();
    }
}

class method_docblock_is_ok {
    /**
     * Constructor.
     */
    public function __construct() {
    }

    /**
     * @return boolean
     */
    public function foo(): bool {
        return false;
    }

    /**
     * Do something.
     * @param integer $baz
     * @return void
     */
    public function bar(int $baz): void {
        print_r($baz);
    }

    /**
     * @return integer[]
     */
    public function baz(): array {
        return [ 0, 1, 2 ];
    }

    /**
     * @return bool[]|null
     */
    protected function qux(): ?array {
        return null;
    }

    /**
     * @return integer
     */
    public function quux(): int {
        return time();
    }

    /**
     * @return boolean
     */
    public function quuz(): bool {
        return time() > 0;
    }

    /**
     * @return double
     */
    public function corge(): float {
        return time() * 0.1;
    }

    /**
     * @return true
     */
    public function grault(): bool {
        return true;
    }

    /**
     * @return false
     */
    public function garply(): bool {
        return false;
    }

    /**
     * @return true
     */
    public function waldo(): bool {
        // Note: This is not valid, but also not easy to catch an error.
        return false;
    }

    /**
     * @return \core\collection
     */
    public function fred(): \core\collection {
        return new \core\orm\collection();
    }

    /**
     * @return \core\orm\collection
     */
    public function plugh(): \core\orm\collection {
        return new \core\orm\collection();
    }

    /**
     * @return collection
     */
    public function xyzzy(): collection {
        return new \core\orm\collection();
    }

    /**
     * @return core\orm\collection|DateTime[]
     */
    public function thud(): collection {
        return new \core\orm\collection();
    }
}

class method_docblock_inheritdoc_is_ok extends method_docblock_base {
    /**
     * @inheritDoc
     */
    public function foo(int $bar): int {
        return $bar <=> 0;
    }
}

class param_has_no_docblock1 {
    public function foo() {
        // Note: The test case does not have to catch any docblock errors
    }
}

class param_has_no_docblock2 {
    public function foo($bar) {
        // Method param_has_no_docblock2::foo() parameter $bar is missing a type hint
        print_r($bar);
    }
}

class param_missing_typehint {
    /**
     * @param int $bar
     * @return integer
     */
    public function foo($bar): int {
        // Method param_missing_typehint::foo() parameter $bar is missing a type hint
        return $bar - 1;
    }
}

class return_missing_type_hint {
    /**
     * Return something.
     * @param integer $bar
     * @return boolean
     */
    public function foo(int $bar) {
        // Method return_missing_type_hint::foo() is missing a return type hint
        return $bar > rand(-100, 100);
    }
}

class return_magic_methods_are_ignored {
    public function __construct() {
    }

    public function __destruct() {
    }

    public function __call($name, $arguments) {
        // TODO: must be __call(string $name, array $arguments): mixed
    }

    public static function __callStatic($name, $arguments) {
        // TODO: must be __callStatic(string $name, array $arguments): mixed
    }

    public function __get($name) {
        // TODO: must be __get(string $name): mixed
    }

    public function __set($name, $value) {
        // TODO: must be __set(string $name, mixed $value): void
    }

    public function __isset($name) {
        // TODO: must be __isset(string $name): bool
    }

    public function __unset($name) {
        // TODO: must be __unset(string $name): void
    }

    public function __sleep() {
        // TODO: must be __sleep(): array
    }

    public function __wakeup() {
        // TODO: must be __wakeup(): void
    }

    public function __toString() {
        // TODO: must be __toString(): string
    }

    public function __invoke() {
        // TODO: must be __invoke(...): mixed
    }

    public static function __set_state($properties) {
        // TODO: must be __set_state(array $properties): object
    }

    public function __clone() {
        // TODO: must be __clone(): void
    }

    public function __debugInfo() {
        // TODO: must be __debugInfo(): array
    }
}

// TODO: detect magic methods
// class return_non_magic_methods_are_not_ignored {
//     public function __foo() {
//         // Method return_missing_type_hint::__foo() is missing a return type hint
//         return true;
//     }
// }

class return_type_is_ok {
    public function foo(): bool {
        return true;
    }

    final public function bar(): ?int {
        return null;
    }

    protected function baz(): return_type_is_ok {
        return $this;
    }

    protected static function qux(): string {
        return '?';
    }
}

abstract class accepted_with_no_parameter_hint_base {
    /**
     * Some old function
     * @param stdClass $data
     * @return array
     */
    public function waldo($data) {
        return [ 'a' => 'b' <=> 'c' ];
    }
}

class accepted_with_no_parameter_hint extends accepted_with_no_parameter_hint_base {
    /**
     * @param integer $foo
     * @param mixed $bar
     */
    public function __construct(int $foo, $bar) {
        // Note: Parameter $bar is not an error
        print_r([$foo, $bar]);
    }

    /**
     * @inheritDoc
     */
    public function waldo($data) {
        // Note: When extending an old class, type hints cannot be supplied
        return [ 'a' => 0 ];
    }

    /**
     * Return $null.
     * @param null $null must be null
     * @return null
     */
    public function fred($null = null) {
        // Note: 'null' cannot be a type hint
        if ($null !== null) {
            throw new \Exception('$null must be null');
        }
        return $null;
    }

    /**
     * Do nothing.
     */
    public function plugh(): void {
        // Note: @return void can be omitted in this case
    }

    /**
     * Do something.
     * @param mixed $baz
     * @param int|bool $qux
     * @return mixed
     */
    public function xyzzy($baz, $qux) {
        // Note: Parameters $baz, $qux and the missing return type hint are not an error
        if ($baz === $qux) {
            return '?';
        }
        return 42.0;
    }

    /**
     * Return something.
     * @return null|int|array|string|method_param_mixed_is_ok
     */
    public function thud() {
        // Note: Missing return type hint is not an error
        switch (rand(0, 5)) {
            case 0:
                return null;
            case 1:
                return 42;
            case 2:
                return array(2, 0, 1, 9);
            case 3:
                return 'kia ora';
            case 4:
                return $this;
        }
    }
}
