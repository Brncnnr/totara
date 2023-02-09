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
 * @package totara_hierarchy
 */

use core_phpunit\testcase;
use hierarchy_goal\entity\company_goal_framework;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara hierarchy personal goal type resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_type_company_goal_framework_testcase extends testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'totara_hierarchy_company_goal_framework';

    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Only company goal framework records are accepted");

        $this->resolve_graphql_type(self::TYPE, 'id', 'bad source');
    }

    public function test_resolve(): void {
        self::setAdminUser();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = self::getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $goal_framework_data = [
            'idnumber' => 'test111',
            'shortname' => 'framework111',
            'fullname' => 'Framework 111',
            'description' => 'should return null',
        ];

        $goal_framework = $hierarchy_generator->create_goal_frame($goal_framework_data);
        $goal_framework_data['id'] = $goal_framework->id;

        $goal_framework = new company_goal_framework($goal_framework->id);

        foreach ($goal_framework_data as $field => $expected_value) {
            $value = $this->resolve_graphql_type(self::TYPE, $field, $goal_framework, []);
            if ($field === 'description') {
                self::assertNull($value);
            } else {
                self::assertEquals($expected_value, $value);
            }
        }
    }
}
