<?php
/**
 * This file is part of Totara Core
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
 * @author Scott Davies <scott.davies@totaralearning.com>
 * @package totara_api
 */

use core_phpunit\testcase;
use GraphQL\Error\DebugFlag;
use totara_api\global_api_config;
use totara_api\response_debug;

/**
 * Unit tests for the global_api_config class for the totara_api.
 */
class totara_api_global_api_config_testcase extends testcase {

    /**
     * @return void
     */
    public function test_invalid_user_update_error_response_levels(): void {
        self::setAdminUser();
        $original_response_debug_setting = global_api_config::get_response_debug();

        // Test with 'none' level.
        set_config('response_debug', response_debug::ERROR_RESPONSE_LEVEL_NONE, 'totara_api');
        $debug_response_level = global_api_config::get_response_debug_flag();
        $this->assertEquals(DebugFlag::NONE, $debug_response_level);

        // Test with 'normal' level.
        set_config('response_debug', response_debug::ERROR_RESPONSE_LEVEL_NORMAL, 'totara_api');
        $debug_response_level = global_api_config::get_response_debug_flag();
        $this->assertEquals(DebugFlag::INCLUDE_DEBUG_MESSAGE, $debug_response_level);

        // Test with 'developer' level.
        set_config('response_debug', response_debug::ERROR_RESPONSE_LEVEL_DEVELOPER, 'totara_api');
        $debug_response_level = global_api_config::get_response_debug_flag();
        $this->assertEquals(3, $debug_response_level);

        // Test with an invalid level - this will be ignored and the default response level should be returned instead.
        $debug_response_level = global_api_config::get_response_debug_flag(-1);
        $this->assertEquals(DebugFlag::INCLUDE_DEBUG_MESSAGE, $debug_response_level);

        // Tear down.
        set_config('response_debug', $original_response_debug_setting, 'totara_api');
    }
}
