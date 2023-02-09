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

use aggregation_highest\highest;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_overall_aggregation_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_overall_aggregation';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/overall_aggregation objects are accepted/");

        $this->resolve_graphql_type(self::QUERY_TYPE, 'aggregation_type', new stdClass());
    }

    public function test_resolve_successful() {
        $highest = new highest();
        // resolve aggregation_type
        $this->assertEquals($highest->get_agg_type(), $this->resolve_graphql_type(self::QUERY_TYPE, 'aggregation_type', $highest));

        // resolve description
        $this->assertEquals($highest->get_description(), $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $highest));

        // resolve title
        $this->assertEquals($highest->get_title(), $this->resolve_graphql_type(self::QUERY_TYPE, 'title', $highest));
    }

    public function test_resolve_unknown_field() {
        $highest = new highest();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown field');
        $this->resolve_graphql_type(self::QUERY_TYPE, 'un_known', $highest);
    }
}