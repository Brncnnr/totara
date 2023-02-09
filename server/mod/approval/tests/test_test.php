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

use core\collection;
use core_phpunit\testcase;
use mod_approval\plugininfo\approvalform;
use totara_core\path;

/**
 * Test tests.
 * @group approval_workflow
 * @group approval_workflow_check
 */
class mod_approval_test_testcase extends testcase {
    /** @var collection<string, string> */
    private $paths;

    protected function setUp(): void {
        parent::setUp();
        $this->paths = new collection();
        $this->add_tests(__DIR__);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->paths = null;
    }

    /**
     * Ensure all tests have a valid class docblock
     */
    public function test_classes() {
        $errors = [];
        $covers = [];
        // TODO: check plugins test files
        foreach ($this->paths as $fullpath => $filename) {
            $contents = @file_get_contents($fullpath);
            $classname = preg_replace('/_test\.php$/', '', $filename);
            $classname_re = preg_quote($classname);
            if (!preg_match("/class\s+(mod_approval_{$classname_re}_testcase)\s+extends\s+\\\\?([\w\\\\]*testcase(_base)?)\s+/", $contents, $matches)) {
                $errors[] = "{$filename}: Cannot find a testcase with a valid name. Did you name it \"mod_approval_{$classname}_testcase\"?";
                continue;
            }
            $testcaseclass = $matches[2];
            if ($testcaseclass == 'advanced_testcase' || $testcaseclass == 'basic_testcase') {
                $errors[] = "{$filename}: \"{$testcaseclass}\" is deprecated. Extend the new testcase class instead";
            }
            $classname = $matches[1];
            $class = new ReflectionClass($classname);
            $docblock = $class->getDocComment();
            if (!$docblock) {
                $errors[] = "{$filename}: The class docblock is missing";
                continue;
            }
            if (!preg_match('/\*\s+@group\s+approval_workflow/', $docblock)) {
                $errors[] = "{$filename}: \"@group approval_workflow\" is missing in the class docblock";
            }
            if (strpos($docblock, '@coversDefaultClass') === false) {
                // @coversDefaultClass is optional
                continue;
            }
            if (preg_match('/@coversDefaultClass\s+(.+)/', $docblock, $matches)) {
                $classname = $matches[1];
                if (class_exists($classname)) {
                    if (substr($classname, 0, 1) === '\\') {
                        $classname = substr($classname, 1);
                    }
                    if (isset($covers[$classname])) {
                        $errors[] = "{$filename}: \"{$classname}\" has already been @coversDefaultClass'd by {$covers[$classname]}";
                    } else {
                        $covers[$classname] = $filename;
                    }
                } else {
                    $errors[] = "{$filename}: \"{$classname}\" does not exist. Did you set @coversDefaultClass to a fully qualified class name?";
                }
            } else {
                $errors[] = "{$filename}: @coversDefaultClass must precedes a fully qualified class name";
            }
        }
        if (!empty($errors)) {
            $this->fail(implode(PHP_EOL, $errors));
        }
    }

    /**
     * Tests all test files are suffixed with _test.php
     */
    public function test_files_are_suffixed() {
        $white_list = [
           'testcase.php',
           'totara_notification_base.php',
           'totara_notification_stage_base.php',
           'totara_notification_level_base.php',
        ];
        $errors = $this->get_files_not_suffixed(__DIR__, $white_list);
        $this->assertEmpty($errors, "The following test files are not suffixed with _test.php:" . PHP_EOL . implode(PHP_EOL, $errors));
    }

    /**
     * Get files not suffixed.
     *
     * @param string $directory
     * @param array $excluded_filenames
     *
     * @return array
     */
    private function get_files_not_suffixed(string $directory, array $excluded_filenames): array {
        $errors = [];
        $test_files = new DirectoryIterator($directory);

        foreach ($test_files as $item) {
            if ($item->isDot() || !$item->isFile()) {
                continue;
            }

            $filename = $item->getFilename();
            $is_prefixed = preg_match('/_test\.php$/', $filename);

            if (!in_array($filename, $excluded_filenames, true) && !$is_prefixed) {
                $errors[] = (new path($directory, $filename))->out(true);
            }
        }

        return $errors;
    }

