<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

use core_phpunit\testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Class totara_core_code_quality_testcase_base
 */
abstract class totara_core_code_quality_testcase_base extends testcase {
    /**
     * Set true to disable code inspection.
     * @var boolean
     */
    protected $disabled_code_inspector = false;

    /**
     * Get the fully qualified class names for testing docblocks and type hints.
     *
     * @return string[]
     */
    abstract protected function get_classes_to_test(): array;

    /**
     * Get the array of directories that are excluded from CRLF checks.
     *
     * @return array
     */
    protected function get_whitelist_crlf(): array {
        return [];
    }

    /**
     * Get the name of the component.
     *
     * @return string
     */
    protected static function get_component(): string {
        if (preg_match('/^(.+)_code_quality_testcase$/', static::class, $matches)) {
            return $matches[1];
        }
        throw new coding_exception('get_component() needs to be overridden');
    }

    /**
     * Add inherited classes to the class list array.
     *
     * @param string[]      $tested_classes The array of classes to append the list of found classes
     * @param string|null   $namespace      The relative namespace to totara_msteams
     * @param string|null   $baseclass      The base class name without a namespace
     * @param string        $relpath        The relative path to the component directory
     * @return void
     */
    protected static function add_inherited_classes(array &$tested_classes, ?string $namespace, ?string $baseclass, string $relpath): void {
        $component = static::get_component();
        if ($namespace !== null) {
            $classes = \core_component::get_namespace_classes($namespace, $baseclass, $component);
        } else {
            // get_namespace_classes() does not work with $namespace = ''.
            $classes = array_keys(\core_component::get_component_classes_in_namespace($component, ''));
            if ($baseclass !== null) {
                $classes = array_filter($classes, function ($class) use ($baseclass) {
                    $rc = new ReflectionClass($class);
                    $rcbase = new ReflectionClass($baseclass);
                    if ($rcbase->isInterface()) {
                        return $rc->implementsInterface($baseclass);
                    } else {
                        return $rc->isSubclassOf($baseclass);
                    }
                });
            }
        }
        $tested_classes = array_unique(array_merge($tested_classes, $classes));
        if ($baseclass !== null && !in_array($baseclass, $tested_classes)) {
            $tested_classes[] = $baseclass;
        }
    }

    /**
     * Add classes whose name matches the specified pattern to the class list array.
     *
     * @param string[]  $tested_classes The array of classes to append the list of found classes
     * @param string    $pattern        Regular expression pattern
     * @return void
     */
    protected static function add_matching_classes(array &$tested_classes, string $pattern): void {
        $component = static::get_component();
        $classes = array_keys(\core_component::get_component_classes_in_namespace($component));
        $classes = array_filter($classes, function ($name) use ($pattern) {
            return (bool)preg_match($pattern, $name);
        });
        $tested_classes = array_unique(array_merge($tested_classes, $classes));
    }

    /**
     * Ensure a docblock is present on the class.
     *
     * @param string $classname The name of the class to inspect
     * @return array list of errors found
     */
    public function inspect_class_docblock(string $classname): array {
        $errors = array();
        try {
            $class = new ReflectionClass($classname);
            // check class docblock - must be present
            $docblock = $class->getDocComment();
            if (($error = self::check_common_docblock_problems($docblock, 'class', ['* Undocumented class'])) != null) {
                $errors[] = $error;
            }
            // TODO: should have a package hint
            /*
            if (strpos($docblock, '* @package') === false) {
               $errors[] = 'Class docblock missing @package declaration';
            }
            if (strpos($docblock, '* @subpackage') === false) {
               $errors[] = 'Class docblock missing @subpackage declaration';
            }
            */
        } catch (\ReflectionException $e) {
            $errors[] = $e->getMessage();
        }

        self::insert_summary_if_any_error($errors, $classname, 'class docblock');
        return $errors;
    }

