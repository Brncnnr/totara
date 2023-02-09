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

use core\date_format;
use core\format;
use core\orm\formatter\entity_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_testcase.php');

/**
 * @package core
 * @group orm
 */
class core_orm_entity_formatter_testcase extends orm_entity_testcase {

    public function test_format_entity() {
        $entity = $this->get_entity();

        $formatter = new sample_entity_formatter($entity, context_system::instance());

        $this->assertEquals(123, $formatter->format('id'));
        $this->assertEquals('5', $formatter->format('parent_id'));
        $this->assertEquals('<span>JohnP</span>', $formatter->format('name', format::FORMAT_RAW));
        $this->assertEquals('JohnP', $formatter->format('name', format::FORMAT_PLAIN));
        $this->assertEquals('JohnP', $formatter->format('name', format::FORMAT_HTML));
        $this->assertEquals('1544499389', $formatter->format('created_at', date_format::FORMAT_TIMESTAMP));
        $this->assertEquals('1544598389', $formatter->format('updated_at', date_format::FORMAT_TIMESTAMP));
        $this->assertEquals('11/12/2018', $formatter->format('created_at', date_format::FORMAT_DATELONG));
        $this->assertEquals('12/12/2018', $formatter->format('updated_at', date_format::FORMAT_DATELONG));
    }

    public function test_undefined_mapping() {
        $model = $this->get_entity();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Field was not found in the format map.');

        $formatter = new sample_entity_formatter($model, context_system::instance());
        $formatter->format('is_deleted');
    }

    public function test_unknown_property() {
        $model = $this->get_entity();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown field idontexist');

        $formatter = new sample_entity_formatter($model, context_system::instance());
        $formatter->format('idontexist');
    }

    private function get_entity() {
        $params = [
            'id' => 123,
            'name' => '<span>JohnP</span>',
            'is_deleted' => 0,
            'parent_id' => 5,
            'created_at' => 1544499389,    // 12/11/2018 @ 3:36am (UTC)
            'updated_at' => 1544598389     // 12/12/2018 @ 7:06am (UTC)
        ];

        return new sample_entity($params, false);
    }

    public function test_accepts_only_entities() {
        $class = new stdClass();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Entity formatter can only format entities');

        new sample_entity_formatter($class, context_system::instance());
    }

}

/**
 * Entity formatter for sample_entity
 */
class sample_entity_formatter extends entity_formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'name' => string_field_formatter::class,
            'type' => null,
            'parent_id' => null,
            'entity_class' => null,
            //'is_deleted' => null,  // undefined mapping
            'created_at' => date_field_formatter::class,
            'updated_at' => date_field_formatter::class,
        ];
    }

}