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

use core\orm\entity\entity;
use core\orm\entity\model;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_many_through;
use core\orm\entity\relations\has_one;
use core\orm\entity\relations\has_one_through;
use core\orm\entity\relations\one_to_one;
use core\orm\entity\relations\relation;
use core_phpunit\testcase;
use mod_approval\testing\gql_parser_error;
use mod_approval\testing\graphqls_parser;
use mod_approval\testing\php_parser;
use mod_approval\testing\php_parser_error;
use totara_core\path;

/**
 * Statically analyse entities, models and so on
 * @group approval_workflow
 * @group approval_workflow_check
 */
class mod_approval_static_testcase extends testcase {
    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @return xmldb_structure
     */
    private function load_xmldb_structure(): xmldb_structure {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $xmldb_file = new xmldb_file($CFG->dirroot . '/mod/approval/db/install.xml');
        $xmldb_file->setDTD($CFG->libdir . '/xmldb/xmldb.dtd');
        $xmldb_file->setSchema($CFG->libdir . '/xmldb/xmldb.xsd');
        if (!$xmldb_file->fileExists()) {
            $this->markTestSkipped('XMLDB file cannot be found');
        }
        $this->assertTrue($xmldb_file->loadXMLStructure(), 'XMLDB file cannot be loaded');
        return $xmldb_file->getStructure();
    }

