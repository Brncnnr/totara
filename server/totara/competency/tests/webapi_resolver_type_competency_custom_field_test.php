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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_competency
 */

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_competency_custom_field_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_competency_custom_field';

    public function test_resolve_successful() {
        $comp_custom_fields = $this->create_data();
        // resolve title
        $this->assertEquals(
            'title one',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'title', $comp_custom_fields, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            '<p>title one</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'title', $comp_custom_fields, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'title one',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'title', $comp_custom_fields, ['format' => format::FORMAT_PLAIN])
        );

        // resolve value
        $this->assertEquals(
            $comp_custom_fields->value, $this->resolve_graphql_type(self::QUERY_TYPE, 'value', $comp_custom_fields)
        );

        // resolve type
        $this->assertEquals($comp_custom_fields->type, $this->resolve_graphql_type(self::QUERY_TYPE, 'type', $comp_custom_fields));
    }

    public function test_resolve_unknown_field() {
        $comp_custom_fields = $this->create_data();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown field');
        $this->resolve_graphql_type(self::QUERY_TYPE, 'unknown_fields', $comp_custom_fields);
    }

    private function create_data() {
        $comp_custom_fields = new stdClass();
        $comp_custom_fields->title = '<p>title one</p>';
        $comp_custom_fields->value = 123;
        $comp_custom_fields->type = 1;

        return $comp_custom_fields;
    }
}