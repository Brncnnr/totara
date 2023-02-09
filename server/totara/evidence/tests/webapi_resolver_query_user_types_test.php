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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_evidence
 */

use core\orm\query\builder;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_evidence\models\evidence_item;
use totara_evidence\models\evidence_type;
use totara_webapi\phpunit\webapi_phpunit_helper;

class webapi_resolver_query_user_types_testcase extends advanced_testcase {

    private const QUERY = "totara_evidence_user_evidence_types";

    use webapi_phpunit_helper;

    public function test_load_types_as_admin() {
        $data = $this->create_test_data();

        $this->setAdminUser();

        // Test without filters applied, page size 2
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($data->user->id));
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(4, $result['items']);

        [$type1, $type2, $hidden_type, $type3, $unused_type] = $data->types;
        $expected_type_ids = [$type1->id, $type2->id, $type3->id, $hidden_type->id];
        $actual_type_ids = array_column($result['items'], 'id');

        $this->assertEqualsCanonicalizing($expected_type_ids, $actual_type_ids);
        $this->assertNotContains($unused_type->id, $actual_type_ids);
    }

    public function test_load_types_as_owner() {
        $data = $this->create_test_data();

        // Test without filters applied, page size 2
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($data->user->id));
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(4, $result['items']);

        [$type1, $type2, $hidden_type, $type3, $unused_type] = $data->types;
        $expected_type_ids = [$type1->id, $type2->id, $type3->id, $hidden_type->id];
        $actual_type_ids = array_column($result['items'], 'id');

        $this->assertEqualsCanonicalizing($expected_type_ids, $actual_type_ids);
        $this->assertNotContains($unused_type->id, $actual_type_ids);
    }

    public function test_load_types_as_different_user() {
        $data = $this->create_test_data();

        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);

        // Test without filters applied, page size 2
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($data->user->id));
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(0, $result['items']);

        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability('totara/evidence:viewanyevidenceonothers', CAP_ALLOW, $role_id, context_system::instance()->id);

        // With the right capability the other user should see the types
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($data->user->id));
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(4, $result['items']);

        [$type1, $type2, $hidden_type, $type3, $unused_type] = $data->types;
        $expected_type_ids = [$type1->id, $type2->id, $type3->id, $hidden_type->id];
        $actual_type_ids = array_column($result['items'], 'id');

        $this->assertEqualsCanonicalizing($expected_type_ids, $actual_type_ids);
        $this->assertNotContains($unused_type->id, $actual_type_ids);
    }

    public function test_load_types_defaults_to_current_user() {
        $data = $this->create_test_data();

        // Test without filters applied, page size 2
        $result = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(4, $result['items']);

        [$type1, $type2, $hidden_type, $type3, $unused_type] = $data->types;
        $expected_type_ids = [$type1->id, $type2->id, $type3->id, $hidden_type->id];
        $actual_type_ids = array_column($result['items'], 'id');

        $this->assertEqualsCanonicalizing($expected_type_ids, $actual_type_ids);
        $this->assertNotContains($unused_type->id, $actual_type_ids);
    }

    public function test_no_types() {
        self::setAdminUser();

        // Test without filters applied, page size 2
        $result = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(0, $result['items']);
    }

    public function test_load_types_not_being_logged_in() {
        $this->create_test_data();

        $this->setUser(null);

        $this->expectException(require_login_exception::class);

        $this->resolve_graphql_query(self::QUERY, []);
    }

    public function test_feature_not_enabled() {
        $this->create_test_data();

        advanced_feature::disable('evidence');

        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage('Feature evidence is not available.');

        $this->resolve_graphql_query(self::QUERY, []);
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

    public function test_query_operation() {
        $this->create_test_data();

        // Test without filters applied, page size 2
        $result = $this->parsed_graphql_operation(self::QUERY, ['input' => []]);
        $this->assert_webapi_operation_successful($result);
        $data = $this->get_webapi_operation_data($result);

        $this->assertArrayHasKey('items', $data);
        $this->assertCount(4, $data['items']);
    }

    private function create_test_data() {
        self::setAdminUser();

        $evidence_generator = $this->getDataGenerator()->get_plugin_generator('totara_evidence');
        $user = $evidence_generator->create_evidence_user();

        // create evidence type
        $type_1 = $evidence_generator->create_evidence_type(['name' => 'Type_1']);
        $type_2 = $evidence_generator->create_evidence_type(['name' => 'Type_2']);
        $type_3 = $evidence_generator->create_evidence_type(
            ['name' => 'Type_3 (hidden)', 'status' => evidence_type::STATUS_HIDDEN]
        );
        $type_4 = $evidence_generator->create_evidence_type(['name' => 'Type_4']);
        $type_5 = $evidence_generator->create_evidence_type(['name' => 'Type_5']);

        $field_data = (object) [
            'key' => 'value',
        ];

        // create evidence items
        $items[] = evidence_item::create($type_1, $user, $field_data, 'Evidence1');
        $items[] = evidence_item::create($type_2, $user, $field_data, 'Evidence2');
        $items[] = evidence_item::create($type_3, $user, $field_data, 'Evidence3');
        $items[] = evidence_item::create($type_4, $user, $field_data, 'Evidence4');

        // set login user
        global $DB;
        $login_user = $user = $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);
        $this->setUser($login_user);

        // encapsulate return value
        $data = new stdClass();
        $data->user = $user;
        $data->types = [$type_1, $type_2, $type_3, $type_4, $type_5];
        $data->items = $items;

        return $data;
    }

    /**
     * Construct query parameters
     *
     * @param int $user_id
     * @return array
     */
    private function get_query_options(int $user_id) {
        return [
            'input' => [
                'user_id' => $user_id,
            ],
        ];
    }

}