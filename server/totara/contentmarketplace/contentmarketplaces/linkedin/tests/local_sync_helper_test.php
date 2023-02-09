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
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\local\sync_helper;
use contentmarketplace_linkedin\task\adhoc_syncing_task;
use core\orm\query\builder;
use core_phpunit\testcase;
use core\entity\adhoc_task;
use contentmarketplace_linkedin\testing\generator;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_local_sync_helper_testcase extends testcase {
    /**
     * @var string
     */
    private const CLASS_NAME = "\\" . adhoc_syncing_task::class;

    /**
     * @return void
     */
    public function test_settings_update_callback_without_client_id(): void {
        set_config("client_secret", "secret", "contentmarketplace_linkedin");

        $db = builder::get_db();
        self::assertEquals(
            0,
            $db->count_records(adhoc_task::TABLE, ["classname" => self::CLASS_NAME])
        );

        sync_helper::settings_update_callback();
        self::assertEquals(
            0,
            $db->count_records(adhoc_task::TABLE, ["classname" => self::CLASS_NAME])
        );
    }

    /**
     * @return void
     */
    public function test_settings_update_callback_without_client_secret(): void {
        set_config("client_id", "id", "contentmarketplace_linkedin");

        $db = builder::get_db();
        self::assertEquals(
            0,
            $db->count_records(adhoc_task::TABLE, ["classname" => self::CLASS_NAME])
        );

        sync_helper::settings_update_callback();
        self::assertEquals(
            0,
            $db->count_records(adhoc_task::TABLE, ["classname" => self::CLASS_NAME])
        );
    }

    /**
     * @return void
     */
    public function test_settings_update_callback_with_sync_already_happened(): void {
        set_config("client_secret", "secret", "contentmarketplace_linkedin");
        set_config("client_id", "id", "contentmarketplace_linkedin");

        $generator = generator::instance();
        $generator->create_learning_object("urn:li:lyndaCourse:211");

        $db = builder::get_db();
        self::assertEquals(
            0,
            $db->count_records(adhoc_task::TABLE, ["classname" => self::CLASS_NAME])
        );

        sync_helper::settings_update_callback();
        self::assertEquals(
            0,
            $db->count_records(adhoc_task::TABLE, ["classname" => self::CLASS_NAME])
        );
    }


    /**
     * @return void
     */
    public function test_settings_update_callback(): void {
        set_config("client_secret", "secret", "contentmarketplace_linkedin");
        set_config("client_id", "id", "contentmarketplace_linkedin");

        $db = builder::get_db();
        self::assertEquals(
            0,
            $db->count_records(adhoc_task::TABLE, ["classname" => self::CLASS_NAME])
        );

        sync_helper::settings_update_callback();
        self::assertEquals(
            1,
            $db->count_records(adhoc_task::TABLE, ["classname" => self::CLASS_NAME])
        );
    }
}