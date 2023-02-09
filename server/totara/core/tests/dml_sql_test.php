<?php
/*
 * This file is part of Totara LMS
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

use core\dml\sql;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests sql class.
 *
 * NOTE: do not use database_driver_testcase here,
 *       all code in sql class should be database neutral.
 */
class totara_core_dml_sql_testcase extends advanced_testcase {
    public function test_constructor_and_gets() {

        // MySQL style ? parameters.

        $sql = "SELECT * FROM {course} WHERE id = ? AND visible = ?";
        $params = [1, true];
        $rawsql = new sql($sql, $params);
        $this->assertSame($sql, $rawsql->get_sql());
        $this->assertSame($params, $rawsql->get_params());
        $this->assertDebuggingNotCalled();

        $sql = "SELECT * FROM {course} WHERE id = ? AND visible = ?";
        $params = ['a' => 1, 'b' => true];
        $rawsql = new sql($sql, $params);
        $this->assertSame($sql, $rawsql->get_sql());
        $this->assertSame(array_values($params), $rawsql->get_params());
        $this->assertDebuggingNotCalled();

        try {
            $sql = "SELECT * FROM {course} WHERE id = ? AND visible = ?";
            $params = [1];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: Incorrect number of query parameters. Expected 2, got 1.', $e->getMessage());
        }

        try {
            $sql = "SELECT * FROM {course} WHERE id = ? AND visible = ?";
            $params = [];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: Incorrect number of query parameters. Expected 2, got 0.', $e->getMessage());
        }

        try {
            $sql = "SELECT * FROM {course} WHERE id = ? AND visible = ?";
            $params = [1, true, 4];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: Incorrect number of query parameters. Expected 2, got 3.', $e->getMessage());
        }

        // Named parameters.

        $this->assertDebuggingNotCalled();

        $sql = "SELECT * FROM {course} WHERE id = :id AND visible = :visible";
        $params = ['id' => 1, 'visible' => true];
        $rawsql = new sql($sql, $params);
        $this->assertSame($sql, $rawsql->get_sql());
        $this->assertSame($params, $rawsql->get_params());
        $this->assertDebuggingNotCalled();

        $sql = "SELECT * FROM {course} WHERE id = :id AND visible = :visible";
        $params = ['visible' => true, 'id' => 1, 'ignored' => 5];
        $rawsql = new sql($sql, $params);
        $this->assertSame($sql, $rawsql->get_sql());
        $this->assertSame(['visible' => true, 'id' => 1], $rawsql->get_params());
        $this->assertDebuggingNotCalled();

        try {
            $sql = "SELECT * FROM {course} WHERE id = :id AND visible = :visible";
            $params = ['id' => true];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: missing param "visible" in query', $e->getMessage());
        }

        try {
            $sql = "SELECT * FROM {course} WHERE id = :id AND visible = :visible";
            $params = [];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: missing param "id" in query', $e->getMessage());
        }

        // PostreSQL dollar parameters - always converted to named parameters.

        $sql = "SELECT * FROM {course} WHERE id = $1 AND visible = $2";
        $params = [1, true];
        $rawsql = new sql($sql, $params);
        $this->assertMatchesRegularExpression('/^SELECT \* FROM {course} WHERE id = :uq_param_\d+ AND visible = :uq_param_\d+$/', $rawsql->get_sql());
        $this->assertSame($params, array_reverse(array_values($rawsql->get_params())));
        $this->assertDebuggingNotCalled();

        $sql = "SELECT * FROM {course} WHERE id = $1 AND visible = $2";
        $params = ['a' => 1, 'b' => true];
        $rawsql = new sql($sql, $params);
        $this->assertMatchesRegularExpression('/^SELECT \* FROM {course} WHERE id = :uq_param_\d+ AND visible = :uq_param_\d+$/', $rawsql->get_sql());
        $this->assertSame(array_values($params), array_reverse(array_values($rawsql->get_params())));
        $this->assertDebuggingNotCalled();

        try {
            $sql = "SELECT * FROM {course} WHERE id = $1 AND visible = $2";
            $params = [1];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: Incorrect number of query parameters. Expected 2, got 1.', $e->getMessage());
        }

        try {
            $sql = "SELECT * FROM {course} WHERE id = $1 AND visible = $2";
            $params = [];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: Incorrect number of query parameters. Expected 2, got 0.', $e->getMessage());
        }

        try {
            $sql = "SELECT * FROM {course} WHERE id = $1 AND visible = $2";
            $params = [1, true, 4];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: Incorrect number of query parameters. Expected 2, got 3.', $e->getMessage());
        }

        // No placeholders.

        $this->assertDebuggingNotCalled();

        $sql = "SELECT * FROM {course} WHERE visible = 1";
        $params = [];
        $rawsql = new sql($sql, $params);
        $this->assertSame($sql, $rawsql->get_sql());
        $this->assertSame([], $rawsql->get_params());
        $this->assertDebuggingNotCalled();

        $sql = "SELECT * FROM {course} WHERE visible = 1";
        $params = [1, true];
        $rawsql = new sql($sql, $params);
        $this->assertSame($sql, $rawsql->get_sql());
        $this->assertSame([], $rawsql->get_params());
        $this->assertDebuggingNotCalled();

        $rawsql = new sql('');
        $this->assertSame('', $rawsql->get_sql());
        $this->assertSame([], $rawsql->get_params());
        $this->assertDebuggingNotCalled();

        $rawsql = new sql('', [1, 2]);
        $this->assertSame('', $rawsql->get_sql());
        $this->assertSame([], $rawsql->get_params());
        $this->assertDebuggingNotCalled();

        // Mixtures of types.

        try {
            $sql = "SELECT * FROM {course} WHERE id = ? AND visible = :visible";
            $params = [1];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: Mixed types of sql query parameters!!', $e->getMessage());
        }

        try {
            $sql = "SELECT * FROM {course} WHERE id = ? AND visible = $1";
            $params = [1];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: Mixed types of sql query parameters!!', $e->getMessage());
        }

        try {
            $sql = "SELECT * FROM {course} WHERE id = :id AND visible = $1";
            $params = [1];
            new sql($sql, $params);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_exception::class, $e);
            $this->assertSame('ERROR: Mixed types of sql query parameters!!', $e->getMessage());
        }