    /**
     * Ensure all GraphQL tests have both resolve_graphql and parsed_graphql_operation
     */
    public function test_webapi_resolvers() {
        $errors = [];
        $paths = $this->paths->filter(function ($filename) {
            return preg_match('/^webapi_(query|mutation|resolver)_/', $filename);
        });
        foreach ($paths as $fullpath => $filename) {
            if (strpos($filename, 'webapi_resolver_') === 0) {
                $errors[] = "{$filename}: Incorrectly formatted file name - remove 'resolver'";
                continue;
            }
            $contents = @file_get_contents($fullpath);
            $query = strpos($filename, 'webapi_query_') === 0;
            if ($query) {
                if (strpos($contents, '$this->resolve_graphql_query') === false) {
                    $errors[] = "{$filename}: Missing test cases with resolve_graphql_query";
                }
            } else {
                if (strpos($contents, '$this->resolve_graphql_mutation') === false) {
                    $errors[] = "{$filename}: Missing test cases with resolve_graphql_mutation";
                }
            }
            if (strpos($contents, '$this->parsed_graphql_operation') === false) {
                $errors[] = "{$filename}: Missing test cases with parsed_graphql_operation";
            }
            if (strpos($contents, '$this->assert_webapi_operation_successful') === false) {
                $errors[] = "{$filename}: Missing test cases that check successful parsed_graphql_operation";
            }
            if (strpos($contents, '$this->assert_webapi_operation_failed') === false) {
                $errors[] = "{$filename}: Missing test cases that check unsuccessful / failed parsed_graphql_operation";
            }
        }
        if (!empty($errors)) {
            $this->fail(implode(PHP_EOL, $errors));
        }
    }

    /**
     * @param string $dir
     */
    private function add_tests(string $dir): void {
        $items = new DirectoryIterator($dir);
        foreach ($items as $item) {
            if ($item->isDot() || !$item->isFile() || !preg_match('/_test\.php$/', $item->getFilename())) {
                continue;
            }
            $path = (new path($dir, $item->getFilename()))->out(true);
            @require_once($path);
            $this->paths->set($item->getFilename(), $path);
        }
    }

    /**
     * Merge plugins test files.
     */
    private function append_plugin_tests(): void {
        $pluginnames = array_keys(core_plugin_manager::instance()->get_installed_plugins('approvalform'));
        foreach ($pluginnames as $pluginname) {
            $plugin = approvalform::from_plugin_name($pluginname);
            $dir = $plugin->full_path('tests');
            if ($dir && is_dir($dir)) {
                $this->add_tests($dir);
            }
        }
    }

    /**
     * Ensure all tests have a valid "covers" docblock.
     */
    public function test_coverage() {
        $errors = [];
        $this->append_plugin_tests();
        foreach ($this->paths as $fullpath => $filename) {
            $contents = @file_get_contents($fullpath);
            if (!preg_match('/class\s+([^\s]+_testcase)\s+/', $contents, $matches)) {
                // No valid class? Should be caught by test_classes.
                continue;
            }
            $test_class = new ReflectionClass($matches[1]);
            $docblock = $test_class->getDocComment();
            if (preg_match('/@coversDefaultClass\s+(.+)/', $docblock, $matches)) {
                if (!class_exists($matches[1])) {
                    // Wrong class. Should be caught by test_classes.
                    continue;
                }
                $default_class = new ReflectionClass($matches[1]);
            } else {
                $default_class = null;
            }
            foreach ($test_class->getMethods(ReflectionMethod::IS_PUBLIC) as $test_method) {
                if ($test_method->getDeclaringClass()->getName() !== $test_class->getName()) {
                    continue;
                }
                if (strpos($test_method->getName(), 'test_') !== 0) {
                    continue;
                }
                $docblock = $test_method->getDocComment();
                if (!preg_match_all('/@covers\s+(.+)\s*/', $docblock, $matches, PREG_SET_ORDER)) {
                    // @covers is optional
                    continue;
                }
                $error_prefix = "{$filename}:{$test_method->getStartLine()}: {$test_class->getName()}::{$test_method->getName()}";
                $nonexist_class = [];
                foreach ($matches as $match) {
                    if (strpos($match[1], '::') === false) {
                        $errors[] = "{$error_prefix}: @covers must be in the format `fqcn::method` or `::method`";
                        continue;
                    }
                    [$class, $method] = explode('::', $match[1], 2);
                    if ($class) {
                        if (!class_exists($class)) {
                            // Do not display the same error multiple times.
                            if (!isset($nonexist_class[$class])) {
                                $nonexist_class[$class] = true;
                                $errors[] = "{$error_prefix}: @covers class {$class} does not exist";
                            }
                            continue;
                        }
                        $cover_class = new ReflectionClass($class);
                    } else if (!$default_class) {
                        $errors[] = "{$error_prefix}: @covers class name is missing";
                        continue;
                    } else {
                        $cover_class = $default_class;
                    }
                    if (!$method) {
                        $errors[] = "{$error_prefix}: @covers method name is missing";
                        continue;
                    }
                    if (!$cover_class->hasMethod($method)) {
                        $errors[] = "{$error_prefix}: @covers {$cover_class->getName()}::{$method} does not exist";
                    }
                }
            }
        }
        if (!empty($errors)) {
            $this->fail(implode(PHP_EOL, $errors));
        }
    }
}