    /**
     * Ensure docblocks are present on all class properties.
     *
     * @param string $classname The name of the class to inspect
     * @return array list of errors found
     */
    public function inspect_property_docblocks(string $classname): array {
        $errors = array();
        try {
            $class = new ReflectionClass($classname);

            foreach ($class->getProperties() as $property) {
                $docblock = $property->getDocComment();

                $inherited_doc = self::is_inherited_doc($docblock);
                $parent_property = self::seek_parent_property($property);
                if (empty($docblock) && $parent_property) {
                    continue;
                }
                if (($error = self::check_common_docblock_problems($docblock, '$'.$property->getName(), ['* Undocumented variable'])) != null) {
                    $errors[] = $error;
                    continue;
                }
                $var_type = self::extract_var_type($docblock);
                if ($var_type === false) {
                    if ($inherited_doc) {
                        if (!$parent_property) {
                            $errors[] = "\${$property->getName()} is not inherited";
                        }
                    } else {
                        $errors[] = "Missing @var declaration for \${$property->getName()}";
                    }
                } else {
                    // Invalid pattern to match @param declarations as below:
                    /**
                     * @var [type]
                     */
                    $pattern = '/@var\s+\[type\]/';
                    if (preg_match($pattern, $docblock)) {
                        $errors[] = "Invalid @var type for \${$property->getName()}";
                    }
                }
            }
        } catch (\ReflectionException $e) {
            $errors[] = $e->getMessage();
        }

        self::insert_summary_if_any_error($errors, $classname, 'property docblocks');
        return $errors;
    }

