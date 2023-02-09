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
use totara_competency\models\user_group as user_group_model;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_user_group_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_user_group';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Accepting only entities.');

        $this->resolve_graphql_type(self::QUERY_TYPE, 'id', new stdClass());
    }

    public function test_resolve_successful() {
        $user_group = $this->create_data();

        // resolve id
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'id', $user_group);
        $this->assertEquals($user_group->get_id(), $result);

        // resolve name
        $this->assertEquals(
            'pos 1', $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $user_group, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            '<p>pos 1</p>', $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $user_group, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'pos 1', $this->resolve_graphql_type(self::QUERY_TYPE, 'name', $user_group, ['format' => format::FORMAT_PLAIN])
        );

        // resolve is_deleted
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'is_deleted', $user_group);
        $this->assertEquals($user_group->is_deleted(), $result);

        // resolve type
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'type', $user_group);
        $this->assertEquals($user_group->get_type(), $result);
    }

    public function test_resolve_unknown_field() {
        $user_group = $this->create_data();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/Unknown field/");
        $this->resolve_graphql_type(self::QUERY_TYPE, 'unknown_field', $user_group);
    }

    private function create_data() {
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $hierarchy_generator = $generator->hierarchy_generator();
        $fw = $hierarchy_generator->create_pos_frame([]);
        $pos_entity = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => "<p>pos 1</p>"]);
        $pos = user_group_model\position::load_by_id($pos_entity->id);
        return $pos;
    }
}