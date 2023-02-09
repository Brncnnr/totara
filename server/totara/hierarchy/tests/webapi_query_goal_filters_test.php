<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\activity
 *
 * @group perform
 */
class mod_perform_webapi_query_goal_filters_testcase extends testcase {
    private const QUERY = 'totara_hierarchy_goal_filters';

    use webapi_phpunit_helper;

    /**
     * Test the query through the GraphQL stack.
     */
    public function test_ajax_query_successful() {
        self::setAdminUser();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = self::getDataGenerator()->get_plugin_generator('totara_hierarchy');

        // Create company goal frameworks.
        $framework1_data = [
            'idnumber' => 'fw-1',
            'shortname' => 'FW1',
            'fullname' => 'Framework One',
        ];
        $framework1 = $hierarchy_generator->create_goal_frame($framework1_data);
        $framework2_data = [
            'idnumber' => 'fw-2',
            'shortname' => 'FW2',
            'fullname' => 'Framework Two',
        ];
        $framework2 = $hierarchy_generator->create_goal_frame($framework2_data);

        // Create company goal types.
        $company_type1_data = [
            'idnumber' => 'ctype-1',
            'shortname' => 'CTYPE1',
            'fullname' => 'Company Type One',
        ];
        $company_type1_id = $hierarchy_generator->create_goal_type($company_type1_data);
        $company_type2_data = [
            'idnumber' => 'ctype-2',
            'shortname' => 'CTYPE2',
            'fullname' => 'Company Type Two',
        ];
        $company_type2_id = $hierarchy_generator->create_goal_type($company_type2_data);

        // Create personal goal types.
        $personal_type1_data = [
            'idnumber' => 'fw-1',
            'shortname' => 'FW1',
            'fullname' => 'Type One',
        ];
        $personal_type1 = $hierarchy_generator->create_personal_goal_type($personal_type1_data);
        $personal_type2_data = [
            'idnumber' => 'fw-2',
            'shortname' => 'FW2',
            'fullname' => 'Type Two',
        ];
        $personal_type2 = $hierarchy_generator->create_personal_goal_type($personal_type2_data);

        $result = $this->parsed_graphql_operation(self::QUERY, ['input' => ['check_goal_exist' => false]], graphql::TYPE_AJAX, true);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);

        self::assertCount(3, $result);

        // Check returned company goal frameworks.
        $expected_frameworks = [
            $framework1->id =>  $framework1->fullname,
            $framework2->id =>  $framework2->fullname,
        ];
        self::assertCount(2, $result['company_goal_types']['items']);
        foreach ($result['company_goal_frameworks']['items'] as $actual_framework) {
            self::assertCount(2, $actual_framework);
            self::assertEquals($expected_frameworks[$actual_framework['id']], $actual_framework['fullname']);
        }

        // Check returned company goal types.
        $expected_company_types = [
            $company_type1_id => $company_type1_data['fullname'],
            $company_type2_id => $company_type2_data['fullname'],
        ];
        self::assertCount(2, $result['company_goal_types']['items']);
        foreach ($result['company_goal_types']['items'] as $actual_company_type) {
            self::assertCount(2, $actual_company_type);
            self::assertEquals($expected_company_types[$actual_company_type['id']], $actual_company_type['fullname']);
        }

        // Check returned personal goal types.
        $expected_personal_types = [
            $personal_type1->id => $personal_type1_data['fullname'],
            $personal_type2->id => $personal_type2_data['fullname'],
        ];
        self::assertCount(2, $result['personal_goal_types']['items']);
        foreach ($result['personal_goal_types']['items'] as $actual_personal_type) {
            self::assertCount(2, $actual_personal_type);
            self::assertEquals($expected_personal_types[$actual_personal_type['id']], $actual_personal_type['fullname']);
        }
    }
}
