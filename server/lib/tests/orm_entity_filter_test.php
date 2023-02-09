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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_orm
 * @category test
 */

use core\orm\entity\repository;
use core\orm\entity\filter\equal;
use core\orm\entity\filter\filter;
use core\orm\entity\filter\in;
use core\orm\entity\filter\like;
use core\orm\query\builder;
use core\orm\query\field;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_testcase.php');

/**
 * Class core_orm_entity_filter_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_entity_filter_testcase extends orm_entity_testcase {

    public function test_no_filters() {
        $this->create_sample_record(['name' => 'Barack Obama']);
        $this->create_sample_record(['name' => 'George W. Bush']);
        $this->create_sample_record(['name' => 'George H. Bush']);

        $repository = extended_sample_entity::repository();
        $result = $repository
            ->set_filters(['name' => 'Barack'])
            ->get();

        // No filters defined on the entity means always get all records.
        $this->assertCount(3, $result);
    }

    public function test_pass_filter_object_directly() {
        $this->create_sample_record(['name' => 'Barack Obama']);
        $record2 = $this->create_sample_record(['name' => 'George W. Bush']);
        $record3 = $this->create_sample_record(['name' => 'George H. Bush']);

        $repository = extended_sample_entity::repository();
        $filter = (new like())->set_params('name')->set_value('Bush');
        $result = $repository->set_filters([$filter])->get();

        $this->assertCount(2, $result);
        $this->assertEquals([$record2['id'], $record3['id']], $result->pluck('id'));
    }

    public function test_use_combination_of_default_and_custom_filters() {
        $this->create_sample_record(['name' => 'Barack Obama', 'type' => 1, 'parent_id' => 1]);
        $record2 = $this->create_sample_record(['name' => 'George W. Bush', 'type' => 3, 'parent_id' => 2]);
        $this->create_sample_record(['name' => 'George H. Bush', 'type' => 3, 'parent_id' => 3]);
        $this->create_sample_record(['name' => 'Another Bush', 'type' => 2, 'parent_id' => 2]);

        $default_filters = ['type' => new equal('type')];

        $repository = $this->create_with_default_filters($default_filters);
        $filters = [
            'type' => 3,
            my_custom_filter::class => 'Bush',
            (new equal('parent_id'))->set_value(2)
        ];
        $result = $repository->set_filters($filters)->get();

        // Just this one record matches all three given filters
        $this->assertCount(1, $result);
        $this->assertEquals([$record2['id']], $result->pluck('id'));
    }

    public function test_default_filters_like() {
        $record = $this->create_sample_record(['name' => 'Barack Obama']);
        $record2 = $this->create_sample_record(['name' => 'George W. Bush']);
        $record3 = $this->create_sample_record(['name' => 'George H. Bush']);

        $default_filters = ['name' => (new like())->set_params('name')];

        $repository = $this->create_with_default_filters($default_filters);

        $result = $repository->set_filters(['name' => 'Barack'])->get();

        $this->assertCount(1, $result);
        $this->assertEquals([$record['id']], $result->pluck('id'));

        // We need to create a new entity object
        // as the old one now contains the previous conditions
        $repository = $this->create_with_default_filters($default_filters);

        $result = $repository->set_filters(['name' => 'Bush'])->get();

        // Found two Bushes
        $this->assertCount(2, $result);
        $this->assertEquals([$record2['id'], $record3['id']], $result->pluck('id'));
    }

    public function test_equals_accepts_fields_correctly() {
        $filter = new equal(new field('field'));

        $filter->set_builder(new builder());
        $filter->apply();

        $this->assertTrue(true);
    }

    public function test_like_accepts_fields_correctly() {
        $filter = new like(new field('field'), '');

        $filter->set_builder(new builder());
        $filter->apply();

        $this->assertTrue(true);
    }

    public function test_like_accepts_only_string_patterns() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('pattern param for like filter needs to be a string');

        (new like('', []))->apply();
    }

    public function test_like_accepts_only_boolean_case_sensitivity_param() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('case_sensitive param for like filter needs to be boolean');

        (new like('', '', ''))->apply();
    }

    public function test_in_accepts_only_string_columns() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('cols param for in filter needs to be a string');

        (new in(new stdClass()))->apply();
    }

    public function test_default_filters_equal() {
        $record = $this->create_sample_record(['name' => 'Barack Obama']);
        $record2 = $this->create_sample_record(['name' => 'George W. Bush']);
        $this->create_sample_record(['name' => 'George H. Bush']);

        $default_filters = ['name' => (new equal())->set_params('name')];

        $repository = $this->create_with_default_filters($default_filters);

        // Just filtering for a part of the name should not work.
        $result = $repository->set_filters(['name' => 'Bush'])->get();
        $this->assertCount(0, $result);

        $repository = $this->create_with_default_filters($default_filters);
        $result = $repository->set_filters(['name' => 'George W. Bush'])->get();

        // Only an excact string match gets us results.
        $this->assertCount(1, $result);
        $this->assertEquals([$record2['id']], $result->pluck('id'));

        // search for either one OR the other
        $repository = $this->create_with_default_filters($default_filters);
        $result = $repository->set_filters(['name' => ['George W. Bush', 'Barack Obama']])->get();

        $this->assertCount(2, $result);
        $this->assertEquals([$record['id'], $record2['id']], $result->pluck('id'));
    }

    public function test_default_filters_in() {
        $this->create_sample_records();

        $default_filters = ['type' => (new in())->set_params('type')];

        $repository = $this->create_with_default_filters($default_filters);

        // Only one value is basically the same as equal
        $result = $repository->set_filters(['type' => '1'])->get();
        $this->assertCount(1, $result);

        // Try another one
        $repository = $this->create_with_default_filters($default_filters);
        $result = $repository->set_filters(['type' => 2])->get();

        $this->assertCount(2, $result);

        // Now searching for more than one record.
        $repository = $this->create_with_default_filters($default_filters);
        $result = $repository->set_filters(['type' => [1, 2]])->get();

        $this->assertCount(3, $result);
    }

    public function test_trim() {
        $value = new stdClass();
        $value->value = null;

        $filter = new class() extends filter {
            public function apply() {
                $this->params[0]->value = $this->value;
            }
        };

        $filter->set_params($value)
            ->set_value('  abc  ')
            ->apply();

        $this->assertEquals('abc', $value->value);

        $filter->dont_trim()
            ->set_value('  abc  ')
            ->apply();

        $this->assertEquals('  abc  ', $value->value);

        $filter->set_value(['foo' => 'bar'])
            ->apply();

        $this->assertEquals(['foo' => 'bar'], $value->value);
    }

    public function test_it_returns_a_list_of_filters() {
        $filters = [(new in())->set_params('type')];

        $this->assertEquals($filters, sample_entity::repository()->set_filters($filters)->get_filters());
    }

    /**
     * @param array $default_filters
     * @return repository|PHPUnit_Framework_MockObject_MockObject
     */
    private function create_with_default_filters(array $default_filters = []) {
        /** @var extended_sample_entity|PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMockBuilder(repository::class)
            ->setConstructorArgs([extended_sample_entity::class])
            ->setMethods(['get_default_filters'])
            ->getMock();

        $repository->method('get_default_filters')
            ->willReturn($default_filters);

        return $repository;
    }

}

class my_custom_filter extends filter {

    public function apply() {
        $this->builder->where('name', 'LIKE', "{$this->value}");
    }

}