    /**
     * Ensure docblocks are present on all class methods.
     *
     * You MUST include a description even if it appears to be obvious from the @param and/or @return lines.
     * An exception is made for overridden methods which make no change to the meaning of the parent method and maintain the same
     * arguments/return values. In this case you should omit the comment completely.
     * Another exception is made for methods whose return type is void, in which case @return void may be omitted.
     *
     * @param string $classname The name of the class to inspect
     * @return array list of errors found
     */
    public function inspect_method_docblocks(string $classname): array {
        $to_names = function ($e) {
            return $e->getName();
        };
        $errors = array();

        try {
            $class = new ReflectionClass($classname);
            $class_file = $class->getFileName();

            foreach ($class->getMethods() as $method) {
                if ($method->getFileName() != $class_file) {
                    // Method is defined elsewhere and used here as a trait or implementation.
                    continue;
                }
                $docblock = $method->getDocComment();
                $parameters = $method->getParameters();

                if (empty($docblock)) {
                    // does this method override a method in the parent class with same arguments/return values?
                    try {
                        $prototype = $method->getPrototype();
                        $parameter_names = array_map($to_names, $parameters);
                        $prototype_parameter_names = array_map($to_names, $prototype->getParameters());
                        if ($prototype_parameter_names != $parameter_names) {
                            $errors[] = "No method docblock for {$method->class}::{$method->getName()}(), which extends {$prototype->getDeclaringClass()->getName()}::{$prototype->getName()}() but has different parameters";
                        }
                    } catch (\ReflectionException $e) {
                        // There is a ReflectionException thrown if the method does not have a prototype.
                        $errors[] = "No method docblock for {$method->class}::{$method->getName()}()";
                    }
                } else {
                    if (($error = self::check_common_docblock_problems($docblock, "{$method->class}::{$method->getName()}()", ['* Undocumented function'])) != null) {
                        $errors[] = $error;
                        // If the docblock seems empty, then stop further checking.
                        if (strpos($error, 'Empty') !== false) {
                            continue;
                        }
                    }
                    $inherited_doc = self::is_inherited_doc($docblock);
                    foreach ($parameters as $parameter) {
                        // Pattern to match invalid @param declarations as below:
                        /**
                         * @param [type] $foo docblock template inserted by docblock extension
                         */
                        $pattern = '/@param\s+\[type\]\s+(\.\.\.|)\$' . preg_quote($parameter->getName()) . '\W/';
                        if (preg_match($pattern, $docblock)) {
                            $errors[] = "Method {$method->class}::{$method->getName()}() docblock invalid @param type for \${$parameter->getName()}";
                            continue;
                        }
                        // Pattern to match invalid @param declarations as below:
                        /**
                         * @param $foo docblock template inserted by docblock extension
                         */
                        $pattern = '/\*\s+@param\s+(\.\.\.|)\$' . preg_quote($parameter->getName()) . '\W/';
                        if (preg_match($pattern, $docblock)) {
                            $errors[] = "Method {$method->class}::{$method->getName()}() docblock missing @param type for \${$parameter->getName()}";
                            continue;
                        }
                        // Pattern to match @param declarations as below:
                        /**
                         * @param \mod_facetoface\seminar $foo is a seminar
                         * @param string ...$foo
                         * @param int|null $bar_dog is $foo's magic number
                         * @param \stdClass[] $users array of user records
                         * @param bool $foo $bar should be null if this is true
                         * @param int  $bar we like to line things using spaces too
                         */
                        $param_type = self::extract_param_type($docblock, $parameter);
                        if ($param_type === false) {
                            // If the docblock has @inheritDoc, then @param is optional.
                            if ($inherited_doc == false) {
                                $errors[] = "Method {$method->class}::{$method->getName()}() docblock missing @param declaration for \${$parameter->getName()}";
                            }
                        } else if ($parameter->hasType()) {
                            $error = self::check_mismatched_typehint($param_type, self::get_type_string($parameter->getType()));
                            if (is_string($error)) {
                                $errors[] = "Method {$method->class}::{$method->getName()}() docblock has incorrect @param type for \${$parameter->getName()}: '{$error}' expected";
                            } else if ($error === false) {
                                $errors[] = "Method {$method->class}::{$method->getName()}() docblock has incorrect @param type for \${$parameter->getName()}";
                            }
                        }
                    }
                    // Ensure @return matches the return type
                    $return_type = self::extract_return_type($docblock);
                    if ($return_type === false) {
                        if ($inherited_doc == false && self::is_magic_method($method) == false) {
                            if ($method->hasReturnType() && self::get_type_string($method->getReturnType()) === 'void') {
                                // CITE: the @return tag MAY be omitted here, in which case @return void is implied.
                                // https://docs.phpdoc.org/references/phpdoc/tags/return.html
                                // Because looking for all `return` statements in a method is too much work, here is our rule:
                                // If a method has a 'void' return type hint, '@return void' may be omitted.
                            } else {
                                $errors[] = "Method {$method->class}::{$method->getName()}() docblock missing @return declaration";
                            }
                            // Already handled; No need of guess game.
                            continue;
                        }
                    } else if ($return_type === 'this') {
                        // Special error message for '@return this'
                        $errors[] = "Method {$method->class}::{$method->getName()}() docblock has incorrect '@return this': 'self' expected";
                        // Already handled; No need of guess game.
                        continue;
                    }
                    if ($inherited_doc) {
                        continue;
                    }
                    // Try to catch frequent mistakes of @return types.
                    // For example, the return type hint is signup while the @return type is bool.
                    if ($method->hasReturnType()) {
                        $error = self::check_mismatched_typehint($return_type, self::get_type_string($method->getReturnType()));
                        if (is_string($error)) {
                            $errors[] = "Method {$method->class}::{$method->getName()}() docblock has incorrect @return type: '{$error}' expected";
                        } else if ($error === false) {
                            $errors[] = "Method {$method->class}::{$method->getName()}() docblock has incorrect @return type";
                        }
                    }
                }
            }
        } catch (\ReflectionException $e) {
            $errors[] = $e->getMessage();
        }

        self::insert_summary_if_any_error($errors, $classname, 'method docblocks');
        return $errors;
    }

