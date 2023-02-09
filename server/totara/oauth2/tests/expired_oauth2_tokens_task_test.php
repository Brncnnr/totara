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
 * @author Scott Davies <scott.davies@totara.com>
 * @package totara_oauth2
 */

use core_phpunit\testcase;
use totara_oauth2\task\expired_oauth2_tokens_task;
use totara_oauth2\testing\generator;
use totara_oauth2\entity\access_token;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit test(s) for the expired_oauth2_tokens_task scheduled task.
 */
class expired_oauth2_tokens_task_testcase extends testcase {
    /**
     * @return void
     */
    public function test_expired_oauth_token_records_deleted(): void {
        $generator = generator::instance();
        $provider = $generator->create_client_provider("test_api_clientp_1");

        // Create 3 expired access tokens.
        $old_timestamps = [
            DAYSECS * 2, // 2 days ago
            DAYSECS, // 1 day ago
            HOURSECS, // 1 hour ago
        ];
        for ($i = 0; $i < 3; $i++) {
            $params = [
                'access_token' => uniqid(),
                'expires' => time() - $old_timestamps[$i],
                'scope' => null
            ];
            $entity = $generator->create_access_token(null, $params);

            if ($i === 2) {
                $invalid_token_check_identifier = $entity->getIdentifier();
            }
        }

        // Create 2 non-expired access tokens.
        $valid_timestamps = [
            HOURSECS, // 1 hour in the future
            DAYSECS // 1 day in the future
        ];
        for ($i = 0; $i < 2; $i++) {
            $params = [
                'access_token' => uniqid(),
                'expires' => time() + $old_timestamps[$i],
                'scope' => null
            ];
            $entity = $generator->create_access_token(null, $params);
        }

        $original_count = access_token::repository()->count();
        $this->assertGreaterThanOrEqual(5, $original_count);

        // Operate.
        $task = new expired_oauth2_tokens_task();
        $task->execute();

        // Assert - 3 invalid records should have been removed.
        $new_count = access_token::repository()->count();
        $this->assertEquals(3, $original_count - $new_count);

        $this->assertEmpty(access_token::repository()->where('identifier', $invalid_token_check_identifier)->get());
    }
}
