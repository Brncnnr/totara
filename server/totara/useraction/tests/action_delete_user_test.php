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
use totara_useraction\action\action_result;
use totara_useraction\action\delete_user;
use core\entity\user;

/**
 * Test the GraphQL queries for scheduled rules.
 *
 * @group totara_useraction
 */
class totara_useraction_action_delete_user_testcase extends testcase {
    /**
     * Assert the delete user action can delete a user.
     *
     * @return void
     */
    public function test_delete_user(): void {
        global $DB;
        $this->setAdminUser();

        $user_a = $this->getDataGenerator()->create_user();

        self::assertTrue($DB->record_exists('user', ['id' => $user_a->id]));

        $user_action = new delete_user();

        $result = $user_action->execute(new user($user_a->id));
        self::assertInstanceOf(action_result::class, $result);
        self::assertTrue($result->is_success());
        self::assertFalse($DB->record_exists('user', ['id' => $user_a->id, 'deleted' => 0]));

        $user_a = new user($user_a->id);
        $DB->get_record('user', ['id' => $user_a->id]);

        $result = $user_action->execute($user_a);
        self::assertInstanceOf(action_result::class, $result);
        self::assertFalse($result->is_success());
        self::assertEquals('User was previously deleted', $result->get_message());
        self::assertFalse($DB->record_exists('user', ['id' => $user_a->id, 'deleted' => 0]));

        // Force a failure in
        $user_b = $this->getDataGenerator()->create_user();
        unset($user_b->username);
        $result = $user_action->execute(new user($user_b));
        self::assertFalse($result->is_success());
        $this->assertDebuggingCalled(
            'Could not delete: Coding error detected, it must be fixed by a programmer: ' .
            'Invalid $user parameter in delete_user() detected'
        );

        $user_c = guest_user();
        $result = $user_action->execute(new user($user_c));
        self::assertFalse($result->is_success());
        self::assertSame("Unknown", $result->get_message());
        $this->assertDebuggingCalled('Guest user account can not be deleted.');
    }
}