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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

use totara_core\http\clients\curl_client;
use totara_msteams\botfw\auth\default_authoriser;
use totara_msteams\botfw\builder;
use totara_msteams\botfw\logger\stdout_logger;
use totara_msteams\botfw\notification\default_notification;
use totara_msteams\botfw\resolver\v3_resolver;
use totara_msteams\botfw\router\null_router;
use totara_msteams\botfw\storage\database_storage;

class totara_msteams_botfw_builder_bot_testcase extends advanced_testcase {
    public function test_build() {
        // Test build() with the default set.
        $bot = builder::bot()->build();
        $this->assertInstanceOf(null_router::class, $bot->get_router());
        $this->assertInstanceOf(default_authoriser::class, $bot->get_authoriser());
        $this->assertInstanceOf(curl_client::class, $bot->get_client());
        $this->assertInstanceOf(v3_resolver::class, $bot->get_resolver());
        $this->assertInstanceOf(default_notification::class, $bot->get_notification());
        $this->assertInstanceOf(database_storage::class, $bot->get_storage());
        $this->assertInstanceOf(stdout_logger::class, $bot->get_logger());
    }
}
