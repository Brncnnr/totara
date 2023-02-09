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
class totara_competency_webapi_resolver_type_summarized_pathway_criteria_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_summarized_pathway_criteria';

    public function test_resolve_item_type() {
        $data = $this->create_data(['item_type' => 'test_type']);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'item_type', $data);
        $this->assertEquals('test_type', $result);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('/Expected value, but was not found and was not nullable./');
        $data = $this->create_data([]);
        $this->resolve_graphql_type(self::QUERY_TYPE, 'item_type', $data);
    }

    public function test_resolve_item_aggregation() {
        $data = $this->create_data(['item_aggregation' => 'test_agg']);
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'item_aggregation', $data);
        $this->assertEquals('test_agg', $result);

        $data = $this->create_data([]);
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'item_aggregation', $data);
        $this->assertNull($result);
    }

    public function test_resolve_error() {
        $data = $this->create_data(['error' => '<p>ERROR</p>']);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'error', $data, ['format' => format::FORMAT_HTML]);
        $this->assertEquals('ERROR', $result);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'error', $data, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('<p>ERROR</p>', $result);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'error', $data, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals('ERROR', $result);
    }

    public function test_resolve_items() {
        $item = new stdClass();
        $item->description = 'test description';
        $item->error = 'test error';
        $items[] = $item;
        $data = $this->create_data(['items' => $items]);

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'items', $data);
        $this->assertEquals($item->description, $result[0]->description);
        $this->assertEquals($item->error, $result[0]->error);
    }

    /**
     * Create summarized pathway criteria
     *
     * @param array $param
     * @return stdClass
     */
    private function create_data(array $param) {
        $data = new stdClass();
        if (isset($param['item_type'])) {
            $data->item_type = $param['item_type'];
        }
        $data->item_aggregation = $param['item_aggregation'] ?? null;
        $data->error = $param['error'] ?? null;
        $data->items = $param['items'] ?? null;

        return $data;
    }
}