    /**
     * Check the integrity of entity classes
     */
    public function test_entities() {
        global $CFG;
        $structure = $this->load_xmldb_structure();
        $entities = [];
        foreach (glob($CFG->dirroot . '/mod/approval/classes/entity/*', GLOB_ONLYDIR) as $dir) {
            $name = (new path($dir))->get_name();
            $entities = array_merge($entities, core_component::get_namespace_classes("entity\\{$name}", entity::class, 'mod_approval'));
        }
        $errors = [];
        foreach ($entities as $entity) {
            $relentity = preg_replace('/^.*\\\\/', '', $entity);
            $tablename = $entity::TABLE;
            $table = $structure->getTable($tablename);
            if (!$table) {
                $errors[] = "{$relentity}.php: {$tablename} is not found";
                continue;
            }
            $refentity = new ReflectionClass($entity);
            $docblock = $refentity->getDocComment();
            if (!$docblock) {
                $errors[] = "{$relentity}.php: docblock is not found";
                continue;
            }
            if ($entity::UPDATED_TIMESTAMP != '' && !$entity::SET_UPDATED_WHEN_CREATED) {
                $errors[] = "{$relentity}.php: SET_UPDATED_WHEN_CREATED is not set";
            }
            foreach ($table->getFields() as $field) {
                $name = $field->getName();
                $rename = preg_quote('$' . $name);
                $error = '';
                $suggestion = '';
                if ($name == 'id' || $name == $entity::CREATED_TIMESTAMP || $name == $entity::UPDATED_TIMESTAMP) {
                    if (preg_match("/\\*\\s+@property-read\\s+(int|integer)\\s+{$rename}/", $docblock)) {
                        continue;
                    }
                    if (preg_match("/\\*\\s+@property(|-write)\\s+(int|integer)\\s+{$rename}/", $docblock)) {
                        $error = "{$relentity}.php: property {$name} is not read only";
                    }
                    $suggestion = "@property-read int \${$name}";
                } else if ($field->getNotNull()) {
                    if (preg_match("/\\*\\s+@property\\s+(bool|boolean|int|integer|string|float|double)\\s+{$rename}/", $docblock)) {
                        continue;
                    }
                    $suggestion = "@property [type] \${$name}";
                } else {
                    if (preg_match("/\\*\\s+@property\\s+(bool|boolean|int|integer|string|float|double)\\|null\\s+{$rename}/", $docblock)) {
                        continue;
                    }
                    if (preg_match("/\\*\\s+@property\\s+null\\|(bool|boolean|int|integer|string|float|double)\\s+{$rename}/", $docblock)) {
                        continue;
                    }
                    $suggestion = "@property [type]|null \${$name}";
                }
                if (!$error) {
                    $error = "{$relentity}.php: property declaration for {$name} is not found or invalid";
                }
                $error .= ", possible suggestion '{$suggestion}'";
                $errors[] = $error;
            }
            $inst = new $entity();
            if (preg_match_all("/\\*\\s+@property-read\\s+([a-z0-9_\\|\\[\\]]+)\\s+\\$([a-z0-9_]+)/", $docblock, $matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $type = $matches[1][$i];
                    if (preg_match('/^(|null\\|)(bool|boolean|int|integer|string|float|double)(\\|null|)$/', $type)) {
                        continue;
                    }
                    $name = $matches[2][$i];
                    if (!$refentity->hasMethod($name)) {
                        $errors[] = "{$relentity}.php: method not found for relationship {$name}";
                    }
                    $relation = $inst->{$name}();
                    if (!($relation instanceof relation)) {
                        $errors[] = "{$relentity}.php: method not returning relation for relationship {$name}";
                        continue;
                    }
                    $prop = new ReflectionProperty($relation, 'foreign_key');
                    $prop->setAccessible(true);
                    $foreign_key = $prop->getValue($relation);
                    $prop = new ReflectionProperty($relation, 'related');
                    $prop->setAccessible(true);
                    $related = $prop->getValue($relation);
                    $prop = new ReflectionProperty($relation, 'key');
                    $prop->setAccessible(true);
                    $key = $prop->getValue($relation);
                    if (!class_exists($related)) {
                        $errors[] = "{$relentity}.php: class {$related} not found for relationship {$name}";
                    }
                    $reltablename = $related::TABLE;
                    $reltable = $structure->getTable($reltablename);
                    if (!$reltable && strpos($related, 'mod_approval\\') === 0) {
                        $errors[] = "{$relentity}.php: table {$reltablename} not found for relationship {$name}";
                    }
                    $field = $table->getField($key);
                    if (!$field) {
                        $errors[] = "{$relentity}.php: foreign key {$tablename}.{$key} not found for relationship {$name}";
                    }
                    if ($reltable) {
                        $relfield = $reltable->getField($foreign_key);
                        if (!$relfield) {
                            $errors[] = "{$relentity}.php: key {$reltablename}.{$foreign_key} not found for relationship {$name}";
                        }
                    }
                    if (preg_match('/^collection\\|([a-z0-9_]+)\\[\\]$/', $type, $tymatches)) {
                        if (!($relation instanceof has_many) && !($relation instanceof has_many_through)) {
                            $errors[] = "{$relentity}.php: method not returning has_many or has_many_through for relationship {$name}";
                            continue;
                        }
                        if ($relation instanceof has_many_through) {
                            // FIXME: check intermediate props
                        }
                    } else if (preg_match('/^null\\|([a-z0-9_]+)$/', $type, $tymatches) || preg_match('/^([a-z0-9_]+)\\|null$/', $type, $tymatches)) {
                        if ((!$relation instanceof has_one) && (!$relation instanceof has_one_through) && (!$relation instanceof one_to_one)) {
                            $errors[] = "{$relentity}.php: method not returning has_one, has_one_through or one_to_one for relationship {$name}";
                            continue;
                        }
                        if ($field && $field->getNotNull()) {
                            $error = "{$relentity}.php: property is nullable even though the foreign key is non-nullable for relationship {$name}";
                            $error .= ", possible suggestion: '@property-read {$tymatches[1]} \${$name}'";
                            $errors[] = $error;
                        }
                        if ($relation instanceof has_one_through) {
                            // FIXME: check intermediate props
                        }
                    } else {
                        if ((!$relation instanceof belongs_to) && (!$relation instanceof has_one) && (!$relation instanceof has_one_through) && (!$relation instanceof one_to_one)) {
                            $errors[] = "{$relentity}.php: method not returning belongs_to, has_one, has_one_through or one_to_one for relationship {$name}";
                            continue;
                        }
                        if ($field && !$field->getNotNull()) {
                            $error = "{$relentity}.php: property is non-nullable even though the foreign key is nullable for relationship {$name}";
                            $error .= ", possible suggestion: '@property-read {$type}|null \${$name}'";
                            $errors[] = $error;
                        }
                    }
                }
            }
        }
        if (!empty($errors)) {
            $this->fail(implode(PHP_EOL, $errors));
        }
    }

