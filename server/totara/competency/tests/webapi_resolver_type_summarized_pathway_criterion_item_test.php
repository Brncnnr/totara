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
class totara_competency_webapi_resolver_type_summarized_pathway_criterion_item_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_summarized_pathway_criterion_item';

    public function test_resolve_successful() {
        $data = $this->create_data();

        // resolve description
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data, ['format' => format::FORMAT_HTML]);
        $this->assertEquals('Description', $result);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('<p>Description</p>', $result);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $data, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals('Description', $result);

        // resolve error
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'error', $data, ['format' => format::FORMAT_HTML]);
        $this->assertEquals('Error', $result);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'error', $data, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('<p>Error</p>', $result);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'error', $data, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals('Error', $result);
    }

    public function test_resolve_unknown_field() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown field');
        $this->resolve_graphql_type(self::QUERY_TYPE, 'unknown', new stdClass(), []);
    }

    /**
     * Create a summarized pathway criterion item
     *
     * @param array $param
     * @return stdClass
     */
    private function create_data() {
        $data = new stdClass();

        $param = [
            'description' => "<p>Description</p>",
            'error'       => '<p>Error</p>'
        ];

        $data->description = $param['description'];
        $data->error = $param['error'];

        return $data;
    }
}