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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package totara_hierarchy
 */

use core_phpunit\testcase;
use totara_hierarchy\entity\competency as competency_entity;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara hierarchy competency type resolver.
 *
 * @group totara_hierarchy
 * @group totara_competency
 */
class totara_hierarchy_webapi_resolver_type_competency_test extends testcase {
    use webapi_phpunit_helper;

    private const TYPE = 'totara_hierarchy_competency';

    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/competency/");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    public function test_invalid_field(): void {
        $competency = new competency_entity();
        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $competency);
    }

    public function test_resolve(): void {
        $competency = new competency_entity();
        $competency->id = 22;
        $competency->fullname = 'short name competency';

        $testcases = [
            'id' => ['id', $competency->id],
            'name' => ['name', $competency->fullname],
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $expected] = $testcase;

            $value = $this->resolve_graphql_type(self::TYPE, $field, $competency, []);

            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }
}