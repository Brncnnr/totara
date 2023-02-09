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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_orm
 * @category test
 */

use core\collection;
use core\date_format;
use core\format;
use core\orm\entity\model;
use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_testcase.php');

/**
 * @package core
 * @group orm
 */
class core_orm_entity_model_formatter_testcase extends orm_entity_testcase {

    public function test_format_model() {
        $model = $this->get_model();

        $formatter = new sample_entity_model_formatter($model, context_system::instance());

        $this->assertEquals(123, $formatter->format('id'));
        $this->assertEquals('<span>JohnP</span>', $formatter->format('name', format::FORMAT_RAW));
        $this->assertEquals('JohnP', $formatter->format('name', format::FORMAT_PLAIN));
        $this->assertEquals('JohnP', $formatter->format('name', format::FORMAT_HTML));
        $this->assertEquals(sample_entity::class, $formatter->format('entity_class'));
        $this->assertEquals('1544499389', $formatter->format('created_at', date_format::FORMAT_TIMESTAMP));
        $this->assertEquals('1544598389', $formatter->format('updated_at', date_format::FORMAT_TIMESTAMP));
        $this->assertEquals('11/12/2018', $formatter->format('created_at', date_format::FORMAT_DATELONG));
        $this->assertEquals('12/12/2018', $formatter->format('updated_at', date_format::FORMAT_DATELONG));

        $expected_array = [
            'col1' => [
                'key1' => 'val1'
            ],
            'col2' => [
                'key2' => 'val2'
            ],
        ];

        $this->assertEquals($expected_array, $formatter->format('collection'));
    }

    public function test_undefined_mapping() {
        $model = $this->get_model();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Field was not found in the format map.');

        $formatter = new sample_entity_model_formatter($model, context_system::instance());
        $formatter->format('is_deleted');
    }

    public function test_inaccessible_property() {
        $model = $this->get_model();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown field parent_id');

        $formatter = new sample_entity_model_formatter($model, context_system::instance());
        $formatter->format('parent_id');
    }

    public function test_unknown_property() {
        $model = $this->get_model();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown field idontexist');

        $formatter = new sample_entity_model_formatter($model, context_system::instance());
        $formatter->format('idontexist');
    }

    public function test_accepts_only_models() {
        $entity = new sample_entity();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Entity model formatter can only format entity models');

        new sample_entity_model_formatter($entity, context_system::instance());
    }

    private function get_model() {
        $params = [
            'id' => 123,
            'name' => '<span>JohnP</span>',
            'is_deleted' => 0,
            'parent_id' => 5,
            'created_at' => 1544499389,    // 12/11/2018 @ 3:36am (UTC)
            'updated_at' => 1544598389     // 12/12/2018 @ 7:06am (UTC)
        ];

        $entity = new sample_entity($params, false);
        return sample_formatted_model::load_by_entity($entity);
    }

}

/**
 * Model for sample_entity
 *
 * @property-read string $name
 * @property-read int $type
 * @property-read string $parent_id
 * @property-read bool $is_deleted
 * @property-read string $params
 * @property-read int $created_at
 * @property-read int $updated_at
 * @property-read string $entity_class
 */
class sample_formatted_model extends model {

    protected $entity_attribute_whitelist = [
        'id',
        'name',
        'type',
        // 'parent_id', // no access on this one
        'is_deleted',
        'created_at',
        'updated_at',
    ];

    protected $model_accessor_whitelist = [
        'entity_class',
        'collection'
    ];

    protected static function get_entity_class(): string {
        return sample_entity::class;
    }

    public static function get_collection(): collection {
        return collection::new([
            'col1' => [
                'key1' => 'val1'
            ],
            'col2' => [
                'key2' => 'val2'
            ],
        ]);
    }
}

/**
 * Entity model formatter for sample_entity
 */
class sample_entity_model_formatter extends entity_model_formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'name' => string_field_formatter::class,
            'type' => null,
            'parent_id' => null,
            'entity_class' => null,
            'collection' => null,
            //'is_deleted' => null,  // undefined mapping
            'created_at' => date_field_formatter::class,
            'updated_at' => date_field_formatter::class,
        ];
    }

}