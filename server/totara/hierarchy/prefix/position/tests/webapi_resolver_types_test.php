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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package hierarchy_position
 */

use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_hierarchy_webapi_resolver_position_types_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        /** @var \totara_hierarchy\testing\generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        // Create types.
        for ($x = 1; $x <= 10; ++$x) {
            $gen->create_pos_type();
        }
    }

    /**
     * Confirm that we get the correct amount of records back.
     */
    public function test_types() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user->id);

        // Test direct querying.
        $hierarchy = new \hierarchy();
        $hierarchy->shortprefix = 'pos';
        $result = $hierarchy->get_types();
        $this->assertIsArray($result);
        $this->assertEquals(10, sizeof($result));

        // Test via graphql.
        $result = $this->resolve_graphql_query('totara_hierarchy_position_types');
        $this->assertIsArray($result);
        $this->assertEquals(10, sizeof($result));
    }

}
