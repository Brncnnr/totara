<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

use core_phpunit\testcase;
use totara_useraction\fixtures\mock_action;
use totara_useraction\fixtures\mock_invalid_action;
use totara_useraction\local\testing\mock_actions;

/**
 * Test the GraphQL queries for scheduled rules.
 *
 * @group totara_useraction
 */
class totara_useraction_action_factory_testcase extends testcase {
    use mock_actions;

    /**
     * Assert the delete user action can delete a user.
     *
     * @return void
     */
    public function test_factory_create(): void {
        // Test a valid action
        $action = totara_useraction\action\factory::create(mock_action::class);
        self::assertInstanceOf(mock_action::class, $action);

        // Test an invalid action
        self::expectException(coding_exception::class);
        totara_useraction\action\factory::create(mock_invalid_action::class);
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->inject_mock_actions();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->remove_mock_actions();
    }

}