    /**
     * Ensure class methods parameters are type-hinted.
     *
     * @param string $classname The name of the class to inspect
     * @return array list of errors found
     */
    public function inspect_method_parameter_hints(string $classname): array {
        $errors = array();
        try {
            $class = new ReflectionClass($classname);
            $class_file = $class->getFileName();

            foreach ($class->getMethods() as $method) {
                if ($method->getFileName() != $class_file) {
                    // Method is defined elsewhere and used here as a trait or implementation.
                    continue;
                }

                $docblock = $method->getDocComment();
                $parameters = $method->getParameters();
                foreach ($parameters as $parameter) {
                    if ($parameter->hasType() === false) {
                        // Exception: mixed or null parameters cannot supply a type hint.
                        $type = self::extract_param_type($docblock, $parameter);
                        if ($type !== false && !self::is_typehint_possible($type)) {
                            continue;
                        }
                        // Exception: Cannot supply a type hint if a parent class doesn't have one.
                        if (self::can_supply_parameter_typehint($method, $parameter) === false) {
                            continue;
                        }
                        $errors[] = "Method {$method->class}::{$method->getName()}() parameter \${$parameter->getName()} is missing a type hint";
                    }
                }
            }
        } catch (\ReflectionException $e) {
            $errors[] = $e->getMessage();
        }

        self::insert_summary_if_any_error($errors, $classname, 'method parameter hints');
        return $errors;
    }

    /**
     * Ensure class methods have explicit return types.
     *
     * @param string $classname The name of the class to inspect
     * @return array list of errors found
     */
    public function inspect_method_return_hints(string $classname): array {
        $errors = array();
        try {
            $class = new ReflectionClass($classname);
            $class_file = $class->getFileName();

            foreach ($class->getMethods() as $method) {
                $docblock = $method->getDocComment();
                if ($method->getFileName() != $class_file) {
                    // Method is defined elsewhere and used here as a trait or implementation.
                    continue;
                }

                // __construct, __get, __set, et al are magic methods and rarely have a specific return type.
                if (self::is_magic_method($method)) {
                    continue;
                }

                if ($method->hasReturnType() == false) {
                    $type = self::extract_return_type($docblock);
                    // Exception: mixed or null returns cannot supply a return type hint.
                    if ($type !== false && !self::is_typehint_possible($type)) {
                        continue;
                    }
                    // Exception: Cannot supply a type hint if a parent class doesn't have one.
                    if (self::can_supply_return_typehint($method) === false) {
                        continue;
                    }
                    $errors[] = "Method {$method->class}::{$method->getName()}() is missing a return type hint";
                }
            }
        } catch (\ReflectionException $e) {
            $errors[] = $e->getMessage();
        }

        self::insert_summary_if_any_error($errors, $classname, 'method return hints');
        return $errors;
    }

    /**
     * Summarise the number of errors and insert it to the top of the array of errors.
     *
     * @param array     $errors
     * @param string    $classname
     * @param string    $problem
     */
    private static function insert_summary_if_any_error(array &$errors, string $classname, string $problem): void {
        if (!empty($errors)) {
            $rc = new ReflectionClass($classname);
            array_unshift($errors, sprintf("[[ %d problem(s) with %s %s at %s:%d ]]", count($errors), $problem, $classname, $rc->getFileName(), $rc->getStartLine()));
        }
    }

    /**
     * Check the common problems of docblock.
     *
     * @param string    $docblock The docblock to test.
     * @param string    $what The docblock for what?
     * @param array     $undocs The array of the fragments of undocument docblock
     * @return string|null
     */
    private static function check_common_docblock_problems(string $docblock, string $what, array $undocs) {
        if (empty($docblock)) {
            return "Empty docblock for $what";
        }
        foreach ($undocs as $undoc) {
            if (strpos($docblock, $undoc) !== false) {
                return "Undocumented docblock for $what";
            }
        }
        // Report empty docblocks as below:
        /**
         *
         */
        /** *** **/
        if (preg_match('/^\/\*[\*\s]*\*\/$/', $docblock)) {
            return "Empty docblock for $what";
        }
        return null;
    }