        // Illegal modification attempts.
        $sql = "SELECT * FROM {course} WHERE id = :id AND visible = :visible";
        $params = ['visible' => true, 'id' => 1, 'ignored' => 5];
        $rawsql = new sql($sql, $params);
        try {
            $rawsql->sql = 'SELECT';
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: sql instance cannot be modified', $e->getMessage());
        }
        try {
            $rawsql->params = [];
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: sql instance cannot be modified', $e->getMessage());
        }
    }

    public function test_to_string() {
        $rawsql = new sql("SELECT * FROM {course} WHERE id = ? AND visible = ?", [1, true]);
        $this->assertSame($rawsql->get_sql(), (string)$rawsql);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 1]);
        $this->assertSame($rawsql->get_sql(), (string)$rawsql);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = $1 AND visible = $2", [1, true]);
        $this->assertSame($rawsql->get_sql(), (string)$rawsql);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = ? AND visible = ?", [1, true]);
        $this->assertSame($rawsql->get_sql(), (string)$rawsql);
    }

    public function test_is_empty() {
        $rawsql = new sql("SELECT * FROM {course} WHERE id = ? AND visible = ?", [1, true]);
        $this->assertFalse($rawsql->is_empty());

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 1]);
        $this->assertFalse($rawsql->is_empty());

        $rawsql = new sql("0");
        $this->assertFalse($rawsql->is_empty());

        $rawsql = new sql("");
        $this->assertTrue($rawsql->is_empty());

        $rawsql = new sql("", [1]);
        $this->assertTrue($rawsql->is_empty());

        $rawsql = new sql(" \n\t");
        $this->assertTrue($rawsql->is_empty());
    }

    public function test_to_named_params() {
        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 1]);
        $namedrawsql = $rawsql->to_named_params();
        $this->assertSame($rawsql, $namedrawsql);

        $rawsql = new sql("SELECT * FROM {course}");
        $namedrawsql = $rawsql->to_named_params();
        $this->assertSame($rawsql, $namedrawsql);

        $rawsql = new sql("");
        $namedrawsql = $rawsql->to_named_params();
        $this->assertSame($rawsql, $namedrawsql);

        $rawsql = new sql("SELECT * FROM {course}", [1]);
        $namedrawsql = $rawsql->to_named_params();
        $this->assertSame($rawsql, $namedrawsql);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = ? AND visible = ?", [1, true]);
        $namedrawsql = $rawsql->to_named_params();
        $sql = $namedrawsql->get_sql();
        $params = $namedrawsql->get_params();
        $this->assertMatchesRegularExpression('/^SELECT \* FROM {course} WHERE id = :uq_param_\d+ AND visible = :uq_param_\d+$/', $sql);
        $this->assertSame([1, true], array_values($params));
        preg_match_all('/:(uq_param_\d+)/', $sql, $matches);
        $this->assertSame(1, $params[$matches[1][0]]);
        $this->assertSame(true, $params[$matches[1][1]]);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = ? AND visible = ?", [1, true]);
        $namedrawsql = $rawsql->to_named_params('grrr');
        $sql = $namedrawsql->get_sql();
        $params = $namedrawsql->get_params();
        $this->assertMatchesRegularExpression('/^SELECT \* FROM {course} WHERE id = :uq_grrr_\d+ AND visible = :uq_grrr_\d+$/', $sql);
        $this->assertSame([1, true], array_values($params));
        preg_match_all('/:(uq_grrr_\d+)/', $sql, $matches);
        $this->assertSame(1, $params[$matches[1][0]]);
        $this->assertSame(true, $params[$matches[1][1]]);
    }

    public function test_append_string() {
        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $result = $rawsql->append("ORDER BY id ASC");
        $this->assertSame("SELECT * FROM {course} WHERE id = :id ORDER BY id ASC", $result->get_sql());
        $this->assertSame($rawsql->get_params(), $result->get_params());
        $this->assertNotSame($rawsql, $result);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $result = $rawsql->append("ORDER BY id ASC", "\n", true);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id\nORDER BY id ASC", $result->get_sql());
        $this->assertSame($rawsql->get_params(), $result->get_params());
        $this->assertNotSame($rawsql, $result);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $result = $rawsql->append("  ORDER BY id ASC", "", true);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id  ORDER BY id ASC", $result->get_sql());
        $this->assertSame($rawsql->get_params(), $result->get_params());
        $this->assertNotSame($rawsql, $result);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $result = $rawsql->append("");
        $this->assertSame($rawsql, $result);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id", $result->get_sql());

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $result = $rawsql->append(" \n");
        $this->assertSame($rawsql, $result);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id", $result->get_sql());

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $result = $rawsql->append("", ' ', false);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id ", $result->get_sql());
        $this->assertSame($rawsql->get_params(), $result->get_params());
        $this->assertNotSame($rawsql, $result);

        $rawsql = new sql("");
        $result = $rawsql->append("ORDER BY id ASC");
        $this->assertSame($rawsql, $result);
        $this->assertSame("", $result->get_sql());

        $rawsql = new sql("");
        $result = $rawsql->append("ORDER BY id ASC", "\n", false);
        $this->assertSame("\nORDER BY id ASC", $result->get_sql());
        $this->assertSame($rawsql->get_params(), $result->get_params());
        $this->assertNotSame($rawsql, $result);
    }

    public function test_append_sql() {
        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $append = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->append($append);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id OR id = :id2", $result->get_sql());
        $this->assertSame(['id' => 10, 'id2' => 20], $result->get_params());
        $this->assertNotSame($rawsql, $result);
        $this->assertNotSame($append, $result);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $append = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->append($append, "\n", false);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id\nOR id = :id2", $result->get_sql());
        $this->assertSame(['id' => 10, 'id2' => 20], $result->get_params());
        $this->assertNotSame($rawsql, $result);
        $this->assertNotSame($append, $result);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $append = new sql("", []);
        $result = $rawsql->append($append);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id", $result->get_sql());
        $this->assertSame(['id' => 10], $result->get_params());
        $this->assertSame($rawsql, $result);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $append = new sql("", []);
        $result = $rawsql->append($append, "\n", false);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id\n", $result->get_sql());
        $this->assertSame(['id' => 10], $result->get_params());
        $this->assertNotSame($rawsql, $result);
        $this->assertNotSame($append, $result);

        $rawsql = new sql(" \n");
        $append = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->append($append);
        $this->assertSame(" \n", $result->get_sql());
        $this->assertSame([], $result->get_params());
        $this->assertSame($rawsql, $result);

        $rawsql = new sql(" \n", []);
        $append = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->append($append, "\t", false);
        $this->assertSame(" \n\tOR id = :id", $result->get_sql());
        $this->assertSame(['id' => 20], $result->get_params());
        $this->assertNotSame($rawsql, $result);
        $this->assertNotSame($append, $result);
    }

    public function test_append_parameter_merging() {

        // NOTE: PostgreSQL $1, $2 params are not tested because they are always converted to named params in constructor.

        $rawsql = new sql("SELECT * FROM {course} WHERE id = ? OR id = ?", [10, 20]);
        $append = new sql("OR id = ?", ['id' => 30]);
        $result = $rawsql->append($append);
        $this->assertSame("SELECT * FROM {course} WHERE id = ? OR id = ? OR id = ?", $result->get_sql());
        $this->assertSame([10, 20, 30], $result->get_params());
        $this->assertNotSame($rawsql, $result);
        $this->assertNotSame($append, $result);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = ? OR id = ?", [10, 20]);
        $append = new sql("OR id = :id", ['id' => 30]);
        $result = $rawsql->append($append);
        $lasti = $this->get_last_unique_param_i('param');
        $this->assertSame("SELECT * FROM {course} WHERE id = :uq_param_" . ($lasti - 1). " OR id = :uq_param_" . $lasti. " OR id = :id", $result->get_sql());
        $this->assertSame(['uq_param_' . ($lasti - 1) => 10, 'uq_param_' . $lasti => 20, 'id' => 30], $result->get_params());
        $this->assertNotSame($rawsql, $result);
        $this->assertNotSame($append, $result);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id1 OR id = :id2", ['id1' => 10, 'id2' => 20]);
        $append = new sql("OR id = :id11", ['id11' => 30]);
        $result = $rawsql->append($append);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id1 OR id = :id2 OR id = :id11", $result->get_sql());
        $this->assertSame(['id1' => 10, 'id2' => 20, 'id11' => 30], $result->get_params());
        $this->assertNotSame($rawsql, $result);
        $this->assertNotSame($append, $result);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id1 OR id = :id2", ['id1' => 10, 'id2' => 20]);
        $append = new sql("OR id = :id1 OR id = :id2", ['id1' => 30, 'id2' => 40]);
        $result = $rawsql->append($append);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id1 OR id = :id2 OR id = :id3 OR id = :id4", $result->get_sql());
        $this->assertSame(['id1' => 10, 'id2' => 20, 'id3' => 30, 'id4' => 40], $result->get_params());
        $this->assertNotSame($rawsql, $result);
        $this->assertNotSame($append, $result);

        $rawsql = new sql("SELECT * FROM {course} WHERE id = :id1 OR id = :id2", ['id1' => 10, 'id2' => 20]);
        $append = new sql("OR id = ?", [30]);
        $result = $rawsql->append($append);
        $lasti = $this->get_last_unique_param_i('param');
        $this->assertSame("SELECT * FROM {course} WHERE id = :id1 OR id = :id2 OR id = :uq_param_" . $lasti, $result->get_sql());
        $this->assertSame(['id1' => 10, 'id2' => 20, 'uq_param_' . $lasti => 30], $result->get_params());
        $this->assertNotSame($rawsql, $result);
        $this->assertNotSame($append, $result);
    }

    public function test_prepend_string() {
        $prepend = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $rawsql = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->prepend($prepend);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id OR id = :id2", $result->get_sql());
        $this->assertSame(['id' => 10, 'id2' => 20], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($prepend, $result);

        $prepend = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $rawsql = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->prepend($prepend, "\n", false);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id\nOR id = :id2", $result->get_sql());
        $this->assertSame(['id' => 10, 'id2' => 20], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($prepend, $result);

        $prepend = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $rawsql = new sql("", []);
        $result = $rawsql->prepend($prepend);
        $this->assertSame("", $result->get_sql());
        $this->assertSame([], $result->get_params());
        $this->assertSame($rawsql, $result);

        $prepend = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $rawsql = new sql("", []);
        $result = $rawsql->prepend($prepend, "\n", false);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id\n", $result->get_sql());
        $this->assertSame(['id' => 10], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($prepend, $result);

        $prepend = new sql(" \n");
        $rawsql = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->prepend($prepend);
        $this->assertSame("OR id = :id", $result->get_sql());
        $this->assertSame(['id' => 20], $result->get_params());
        $this->assertSame($rawsql, $result);
        $this->assertNotSame($prepend, $result);

        $prepend = new sql(" \n", []);
        $rawsql = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->prepend($prepend, "\t", false);
        $this->assertSame(" \n\tOR id = :id", $result->get_sql());
        $this->assertSame(['id' => 20], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($prepend, $result);
    }

    public function test_prepend_sql() {
        $prepend = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $rawsql = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->prepend($prepend);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id OR id = :id2", $result->get_sql());
        $this->assertSame(['id' => 10, 'id2' => 20], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($rawsql, $result);

        $prepend = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $rawsql = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->prepend($prepend, "\n", false);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id\nOR id = :id2", $result->get_sql());
        $this->assertSame(['id' => 10, 'id2' => 20], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($rawsql, $result);

        $prepend = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $rawsql = new sql("", []);
        $result = $rawsql->prepend($prepend);
        $this->assertSame("", $result->get_sql());
        $this->assertSame([], $result->get_params());
        $this->assertSame($rawsql, $result);
        $this->assertNotSame($prepend, $result);

        $prepend = new sql("SELECT * FROM {course} WHERE id = :id", ['id' => 10]);
        $rawsql = new sql("", []);
        $result = $rawsql->prepend($prepend, "\n", false);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id\n", $result->get_sql());
        $this->assertSame(['id' => 10], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($rawsql, $result);

        $prepend = new sql(" \n");
        $rawsql = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->prepend($prepend);
        $this->assertSame("OR id = :id", $result->get_sql());
        $this->assertSame(['id' => 20], $result->get_params());
        $this->assertSame($rawsql, $result);
        $this->assertNotSame($prepend, $result);

        $prepend = new sql(" \n", []);
        $rawsql = new sql("OR id = :id", ['id' => 20]);
        $result = $rawsql->prepend($prepend, "\t", false);
        $this->assertSame(" \n\tOR id = :id", $result->get_sql());
        $this->assertSame(['id' => 20], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($rawsql, $result);
    }

    public function test_prepend_parameter_merging() {
        // NOTE: PostgreSQL $1, $2 params are not tested because they are always converted to named params in constructor.

        $prepend = new sql("SELECT * FROM {course} WHERE id = ? OR id = ?", [10, 20]);
        $rawsql = new sql("OR id = ?", ['id' => 30]);
        $result = $rawsql->prepend($prepend);
        $this->assertSame("SELECT * FROM {course} WHERE id = ? OR id = ? OR id = ?", $result->get_sql());
        $this->assertSame([10, 20, 30], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($rawsql, $result);

        $prepend = new sql("SELECT * FROM {course} WHERE id = ? OR id = ?", [10, 20]);
        $rawsql = new sql("OR id = :id", ['id' => 30]);
        $result = $rawsql->prepend($prepend);
        $lasti = $this->get_last_unique_param_i('param');
        $this->assertSame("SELECT * FROM {course} WHERE id = :uq_param_" . ($lasti - 1). " OR id = :uq_param_" . $lasti. " OR id = :id", $result->get_sql());
        $this->assertSame(['uq_param_' . ($lasti - 1) => 10, 'uq_param_' . $lasti => 20, 'id' => 30], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($rawsql, $result);

        $prepend = new sql("SELECT * FROM {course} WHERE id = :id1 OR id = :id2", ['id1' => 10, 'id2' => 20]);
        $rawsql = new sql("OR id = :id11", ['id11' => 30]);
        $result = $rawsql->prepend($prepend);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id1 OR id = :id2 OR id = :id11", $result->get_sql());
        $this->assertSame(['id1' => 10, 'id2' => 20, 'id11' => 30], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($rawsql, $result);

        $prepend = new sql("SELECT * FROM {course} WHERE id = :id1 OR id = :id2", ['id1' => 10, 'id2' => 20]);
        $rawsql = new sql("OR id = :id1 OR id = :id2", ['id1' => 30, 'id2' => 40]);
        $result = $rawsql->prepend($prepend);
        $this->assertSame("SELECT * FROM {course} WHERE id = :id1 OR id = :id2 OR id = :id3 OR id = :id4", $result->get_sql());
        $this->assertSame(['id1' => 10, 'id2' => 20, 'id3' => 30, 'id4' => 40], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($rawsql, $result);

        $prepend = new sql("SELECT * FROM {course} WHERE id = :id1 OR id = :id2", ['id1' => 10, 'id2' => 20]);
        $rawsql = new sql("OR id = ?", [30]);
        $result = $rawsql->prepend($prepend);
        $lasti = $this->get_last_unique_param_i('param');
        $this->assertSame("SELECT * FROM {course} WHERE id = :id1 OR id = :id2 OR id = :uq_param_" . $lasti, $result->get_sql());
        $this->assertSame(['id1' => 10, 'id2' => 20, 'uq_param_' . $lasti => 30], $result->get_params());
        $this->assertNotSame($prepend, $result);
        $this->assertNotSame($rawsql, $result);
    }

    public function test_combine() {
        $parts = [
            new sql("SELECT *"),
            "\n",
            "FROM {course}",
            new sql("WHERE id = :id", ['id' => 10]),
            new sql("AND visible = ?", [1]),
            new sql(""),
        ];
        $rawsql = sql::combine($parts);
        $lastid = $this->get_last_unique_param_i('param');
        $this->assertSame("SELECT * FROM {course} WHERE id = :id AND visible = :uq_param_" . $lastid, $rawsql->get_sql());
        $this->assertSame(['id' => 10, 'uq_param_' . $lastid => 1], $rawsql->get_params());

        $rawsql = sql::combine($parts, "\t", false);
        $lastid = $this->get_last_unique_param_i('param');
        $this->assertSame("SELECT *\t\n\tFROM {course}\tWHERE id = :id\tAND visible = :uq_param_" . $lastid . "\t", $rawsql->get_sql());
        $this->assertSame(['id' => 10, 'uq_param_' . $lastid => 1], $rawsql->get_params());
    }

    public function test_array_access() {
        $sql = "SELECT * FROM {course} WHERE id = ? AND visible = ?";
        $params = [1, true];
        $rawsql = new sql($sql, $params);

        $this->assertTrue(isset($rawsql[0]));
        $this->assertTrue(isset($rawsql['sql']));
        $this->assertTrue(isset($rawsql[1]));
        $this->assertTrue(isset($rawsql['params']));
        $this->assertFalse(isset($rawsql[2]));
        $this->assertSame($sql, $rawsql[0]);
        $this->assertSame($params, $rawsql[1]);

        list($a, $b) = $rawsql;
        $this->assertSame($sql, $a);
        $this->assertSame($params, $b);

        list('sql' => $a, 'params' => $b) = $rawsql;
        $this->assertSame($sql, $a);
        $this->assertSame($params, $b);

        try {
            $rawsql[0] = 'a';
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
        }

        try {
            $rawsql[1] = 'a';
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
        }

        try {
            $rawsql['sql'] = 'a';
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
        }

        try {
            $rawsql['params'] = 'a';
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
        }

        try {
            $rawsql[2] = 'a';
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
        }

        try {
            unset($rawsql[0]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
        }

        try {
            unset($rawsql[1]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
        }

        try {
            unset($rawsql['sql']);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
        }

        try {
            unset($rawsql['params']);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
        }

        $this->assertSame($sql, $rawsql[0]);
        $this->assertSame($params, $rawsql[1]);

        $this->assertSame($sql, $rawsql['0']);
        $this->assertSame($params, $rawsql['1']);

        $this->assertSame($sql, $rawsql['sql']);
        $this->assertSame($params, $rawsql['params']);
    }

    /**
     * Return $i in the last unique database param.
     *
     * @param string $prefix
     * @return int
     */
    protected function get_last_unique_param_i(string $prefix = 'param'): int {
        $next = str_replace('uq_' . $prefix . '_', '', \moodle_database::get_unique_param());
        return $next - 1;
    }
}
