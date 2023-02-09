<?php
/*
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_orm
 * @category test
 */

use core\orm\entity\model;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_testcase.php');

/**
 * Class core_orm_entity_model_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_entity_model_testcase extends orm_entity_testcase {

    public function test_get_id() {
        $params = [
            'id' => 1001
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model($entity);
        $this->assertSame(1001, $model->get_id());
    }

    public function test_load_by_entity() {
        $params = [
            'id' => 123,
            'name' => 'JohnP',
            'created_at' => '1544499389'
        ];

        $entity = new sample_entity($params, false);
        $model = sample_model::load_by_entity($entity);
        $this->assertEquals(123, $model->id);
        $this->assertEquals('JohnP', $model->name);
        $this->assertEquals('1544499389', $model->created_at);
    }

    public function test_load_by_id() {
        $this->create_table();

        $entity = new sample_entity();
        $entity->name = 'JohnP';
        $entity->created_at = '1544499389';
        $entity->parent_id = 1;
        $entity->save();

        $model = sample_model::load_by_id($entity->id);
        $this->assertSame('JohnP', $model->name);
        $this->assertSame('1544499389', $model->created_at);
    }

    public function test_limited_access_to_array() {
        $params = [
            'id'        => 123,
            'name'      => 'John',
            'type'      => 'corona',
            'parent_id' => 1001,
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model_limited_access($entity);
        $this->assertEquals('corona', $model->type);
        $this->assertEquals(1001, $model->parent_id);
        $this->assertEquals(sample_entity::class, $model->entity_class);
    }

    public function test_isset() {
        $params = [
            'id'        => 123,
            'name'      => 'John',
            'type'      => 'corona',
            'parent_id' => 1001,
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model_limited_access($entity);

        $this->assertTrue(isset($model->type));
        $this->assertTrue(isset($model->parent_id));
        $this->assertTrue(isset($model->entity_class));
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->id));
        $this->assertFalse(isset($model->foobar));

        $params = [
            'id'        => 123,
            'name'      => 'John',
            'type'      => null,
            'parent_id' => 1001,
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model_limited_access($entity);

        // Null values should return false as in other cases
        $this->assertFalse(isset($model->type));
    }

    public function test_limited_access_non_existing_attribute() {
        $params = [
            'id'        => 123,
            'name'      => 'John',
            'type'      => 'corona',
            'parent_id' => 1001,
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model($entity);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Tried to access a property that is not available: idonotexist');

        $test = $model->idonotexist;
    }

    public function test_accessor_whitelist_takes_precedence() {
        $params = [
            'id'        => 123,
            'name'      => 'John',
            'type'      => 'corona',
            'parent_id' => 1001,
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model_limited_access($entity);

        $this->assertSame('JOHN', $model->name);
    }

    public function test_limited_access_not_defined_attribute() {
        $params = [
            'id'        => 123,
            'name'      => 'John',
            'type'      => 'corona',
            'parent_id' => 1001,
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model_limited_access($entity);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Tried to access a property that is not available: idonotexist');

        $test = $model->idonotexist;
    }

    public function test_limited_access_attribute_not_on_whitelist() {
        $params = [
            'id'        => 123,
            'name'      => 'John',
            'type'      => 'corona',
            'parent_id' => 1001,
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model_limited_access($entity);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Tried to access a property that is not available: is_deleted');

        $test = $model->is_deleted;
    }

    public function test_limited_access_attribute_missing_method_for_whitelisted_property() {
        $params = [
            'id'        => 123,
            'name'      => 'John',
            'type'      => 'corona',
            'parent_id' => 1001,
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model_limited_access($entity);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'Tried to access a method attribute which should exist but does not: get_method_not_implemented'
        );

        $test = $model->method_not_implemented;
    }

    public function test_limited_access_attribute_missing_property_for_whitelisted_property() {
        $params = [
            'id'        => 123,
            'name'      => 'John',
            'type'      => 'corona',
            'parent_id' => 1001,
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model_limited_access($entity);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Tried to access an entity attribute which should exist but does not: unknown_property');

        $test = $model->unknown_property;
    }

    public function test_wrong_entity_class() {
        $params = [
            'id' => 123,
        ];

        $entity = new sample_entity($params, false);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Expected entity class to match model class');

        sample_model_wrong_entity::load_by_entity($entity);
    }

    public function test_non_exist_entity() {
        $entity = new sample_entity();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Can load only existing entities');

        sample_model::load_by_entity($entity);
    }

    public function test_get_entity_copy() {
        $params = [
            'id'        => 123,
            'name'      => 'John',
            'type'      => 'corona',
            'parent_id' => 1001,
        ];

        $entity = new sample_entity($params, false);
        $model = new sample_model($entity);
        $cloned_entity = $model->get_entity_copy();

        $this->assertEquals($entity->name, $cloned_entity->name);
        $this->assertEquals('John', $entity->name);
        $this->assertEquals('John', $cloned_entity->name);

        $cloned_entity->name = 'Steve';

        $this->assertNotEquals($entity->name, $cloned_entity->name);
        $this->assertEquals('John', $entity->name);
        $this->assertEquals('Steve', $cloned_entity->name);

        $entity->name = 'Eric';

        $this->assertNotEquals($entity->name, $cloned_entity->name);
        $this->assertEquals('Eric', $entity->name);
        $this->assertEquals('Steve', $cloned_entity->name);
    }

}

/**
 * Class sample_model
 *
 * @property-read string $name
 * @property-read int $created_at
 * @property-read string $type
 * @property-read string $entity_class
 */
class sample_model extends model {
    protected static function get_entity_class(): string {
        return sample_entity::class;
    }
}

/**
 * Class sample_model_limited_access
 *
 * @property-read string $type
 * @property-read int $parent_id
 * @property-read string $entity_class
 */
class sample_model_limited_access extends model {

    protected $entity_attribute_whitelist = [
        'type',
        'parent_id',
        'name',
        'unknown_property'
    ];

    protected $model_accessor_whitelist = [
        'entity_class',
        'method_not_implemented',
        'name'
    ];

    protected static function get_entity_class(): string {
        return sample_entity::class;
    }

    public function get_name(): string {
        return strtoupper($this->entity->name);
    }
}

class sample_model_wrong_entity extends model {
    protected static function get_entity_class(): string {
        return 'abc';
    }
}
