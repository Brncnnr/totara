<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
use core_phpunit\testcase;
use totara_contentmarketplace\sync;
use totara_contentmarketplace\testing\mock\mock_sync_action;
use totara_core\http\clients\simple_mock_client;

/**
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_sync_testcase extends testcase {
    /**
     * @return void
     */
    public function test_set_action_classes_with_invalid_class(): void {
        $client = new simple_mock_client();
        $sync = new sync($client);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            sprintf("Invalid sync class %s", simple_mock_client::class)
        );
        $sync->set_sync_action_classes([simple_mock_client::class]);
    }

    /**
     * @return void
     */
    public function test_set_action_classes(): void {
        $client = new simple_mock_client();
        $sync = new sync($client);

        // Checks that mock sync action is being run correctly.
        self::assertFalse(mock_sync_action::get_invoked());

        $sync->set_sync_action_classes([mock_sync_action::class]);
        $sync->execute(false);

        self::assertTrue(mock_sync_action::get_invoked());
    }
}