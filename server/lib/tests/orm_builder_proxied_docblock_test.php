<?php
/*
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core_orm
 * @category test
 */

use core\orm\entity\entity;
use core\orm\entity\relations\relation;
use core\orm\entity\repository;
use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_testcase.php');

/**
 * Class core_orm_builder_proxied_docblock_testcase
 * This class is used to test docblocks on repository and relations since these both allow calling builder methods on
 * themselves to keep these in sync easier, the tests here check whether these classes have all proper @method annotations
 * if these go out of sync, the test here fails, if it does, please just replace the docblock with the one that the
 * tests suggests.
 *
 * @package core
 * @group orm
 */
class core_orm_builder_proxied_docblock_testcase extends orm_entity_testcase {

    public function test_repository_has_phpdocs() {
        $reflection = new ReflectionClass(repository::class);
        $actual_doc = $reflection->getDocComment();

        $expected_doc = $this->build_phpdoc(
            repository::class,
            __FUNCTION__,
            'Class repository',
            $this->get_overrides()
        );

        $this->assertEquals($expected_doc, $actual_doc, "Repository class php doc should match the expected: \n" . $expected_doc);
    }

    public function test_relation_has_phpdoc() {
        $reflection = new ReflectionClass(relation::class);
        $actual_doc = $reflection->getDocComment();

        $expected_doc = $this->build_phpdoc(
            relation::class,
            __FUNCTION__,
            implode("\n", [
                'Class relation',
                'This class outlines a scaffolding for defining a relationship between entities',
            ]),
            $this->get_overrides()
        );

        $this->assertEquals($expected_doc, $actual_doc, "Relation class php doc should match the expected: \n" . $expected_doc);
    }

    /**
     * This function allows to define return type (for now?) phpdoc overrides if needed...
     *
     * @return array
     */
    public function get_overrides() {
        return [
            'first' => [
                'return' => '\\' . entity::class . '|null',
            ],
            'first_or_fail' => [
                'return' => '\\' . entity::class,
            ],
            'find' => [
                'return' => '\\' . entity::class . '|null',
            ],
            'find_or_fail' => [
                'return' => '\\' . entity::class,
            ],
            'one' => [
                'return' => '\\' . entity::class . '|null',
            ],
        ];
    }

    /**
     * Check whether a given method is in the blacklisted builder methods list...
     *
     * @param ReflectionMethod $method
     * @return bool
     */
    protected function is_blacklisted_method(ReflectionMethod $method) {
        $no_forwarding = (new ReflectionClass(repository::class))
            ->getProperty('blacklisted_builder_methods');

        $no_forwarding->setAccessible(true);

        return in_array(
            $method->getName(),
            $no_forwarding->getValue(new repository(sample_entity::class))
        );
    }

    /**
     * Build phpDoc fragment for a parameter default value
     *
     * @param ReflectionParameter $parameter
     * @return string|null
     */
    protected function build_parameter_default_value(ReflectionParameter $parameter): ?string {
        if (!$parameter->isOptional()) {
            return null;
        }

        $value = $parameter->getDefaultValue();
        switch (true) {
            case is_string($value):
            case is_bool($value):
            case is_array($value):
                if (is_array($value) && empty($value)) {
                    return '[]';
                }
                return preg_replace("/[\r|\n]/", '', var_export($value, true));

            case is_null($value):
                return 'null'; // Var export returns null in capital case...

            default:
                return $value;
        }
    }

    /**
     * Build phpDoc for a parameter type
     *
     * @param ReflectionParameter $parameter
     * @return string
     */
    protected function build_parameter_type(ReflectionParameter $parameter): string {
        $doc = '';

        if ($parameter->getType()) {
            $doc .= $parameter->getType()->allowsNull() ? '?' : '';

            // NOTE: PHP 8.0 deprecated the getClass().
            if (!$parameter->getType()->isBuiltin()) {
                $class = $parameter->getType()->getName();
                // We need to prepend class with \
                if ($class[0] !== '\\') {
                    $class = "\\${class}";
                }

                $doc .= $class;
            } else {
                $doc .= $parameter->getType()->getName();
            }

            $doc .= ' ';
        }

        return $doc;
    }