    /**
     * Check the integrity of model classes
     */
    public function test_models() {
        global $CFG;
        $structure = $this->load_xmldb_structure();
        $models = [];
        foreach (glob($CFG->dirroot . '/mod/approval/classes/model/*', GLOB_ONLYDIR) as $dir) {
            $name = (new path($dir))->get_name();
            $models = array_merge($models, core_component::get_namespace_classes("model\\{$name}", model::class, 'mod_approval'));
        }
        $errors = [];
        foreach ($models as $model) {
            $relmodel = preg_replace('/^.*\\\\/', '', $model);
            $refmodel = new ReflectionClass($model);
            $method = $refmodel->getMethod('get_entity_class');
            $method->setAccessible(true);
            $entity = $method->invoke(null);
            if (!class_exists($entity)) {
                $errors[] = "{$relmodel}.php: entity class {$entity} is not found";
                continue;
            }
            $tablename = $entity::TABLE;
            $table = $structure->getTable($tablename);
            if (!$table) {
                $errors[] = "{$relmodel}.php: {$tablename} is not found";
                continue;
            }
            $docblock = $refmodel->getDocComment();
            if (!$docblock) {
                $errors[] = "{$relmodel}.php: docblock is not found";
                continue;
            }
            $fauxentity = new $entity((object)['id' => 42]);
            $fauxmodel = new $model($fauxentity);
            $prop = $refmodel->getProperty('entity_attribute_whitelist');
            $prop->setAccessible(true);
            $entity_attribute_whitelist = $prop->getValue($fauxmodel);
            $prop = $refmodel->getProperty('model_accessor_whitelist');
            $prop->setAccessible(true);
            $model_accessor_whitelist = $prop->getValue($fauxmodel);
            foreach ($entity_attribute_whitelist as $name) {
                $field = $table->getField($name);
                if (!$field) {
                    $errors[] = "{$relmodel}.php: field {$name} is not found in {$tablename}";
                    continue;
                }
                $rename = preg_quote('$' . $name);
                if ($field->getNotNull()) {
                    if (preg_match("/\\*\\s+@property-read\\s+(bool|boolean|int|integer|string|float|double)\\s+{$rename}/", $docblock)) {
                        continue;
                    }
                    if (preg_match("/\\*\\s+@property\\s+(bool|boolean|int|integer|string|float|double)\\s+{$rename}/", $docblock)) {
                        $error = "{$relmodel}.php: property {$name} is not read only";
                    } else {
                        $error = "{$relmodel}.php: property declaration for {$name} is not found or invalid";
                    }
                    $error .= ", possible suggestion: '@property-read [type] \${$name}'";
                } else {
                    if (preg_match("/\\*\\s+@property-read\\s+(bool|boolean|int|integer|string|float|double)\\|null\\s+{$rename}/", $docblock)) {
                        continue;
                    }
                    if (preg_match("/\\*\\s+@property-read\\s+null\\|(bool|boolean|int|integer|string|float|double)\\s+{$rename}/", $docblock)) {
                        continue;
                    }
                    if (preg_match("/\\*\\s+@property\\s+(|null\\|)(bool|boolean|int|integer|string|float|double)(|\\|null)\\s+{$rename}/", $docblock)) {
                        $error = "{$relmodel}.php: property {$name} is not read only";
                    } else {
                        $error = "{$relmodel}.php: property declaration for {$name} is not found or invalid";
                    }
                    $error .= ", possible suggestion: '@property-read [type]|null \${$name}'";
                }
                $errors[] = $error;
            }
            $model_accessor_whitelist = array_flip($model_accessor_whitelist);
            if (preg_match_all("/\\*\\s+@property(|-read|-write)\\s+([^\\s]+)\\s+\\$([a-z0-9_]+)/", $docblock, $matches)) {
                for ($i = 0; $i < count($matches[2]); $i++) {
                    $name = $matches[3][$i];
                    if ($name === 'id') {
                        continue;
                    }
                    if (in_array($name, $entity_attribute_whitelist)) {
                        if ($refmodel->hasMethod('get_' . $name)) {
                            $errors[] = "{$relmodel}.php getter is redundant for entity attribute {$name}";
                        }
                        continue;
                    }
                    if (!isset($model_accessor_whitelist[$name])) {
                        $errors[] = "{$relmodel}.php: property {$name} is not in model_accessor_whitelist";
                    }
                    unset($model_accessor_whitelist[$name]);
                    if ($matches[1][$i] !== '-read') {
                        $error = "{$relmodel}.php: property {$name} is not read only";
                        $error .= ", possible suggestion: '@property-read [type] \${$name}'";
                        $errors[] = $error;
                    }
                    if (!$refmodel->hasMethod('get_' . $name)) {
                        $errors[] = "{$relmodel}.php: getter not found for relationship {$name}";
                    }
                }
            }
            foreach ($model_accessor_whitelist as $name => $x) {
                $error = "{$relmodel}.php: property declaration for {$name} is not found or invalid";
                $error .= ", possible suggestion: '@property-read [type] \${$name}'";
                $errors[] = $error;
            }
        }
        if (!empty($errors)) {
            $this->fail(implode(PHP_EOL, $errors));
        }
    }

