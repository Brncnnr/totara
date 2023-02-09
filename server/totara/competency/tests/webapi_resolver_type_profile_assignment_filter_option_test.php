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
use totara_competency\entity\assignment;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\profile\filter;
use totara_competency\user_groups;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_profile_assignment_filter_option_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_profile_assignment_filter_option';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Accepting only proficiency_value models.');

        $this->resolve_graphql_type(self::QUERY_TYPE, 'name', new stdClass());
    }

    public function test_resolve_successful() {
        $data = $this->create_data();

        // resolve name
        $expect = get_string('directly_assigned', 'totara_competency');
        $this->assertEquals(
            $expect, $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $data->filter, ['format' => format::FORMAT_PLAIN])
        );
        $this->assertEquals(
            $expect, $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $data->filter, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            $expect, $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $data->filter, ['format' => format::FORMAT_RAW])
        );

        // resolve user_group_type
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'user_group_type', $data->filter, []);
        $this->assertEquals(user_groups::USER, $result);
        // resolve user_group_id
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'user_group_id', $data->filter, []);
        $this->assertEquals(12345, $result);

        // resolve type
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'type', $data->filter, []);
        $this->assertEquals(assignment::TYPE_ADMIN, $result);

        // resolve status
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'status', $data->filter, []);
        $this->assertEquals(assignment::STATUS_DRAFT, $result);

        // resolve status_name
        $this->assertEquals(
            "Draft", $this->resolve_graphql_type(self::QUERY_TYPE, 'status_name', $data->filter, ['format' => format::FORMAT_PLAIN])
        );
        $this->assertEquals(
            "Draft", $this->resolve_graphql_type(self::QUERY_TYPE, 'status_name', $data->filter, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            "Draft", $this->resolve_graphql_type(self::QUERY_TYPE, 'status_name', $data->filter, ['format' => format::FORMAT_RAW])
        );
    }

    private function create_data() {
        $data = new class() {
            public $assignment;
            public $filter;
        };
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $comp = $generator->create_competency();
        $assignment = $generator->assignment_generator()->create_assignment(
            [
                'user_group_type' => user_groups::USER,
                'user_group_id' => 12345,
                'competency_id' => $comp->id,
                'type' => assignment::TYPE_ADMIN,
                'status' => assignment::STATUS_DRAFT,
                'status_name' => "<p>draft</p>"
            ]
        );
        $data->assignment = assignment_model::load_by_id($assignment->id);
        $data->filter = new filter($data->assignment, 'test_key');

        return $data;
    }
}