    /**
     * See if the property is declared in any parent classes or interfaces.
     *
     * @param ReflectionProperty $prop
     * @return ReflectionProperty|null property found in the closest parent class/interface, or null if not found.
     */
    private static function seek_parent_property(ReflectionProperty $prop): ?ReflectionProperty {
        $name = $prop->getName();
        $parent = $class = $prop->getDeclaringClass();
        while (($parent = $parent->getParentClass()) !== false) {
            try {
                return $parent->getProperty($name);
            } catch (ReflectionException $ex) {
                // swallow exception and continue searching
            }
        }
        foreach ($class->getInterfaces() as $interface) {
            try {
                return $interface->getProperty($name);
            } catch (ReflectionException $ex) {
                // swallow exception and continue searching
            }
        }
        return null;
    }

    /**
     * Check the param type and the type hint are compatible.
     *
     * @param string $paramtype     The type string in the PHPDoc block.
     * @param string $typehint      The type hint.
     * @return string|bool          - true if no errors
     *                              - false if types are not compatible
     *                              - string if types are not compatible and guess the correct one
     */
    private static function check_mismatched_typehint(string $paramtype, string $typehint) {
        $typedef = self::translate_known_return_type($paramtype, true);
        $typehint = self::translate_known_return_type($typehint, false);
        if ($typedef === false || $typehint === false) {
            // true for known invalid return types.
            $error = true;
        } else if ($typedef === null || $typehint === null) {
            // Give up because mixed types are too complicated.
            $error = false;
        } else {
            // Both are known return types and comparable as string.
            $error = $typedef !== $typehint;
        }
        if ($error) {
            if ($typehint === 'collection') {
                $paramtypearray = $paramtype;
                $collections = ['\\core\\orm\\collection' => '', 'core\\orm\\collection' => '', '\\core\\collection' => '', 'core\\collection', 'collection' => ''];
                $paramtypearray = trim(strtr($paramtype, $collections), '|');
                $isparamarray = preg_match('/[a-z0-9_\\\\]+\[\]/', $paramtypearray);
                if ($paramtype != $paramtypearray) {
                    return $paramtypearray === '' || $isparamarray;
                } else if ($isparamarray) {
                    // Give us some clue if possible.
                    return 'collection|' . $paramtypearray;
                } else {
                    // Nah, got to find it out by yourself.
                    return false;
                }
            }
            if (is_string($typehint) && substr($typehint, 0, 1) !== '$') {
                // Give us some clue if possible.
                return $typehint;
            } else {
                // Nah, got to find it out by yourself.
                return false;
            }
        }
        // No problem found.
        return true;
    }