    /**
     * Ensure each file has a correct class / function signature format.
     */
    public function test_white_spaces(): void {
        global $CFG;
        $errors = [];
        $paths = [
            '/mod/approval/classes/',
            '/mod/approval/tests/',
        ];
        while (($dir = array_shift($paths)) !== null) {
            $dh = opendir($CFG->dirroot . $dir);
            if (!$dh) {
                continue;
            }
            try {
                while (($file = readdir($dh)) !== false) {
                    $path = $dir . $file;
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    if (is_dir($path) && $path !== '/mod/approval/tests/fixtures') {
                        $paths[] = $path . '/';
                    } else if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                        $parser = new php_parser($CFG->dirroot . $path);
                        try {
                            $parser->run();
                        } catch (php_parser_error $ex) {
                            $errors[] = $ex->getMessage();
                        }
                    }
                }
            } finally {
                closedir($dh);
            }
        }
        if (!empty($errors)) {
            $this->fail(implode(PHP_EOL, $errors));
        }
    }

    /**
     * Test the parser used by test_white_spaces.
     */
    public function test_php_parser(): void {
        global $CFG;
        $testcases = [
            '01-a' => "extra whitespace after 'class'",
            '01-b' => "extra whitespace after 'class'",
            '01-c' => "extra whitespace after 'class'",
            '01-d' => "extra whitespace after 'interface'",
            '02-a' => "bad : extra whitespace before '{'",
            '02-b' => "bad : extra whitespace before '{'",
            '03-a' => "bad : missing whitespace before '{'",
            '03-b' => "bad : missing whitespace before '{'",
            '04-a' => "bad : extra whitespace after 'function'",
            '04-b' => "bad : extra whitespace after 'function'",
            '05-a' => "bad::baddie() : extra whitespace between ')' and ':'",
            '05-b' => "bad::baddie() : extra whitespace between ')' and ':'",
            '06-a' => "bad::baddie() : extra whitespace between ')' and '{'",
            '06-b' => "bad::baddie() : extra whitespace between ')' and '{'",
            '07-a' => "bad::baddie() : extra whitespace between ')' and ';'",
            '07-b' => "bad::baddie() : extra whitespace between ')' and ';'",
            '08-a' => "bad::baddie() : extra whitespace between ':' and a return type",
            '08-b' => "bad::baddie() : extra whitespace between ':' and a return type",
            '09-a' => "bad::baddie() : missing whitespace between ':' and a return type",
            '09-b' => "bad::baddie() : missing whitespace between ':' and a return type",
            '10-a' => "bad::baddie() : extra whitespace between 'void' and '{'",
            '10-b' => "bad::baddie() : extra whitespace between 'void' and '{'",
            '11-a' => "bad::baddie() : extra whitespace between 'void' and ';'",
            '11-b' => "bad::baddie() : extra whitespace between 'void' and ';'",
            '12-a' => "bad::baddie() : missing whitespace between 'void' and '{'",
            '12-b' => "bad::baddie() : missing whitespace between 'void' and '{'",
            '13-a' => "bad::baddie() : missing whitespace between ')' and '{'",
            '13-b' => "bad::baddie() : missing whitespace between ')' and '{'",
            '14-a' => "bad::baddie() : extra whitespace after 'baddie'",
            '14-b' => "bad::baddie() : extra whitespace after 'baddie'",
        ];
        foreach ($testcases as $case => $expected) {
            $path = $CFG->dirroot . '/mod/approval/tests/fixtures/parser/parser_test_' . $case . '.php';
            $parser = new php_parser($path);
            try {
                $parser->run();
                $this->fail('php_parser_error expected');
            } catch (php_parser_error $ex) {
                $this->assertStringContainsString($expected, $ex->getMessage());
            }
        }
    }

    /**
     * Make sure GraphQL schema is sorted by enums, types, unions, inputs, Query resolvers and Mutation resolvers in an alphabetic order.
     */
    public function test_graphql_schema(): void {
        global $CFG;
        $parser = new graphqls_parser();
        $dh = opendir($CFG->dirroot . '/mod/approval/webapi/');
        if (!$dh) {
            return;
        }
        $errors = [];
        try {
            while (($file = readdir($dh)) !== false) {
                if ($file === '.' || $file === '..' || pathinfo($file, PATHINFO_EXTENSION) !== 'graphqls') {
                    continue;
                }
                try {
                    $parser->parse($CFG->dirroot . '/mod/approval/webapi/' . $file);
                } catch (gql_parser_error $ex) {
                    $code = $ex->get_code_fragment();
                    if ($code) {
                        $errors[] = $ex->getMessage() . "\n" . $code;
                    } else {
                        $errors[] = $ex->getMessage();
                    }
                }
            }
        } finally {
            closedir($dh);
        }
        if (!empty($errors)) {
            $this->fail(implode(PHP_EOL, $errors));
        }
    }

    /**
     * Test the parser used by test_graphql_schema.
     */
    public function test_graphqls_parser(): void {
        $parser = new graphqls_parser();
        $rp = new ReflectionProperty($parser, 'file');
        $rp->setAccessible(true);
        $rp->setValue($parser, '/iFail.graphqls');
        $rm = new ReflectionMethod($parser, 'parse_internal');
        $rm->setAccessible(true);
        $happy_testcase = <<<EOF
enum foo {
  NZ
}
type bar {
  id: Int!
}
type Baz {
  di: Int!
}
union quux = bar | Baz
union qux = quux | bar
input corge {
  on: Boolean!
}
input fred {
  name: String
}
extend type Query {
  garply(ok: Boolean): Int
  waldo(who: String): Int
}
extend type Mutation {
  thud(id: Int): String
  xyzzy(id: Int): Int
}
EOF;
        $sad_testcases = [
            "The enum definition must precede the type definitions" => <<<EOF
type foo {
  id: Int!
}
enum bar {
  NZ
}
EOF,
            "The enum definition must precede the union definitions" => <<<EOF
union foo = bar | baz
enum qux {
  NZ
}
EOF,
            "The enum definition must precede the input definitions" => <<<EOF
input foo {
  id: Int!
}
enum bar {
  NZ
}
EOF,
            "The enum definition must precede the extend type definitions" => <<<EOF
extend type Query {
  foo(id: Int!): bar
}
enum bar {
  NZ
}
EOF,
            "The type definition must precede the union definitions" => <<<EOF
union foo = bar | baz
type qux {
  id: Int!
}
EOF,
            "The type definition must precede the input definitions" => <<<EOF
input foo {
  id: Int!
}
type bar {
  id: Int!
}
EOF,
            "The type definition must precede the extend type definitions" => <<<EOF
extend type Query {
  foo(id: Int!): bar
}
type bar {
  id: Int!
}
EOF,
            "The union definition must precede the input definitions" => <<<EOF
input foo {
  id: Int!
}
union bar = baz | qux
EOF,
            "The union definition must precede the extend type definitions" => <<<EOF
extend type Query {
  foo(id: Int!): bar
}
union baz = bar | qux
EOF,
            "The input definition must precede the extend type definitions" => <<<EOF
extend type Query {
  foo(id: Int!): bar
}
input baz {
  id: Int!
}
EOF,
            "The type 'kia' must precede 'Ora'" => <<<EOF
type Ora {
  id: Int!
}
type kia {
  id: Int!
}
EOF,
            "The extend type Query must precede Mutation" => <<<EOF
extend type Mutation {
  foo(id: Int!): Int
}
extend type Query {
  bar(id: Int!): Int
}
EOF,
            "The resolver 'bar' must precede 'foo'" => <<<EOF
extend type Query {
  foo(id: Int!): Int
  bar(id: Int!): Int
}
EOF,
        ];
        $rm->invoke($parser, $happy_testcase);
        foreach ($sad_testcases as $expected => $contents) {
            try {
                $rm->invoke($parser, $contents);
                $this->fail("gql_parser_error expected for '{$expected}'");
            } catch (gql_parser_error $ex) {
                $this->assertStringContainsString($expected, $ex->getMessage());
            } catch (GraphQL\Error\SyntaxError $ex) {
                echo "Syntax error for '{$expected}'\n";
                throw $ex;
            }
        }
    }

    /**
     * Make sure all behat feature files have the @mod_approval tag.
     */
    public function test_behat_features(): void {
        global $CFG;
        $vuejs_whitelist = ['admin_settings.feature', 'manage_vendors.feature'];
        $errors = $this->check_features($CFG->dirroot . '/mod/approval/tests/behat', $vuejs_whitelist);
        $plugins = core_plugin_manager::instance()->get_plugins_of_type('approvalform');
        foreach ($plugins as $plugin) {
            $dir = $plugin->full_path('tests/behat');
            if (is_dir($dir)) {
                $errors = array_merge($errors, $this->check_features($dir, $vuejs_whitelist));
            }
        }
        if (!empty($errors)) {
            $this->fail(implode(PHP_EOL, $errors));
        }
    }

    /**
     * @param string $dir
     * @param string[] $vuejs_whitelist
     * @return array
     */
    private function check_features(string $dir, array $vuejs_whitelist): array {
        $dh = opendir($dir);
        if (!$dh) {
            return [];
        }
        $errors = [];
        try {
            while (($file = readdir($dh)) !== false) {
                if ($file === '.' || $file === '..' || pathinfo($file, PATHINFO_EXTENSION) !== 'feature') {
                    continue;
                }
                $path = realpath($dir . '/' . $file);
                $fp = @fopen($path, 'r');
                if (!$fp) {
                    continue;
                }
                $tags = array_filter(array_map('trim', explode(' ', trim(fgets($fp)))));
                fclose($fp);
                if (!in_array('@mod_approval', $tags)) {
                    $errors[] = "{$path}: missing @mod_approval";
                }
                if (in_array('@javascript', $tags) != in_array('@vuejs', $tags) && !in_array($file, $vuejs_whitelist)) {
                    $errors[] = "{$path}: @javascript must be paired with @vuejs";
                }
            }
        } finally {
            closedir($dh);
        }
        return $errors;
    }
}