    /**
     * Build phpDoc fragment for a parameter
     *
     * @param ReflectionParameter $parameter
     * @param string|null $override
     * @return string
     */
    protected function build_parameter_phpdoc(ReflectionParameter $parameter, string $override = null): string {
        $doc = $this->build_parameter_type($parameter) . '$' . $parameter->getName();

        if (!empty($override)) {
            $doc = $override . '$' . $parameter->getName();
        }

        if (!is_null($default = $this->build_parameter_default_value($parameter))) {
            $doc .= " = $default";
        }

        return $doc;
    }

    /**
     * Build phpDoc fragment for a method return type
     *
     * @param ReflectionMethod $method
     * @param string|null $override
     * @return string
     */
    protected function build_method_return_type(ReflectionMethod $method, string $override = null): string {
        $return = $method->getReturnType();

        // Let's get the return type.
        if (!empty($override)) {
            return $override;
        } else if (preg_match('/\s*\*\s*@return\s+(.*)\s/s', $method->getDocComment(), $matches)) {
            $return = $matches[1];
        } else {
            if ($return) {
                $return = $return->getName() . ($return->allowsNull() ? '|null' : '');
            } else {
                $return = '';
            }
        }
        // We are replacing stdClass to entity...
        return str_replace('stdClass', '\\' . entity::class, $return);
    }

    /**
     * Build phpDoc for a method
     *
     * @param ReflectionMethod $method
     * @param array|null $override
     * @return string
     */
    protected function build_method_phpdoc(ReflectionMethod $method, array $override = null) {
        $params = [];
        // Let's get parameters
        foreach ($method->getParameters() as $parameter) {
            $params[] = $this->build_parameter_phpdoc($parameter, $override[$parameter->getName()] ?? null);
        }

        $all = [
            "@method {$this->build_method_return_type($method, $override['return'] ?? null)}",
            "{$method->getName()}(" . implode(', ', $params) . ')',
        ];

        return implode(' ', array_map('trim', $all));
    }

    /**
     * Filter methods to build phpdoc for for a given class
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function filter_methods_for(ReflectionClass $reflection): array {
        return array_filter(
            (new ReflectionClass(builder::class))->getMethods(ReflectionMethod::IS_PUBLIC),
            function (ReflectionMethod $method) use ($reflection) {
                return !$this->is_blacklisted_method($method) &&
                    !$method->isStatic() &&
                    !$method->isConstructor() &&
                    !$method->isDestructor() &&
                    !$method->isDeprecated() &&
                    $method->getName() !== '__clone' &&
                    !$reflection->hasMethod($method->getName());
            }
        );
    }

    /**
     * Build expected phpDoc describing methods forwarded to the builder for a given class
     *
     * @param string $class
     * @param string $method
     * @param string $header
     * @param array[] $overrides
     * @return string
     */
    protected function build_phpdoc($class, $method, $header, array $overrides = []) {
        $reflection = new ReflectionClass($class);

        $methods = $this->filter_methods_for($reflection);

        if (!empty($header)) {
            $header .= "\n";
        }

        $standard_header = implode("\n", [
            "This is an automatically generated docblock",
            "Please do not edit it directly",
            "See {@see core_orm_builder_proxied_docblock_testcase::{$method}()}",
            "",
        ]);

        $doc = "\n$header\n$standard_header\n";

        $method_docs = [];

        foreach ($methods as $method) {
            $method_docs[] = $this->build_method_phpdoc($method, $overrides[$method->getName()] ?? null);
        }

        $doc .= implode("\n", $method_docs);
        $doc = str_replace("\n", "\n * ", $doc);
        $doc = implode("\n", array_map('rtrim', explode("\n", $doc)));

        return "/**$doc\n */";
    }

}