    /**
     * Extract the var type declaration from a docblock.
     *
     * @param string $docblock                  The PHPDoc block
     * @return string|false                     The var type, or false if not found.
     */
    private static function extract_var_type(string $docblock) {
        $pattern = '/\*\s+@var\s+([^\s]+)/';
        if (preg_match($pattern, $docblock, $matches) === 1) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Extract the param type declaration from a docblock.
     *
     * @param string $docblock                  The PHPDoc block
     * @param ReflectionParameter $parameter    The name of the param.
     * @return string|false                     The type of the param, or false if not found.
     */
    private static function extract_param_type(string $docblock, \ReflectionParameter $parameter) {
        $pattern = '/\*\s+@param\s+([^\s]+)\s+(\.\.\.|)\$' . preg_quote($parameter->getName()) . '\W/';
        if (preg_match($pattern, $docblock, $matches) === 1) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Extract the return type declaration from a docblock.
     *
     * @param string $docblock      The PHPDoc block
     * @return string|false         The return type, or false if not found.
     */
    private static function extract_return_type(string $docblock) {
        $pattern = '/\*\s+@return\s+([^\s]+)/';
        if (preg_match($pattern, $docblock, $matches) === 1) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Check whether the docblock has `inheritedDoc` or not.
     *
     * @param string $docblock      The PHPDoc block
     * @return boolean
     */
    private static function is_inherited_doc(string $docblock): bool {
        $pattern = '/(\*\s+@inheritDoc|\{@inheritedDoc\})/';
        return preg_match($pattern, $docblock) != false;
    }

    /**
     * Check whether the method is a magic method or not.
     *
     * @param ReflectionMethod $method          The current method
     * @return boolean
     */
    private static function is_magic_method(\ReflectionMethod $method): bool {
        // TODO: check more explicitly
        return substr($method->getName(), 0, 2) === '__';
    }

    /**
     * Check whether the overridden method has a parameter type hint.
     *
     * @param ReflectionMethod $method          The current method
     * @param ReflectionParameter $parameter    The parameter of $method
     * @return boolean
     */
    private static function can_supply_parameter_typehint(\ReflectionMethod $method, \ReflectionParameter $parameter): bool {
        try {
            // Ignore magic methods except __construct.
            if (strncmp('__', $method->getName(), 2) == 0 && $method->getName() != '__construct') {
                return false;
            }
            $prototype = $method->getPrototype();
            foreach ($prototype->getParameters() as $protoparam) {
                if ($protoparam->getName() === $parameter->getName()) {
                    return $protoparam->hasType();
                }
            }
        } catch (\ReflectionException $e) {
            // Nothing to do.
        }
        // No prototype means yes.
        return true;
    }

    /**
     * Check whether the overridden method has a return type hint.
     *
     * @param ReflectionMethod $method          The current method
     * @return boolean
     */
    private static function can_supply_return_typehint(\ReflectionMethod $method): bool {
        try {
            $prototype = $method->getPrototype();
            return $prototype->hasReturnType();
        } catch (\ReflectionException $e) {
            // Nothing to do.
        }
        // No prototype means yes.
        return true;
    }

    /**
     * Check whether the type can have a type hint.
     *
     * @param string $type          The return type string
     * @return boolean
     */
    private static function is_typehint_possible(string $type): bool {
        return $type !== 'mixed' && $type !== 'null' && $type !== 'object' && strpos($type, '|') === false;
        // Replace the above line with below only if we need to support >= PHP 7.2
        //return $type !== 'mixed' && $type !== 'null' && strpos($type, '|') === false;
    }

    /**
     * Convert a return type into the corresponding known return type.
     *
     * @param string $type          A return type string
     * @param boolean $in_docblock  True to accept the type definition of docblock
     * @return string|false|null    If a string is returned, it is known to be valid
     *                              If false is returned, the return type is known to be invalid
     *                              If null is returned, the return type is out of hand
     */
    private static function translate_known_return_type(string $type, bool $in_docblock) {
        // Known return type hints
        static $known_return_types = [
            'string' => 'string',
            'object' => null,
            'array' => 'array',
            'iterable' => 'array',
            'self' => null,
            'void' => 'void',
            'float' => 'float',
            'double' => 'float',
            'bool' => 'bool',
            'int' => 'int',
            'core\\collection' => 'collection',
            'core\\orm\\collection' => 'collection',
        ];
        // Known @return types
        static $known_type_definitions = [
            'object' => null,
            'mixed' => null,
            'resource' => null,
            'callable' => null,
            'null' => '$null$',
            'false' => 'bool',
            'true' => 'bool',
            'boolean' => 'bool',
            'integer' => 'int',
        ];
        if (array_key_exists($type, $known_return_types)) {
            return $known_return_types[$type];
        }
        $found = $known_type_definitions[$type] ?? -1;
        if ($found === -1) {
            // Heuristically look for some types.
            if (strpos($type, '[]') !== false) {
                // Treat 'something[]' as array.
                $found = 'array';
            } else if (strpos($type, '|') !== false) {
                // 'mixed' types are too complicated.
                $found = null;
            }
        }
        if ($found !== -1) {
            if ($in_docblock) {
                return $found;
            } else {
                return false;
            }
        }
        return '$user$';
    }

    /**
     * Get the type name as string if possible.
     *
     * @param \ReflectionType|null $type
     * @return string|null
     */
    private static function get_type_string(?\ReflectionType $type): ?string {
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }
        return null;
    }

    /**
     * Recursively scan source files for for carriage return characters
     *
     * @param string $root Root path to start
     * @param string $directory Path to directory to scan
     * @param array  $errors Referenced array of errors
     * @return void
     */
    public function scan_for_crlf(string $root, string $directory, array &$errors): void {
        $relpath = str_replace(DIRECTORY_SEPARATOR, '/', $directory);
        if (strpos($relpath, $root . '/') === 0) {
            $relpath = substr($relpath, strlen($root) + 1);
        }
        $whitelist_crlf = array_merge($this->get_whitelist_crlf(), ['pix', 'tests/fixtures']);
        if (in_array($relpath, $whitelist_crlf)) {
            return;
        }
        $handle = opendir($directory);
        while (false !== ($file = readdir($handle))) {
            // Ignore all dotfiles, including ..
            if (substr($file,0,1) == '.') {
                continue;
            }
            $file_name = $directory.DIRECTORY_SEPARATOR.$file;
            $file_type = filetype($file_name);
            if ($file_type == 'dir') {
                // Directory recursion.
                $this->scan_for_crlf($root, $file_name, $errors);
            } else {
                if (strpos($file_name, $root . '/') === 0) {
                    $relpath = substr($file_name, strlen($root) + 1);
                } else {
                    $relpath = $file_name;
                }
                if (in_array($relpath, $whitelist_crlf)) {
                    continue;
                }
                $contents = file_get_contents($file_name);
                // Look for \r to cover all types of line-ending that aren't \n.
                // If you need \r\n somewhere, please use the CRLF constant.
                if (strpos($contents, "\r") !== false) {
                    $errors[] = "{$file_type} {$file_name} has a carriage return";
                }
            }
        }
        closedir($handle);
    }

    /**
     * Test suite for class docblocks.
     *
     * @return void
     */
    public function test_doc_blocks(): void {
        if ($this->disabled_code_inspector) {
            $this->markTestIncomplete('Code inspection is temporarily disabled.');
        }

        $errors = array();

        foreach ($this->get_classes_to_test() as $class) {
            // Accumulate errors.
            $errors = array_merge($errors, $this->inspect_class_docblock($class));
            $errors = array_merge($errors, $this->inspect_property_docblocks($class));
            $errors = array_merge($errors, $this->inspect_method_docblocks($class));
            $errors = array_merge($errors, $this->inspect_method_parameter_hints($class));
            $errors = array_merge($errors, $this->inspect_method_return_hints($class));
        }

        if (!empty($errors)) {
            // Dump all errors at once.
            $this->fail(implode(PHP_EOL, $errors));
        }
    }

    /**
     * Check seminar source code for CRLF line endings
     *
     * @return void
     */
    public function test_line_endings_in_source_files(): void {
        $component = $component = $this->get_component();
        $source_directory = str_replace(DIRECTORY_SEPARATOR, '/', \core_component::get_component_directory($component));
        $errors = array();
        $this->scan_for_crlf($source_directory, $source_directory, $errors);

        if (count($errors) >= 50) {
            $errors = ["Excessive number of files containing carriage returns. Please check git autocrlf settings and/or editor line endings."];
        }
        if (count($errors)) {
            $this->fail("Found CRLF line endings in seminar source code" . PHP_EOL . implode(PHP_EOL, $errors));
        }
    }
}
