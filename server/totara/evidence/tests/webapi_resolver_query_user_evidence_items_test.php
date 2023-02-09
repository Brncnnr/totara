<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_evidence
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_evidence\models\evidence_item;
use totara_webapi\phpunit\webapi_phpunit_helper;

class webapi_resolver_query_user_evidence_items_testcase extends testcase {

    private const QUERY = "totara_evidence_user_evidence_items";

    use webapi_phpunit_helper;

    public function test_pagination_without_filters() {
        $data = $this->create_test_data();

        // Test without filters applied, page size 2
        $result = $this->resolve_graphql_query(
            self::QUERY,
            $this->get_query_options($data->user->id, null, null, 2)
        );
        $this->assertCount(2, $result['items']);

        // Change page size to 10
        $result = $this->resolve_graphql_query(
            self::QUERY,
            $this->get_query_options($data->user->id, null, null, 10)
        );
        $this->assertCount(4, $result['items']);
    }

    public function test_pagination_with_filters() {
        $data = $this->create_test_data();

        // Apply name filter
        $filter = [
            'search' => 'Evidence1'
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($data->user->id, $filter));
        $this->assertCount(1, $result['items']);
        $this->assertEquals('Evidence1', $result['items'][0]->name);

        // Apply evidence type filter
        $filter = [
            'type_id' => $data->type_2->id
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($data->user->id, $filter));
        $this->assertCount(1, $result['items']);

        // Apply name and type filter
        $filter = [
            'search' => 'Evidence1',
            'type_id' => $data->type_1->id
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($data->user->id, $filter));
        $this->assertCount(1, $result['items']);
        $this->assertEquals('Evidence1', $result['items'][0]->name);

        $filter = [
            'search' => 'Evidence1',
            'type_id' => $data->type_2->id
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($data->user->id, $filter));
        $this->assertCount(0, $result['items']);
    }

    public function test_no_input_should_set_current_user_as_default() {
        $data = $this->create_test_data();

        $this->setUser($data->user->id);

        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null));
        $this->assertCount(2, $result['items']);
    }

    public function test_feature_disabled() {
        advanced_feature::disable('evidence');
        self::setAdminUser();

        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage('Feature evidence is not available.');

        $this->resolve_graphql_query(self::QUERY, $this->get_query_options('1'));
    }

    public function test_require_view_self_capability() {
        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        unassign_capability('totara/evidence:viewanyevidenceonself', $role_id);
        unassign_capability('totara/evidence:manageanyevidenceonself', $role_id);
        unassign_capability('totara/evidence:manageownevidenceonself', $role_id);

        $data = $this->create_test_data();

        // Should return an empty array
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($data->user->id));
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertEmpty($result['items']);
    }

    public function test_require_view_other_capability() {
        $data = $this->create_test_data();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        unassign_capability('totara/evidence:viewanyevidenceonothers', $role_id);

        // Should return an empty array
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($data->user->id));
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertEmpty($result['items']);
    }

    private function create_test_data() {
        self::setAdminUser();

        $evidence_generator = $this->getDataGenerator()->get_plugin_generator('totara_evidence');
        $user = $evidence_generator->create_evidence_user();

        // create evidence type
        $type_1 = $evidence_generator->create_evidence_type(['name' => 'Type_1']);
        $type_2 = $evidence_generator->create_evidence_type(['name' => 'Type_2']);

        $field_data = (object) [
            'key' => 'value',
        ];

        // create evidence items
        $items[] = evidence_item::create($type_1, $user, $field_data, 'Evidence1');
        $items[] = evidence_item::create($type_1, $user, $field_data, 'Evidence2');
        $items[] = evidence_item::create($type_1, $user, $field_data, 'Evidence3');
        $items[] = evidence_item::create($type_2, $user, $field_data, 'Evidence4');

        // set login user
        global $DB;
        $login_user = $user = $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);
        $this->setUser($login_user);

        // encapsulate return value
        $data = new stdClass();
        $data->user = $user;
        $data->type_1 = $type_1;
        $data->type_2 = $type_2;
        $data->items = $items;

        return $data;
    }

    /**
     * Construct query parameters
     *
     * @param int $user_id
     * @param null $filters
     * @param null $cursor
     * @param int $result_size
     * @return array
     */
    private function get_query_options($user_id, $filters = null, $cursor = null, $result_size = 2) {
        $options = [
            'input' => [
                'result_size' => $result_size,
                'cursor' => $cursor,
                'user_id' => $user_id,
            ],
        ];
        if (isset($filters)) {
            $options['input']['filters'] = $filters;
        }
        return $options;
    }
}