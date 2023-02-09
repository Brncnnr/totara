<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */

use container_workspace\totara_notification\workspace_muter;
use core_phpunit\testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_workspace_muter_testcase extends testcase {

    /**
     * @return void
     */
    public function tearDown(): void {
        workspace_muter::reset();
        parent::tearDown();
    }

    /**
     * @dataProvider test_mute_data
     * @param string $notification
     * @param string $action
     * @param bool $first_result
     * @param bool $second_result
     */
    public function test_mute(string $notification, string $action, bool $first_result, bool $second_result): void {
        call_user_func([workspace_muter::class, $action], $notification, 5, 99);

        $another_user = workspace_muter::is_muted($notification, 5, 2);
        self::assertFalse($another_user);

        $first = workspace_muter::is_muted($notification, 5, 99);
        self::assertEquals($first_result, $first, 'First check failed');

        workspace_muter::mute('second_notif', 5, 2);
        $another_notification = workspace_muter::is_muted('second_notif', 5, 2);
        self::assertTrue($another_notification);

        $another_user = workspace_muter::is_muted($notification, 5, 2);
        self::assertFalse($another_user);

        $second = workspace_muter::is_muted($notification, 5, 99);
        self::assertEquals($second_result, $second, 'Second check failed');

        workspace_muter::unmute($notification, 5, 99);
        $third = workspace_muter::is_muted($notification, 5, 99);
        self::assertFalse($third);
    }

    /**
     * @return array[]
     */
    public function test_mute_data(): array {
        return [
            ['test_abc', 'mute', true, false],
            ['test_abc', 'unmute', false, false],
            ['test_abc', 'full_mute', true, true],
        ];
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        workspace_muter::reset();
    }
}
