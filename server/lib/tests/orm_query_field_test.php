<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package core
 * @group orm
 */

use core\dml\sql;
use core\orm\query\builder;
use core\orm\query\field;
use core\orm\query\raw_field;

defined('MOODLE_INTERNAL') || die();

/**
 * Class core_orm_field_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_query_field_testcase extends advanced_testcase {
    public function test_it_generates_field_sql() {
        $builder = (new builder())->from('tbl');

        $this->assertEquals('{tbl}.field', (string) new field('field', $builder));
        $this->assertEquals('{tbl}.*', (string) new field('*', $builder));
        $this->assertEquals('{tbl}.field_with_underscore', (string) new field('field_with_underscore', $builder));
        $this->assertEquals('sum({tbl}.agg)', (string) new field('sum(agg)', $builder));
        $this->assertEquals('sum({tbl}.agg) as x', (string) new field('sum(agg) as x', $builder));
        $this->assertEquals('tbl.field', (string) new field('tbl.field', $builder));
        $this->assertEquals('another_table.field', (string) new field('another_table.field', $builder));
        $this->assertEquals('{tbl}.field5_col3', (string) new field('field5_col3', $builder));
        $this->assertEquals('field5_col3', (string) (new field('field5_col3', $builder))->do_not_prefix());


        $builder = (new builder())->as('tab');

        $this->assertEquals('"tab".field', (string) new field('field', $builder));
        $this->assertEquals('"tab".*', (string) new field('*', $builder));
        $this->assertEquals('tbl.field', (string) new field('tbl.field', $builder));

        $this->assertEquals('max("tab".col) as max_col', (string) new field('max(col) as max_col', $builder));

        $builder = (new builder())->from('table')->as('alias');

        $this->assertEquals('"alias".field', (string) new field('field', $builder));
        $this->assertEquals('"alias".*', (string) new field('*', $builder));
        $this->assertEquals('tbl.field', (string) new field('tbl.field', $builder));
        $this->assertEquals('tbl.field as alias', (string) new field('tbl.field as alias', $builder));


        $field = new field('field');
        $this->assertEmpty($field->get_prefix());

        $field = field::raw('raw as field', ['p' => 'v']);

        $this->assertEquals('raw as field', $field->sql());
        $this->assertEquals(['p' => 'v'], $field->get_params());
    }

    public function test_it_can_create_field_from_raw_sql() {

        $raw = new sql(':may as field', ['may' => 'psychiatric hospital']);

        $field = new field($raw);

        $this->assertEquals(':may as field', $field->sql());
        $this->assertEquals(['may' => 'psychiatric hospital'], $field->get_params());

        $field = field::raw($raw);

        $this->assertEquals(':may as field', $field->sql());
        $this->assertEquals(['may' => 'psychiatric hospital'], $field->get_params());

        // You need to define params via raw sql if you use it.
        field::raw($raw, ['v' => 'f']);
        $this->assertDebuggingCalled('When using sql bag, please pass params through it as well, $params ignored.');
    }

    public function test_it_does_not_like_invalid_fields() {
        $bad_fields = [
            'bad field',
            'as bad as you',
            'not as bad as you',
            'not_bad as $$$',
            '[table].bad',
            '"{bad}".table',
            '{"bad"}.table',
            'good.bad*',
            ''
        ];

        foreach ($bad_fields as $field) {
            try {
                new field($field);
                $this->fail('We have expected a coding exception here for: ' . (string) $field);
            } catch (coding_exception $exception) {
                $this->assertStringContainsString('Invalid field passed', $exception->getMessage());
            }
        }
    }

    public function test_it_gets_and_sets_builder() {
        $a = new builder();
        $b = new builder();


        $field = new raw_field('f', $a);

        $this->assertEquals($a, $field->get_builder());

        $field->set_builder($b);
        $this->assertEquals($b, $field->get_builder());
    }
}