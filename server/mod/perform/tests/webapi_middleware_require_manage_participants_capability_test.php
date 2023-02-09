<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use mod_perform\webapi\middleware\require_manage_participants_capability;

/**
 * @coversDefaultClass \mod_perform\webapi\middleware\require_manage_participants_capability
 *
 * @group perform
 */
class mod_perform_webapi_middleware_require_manage_participants_capability_testcase extends testcase {
    public function td_correct_capabilities(): array {
        $manage_all = 'mod/perform:manage_all_participation';
        $manage_si = 'mod/perform:manage_subject_user_participation';

        return [
            "$manage_all" => [[$manage_all]],
            "$manage_si" => [[$manage_si]],
            'both' => [[$manage_all, $manage_si]]
        ];
    }

    /**
     * @dataProvider td_correct_capabilities
     */
    public function test_correct_capabilities(array $capabilities): void {
        $expected = 34324;
        [$user, $next, $context] = $this->create_test_data($expected, $capabilities);
        $payload = payload::create([], $context);

        $this->setUser($user);
        $result = (new require_manage_participants_capability())
            ->handle($payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
    }

    /**
     * @dataProvider td_correct_capabilities
     */
    public function test_no_capabilities(): void {
        $expected = 34324;
        [$user, $next, $context] = $this->create_test_data($expected, []);
        $payload = payload::create([], $context);

        $this->setAdminUser();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('cannot_manage_participant', 'mod_perform'));
        (new require_manage_participants_capability($user->id))
            ->handle($payload, $next);
    }

    /**
     * Generates test data.
     *
     * @param mixed $expected_result value to return as the result of the next
     *        chained "processor" after the require_activity handler.
     * @param string[] $capabilities user capabilities for the generated user.
     *
     * @return array a [user, the next handler to execute, payload context].
     */
    private function create_test_data($expected_result, array $capabilities): array {
        $next = function (payload $payload) use ($expected_result): result {
            return new result($expected_result);
        };

        $context = execution_context::create('dev');

        $user = $this->getDataGenerator()->create_user();

        if ($capabilities) {
            $context_id = context_user::instance($user->id)->id;

            $role_id = builder::get_db()
                ->get_record('role', ['shortname' => 'user'])
                ->id;

            foreach ($capabilities as $capability) {
                assign_capability($capability, CAP_ALLOW, $role_id, $context_id, true);
            }
        }

        $this->setUser($user);

        return [$user, $next, $context];
    }
}
