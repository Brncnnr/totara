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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_xapi
 */

use core\testing\generator;
use core_phpunit\testcase;
use totara_userdata\userdata\target_user;
use totara_xapi\entity\xapi_statement;
use totara_xapi\userdata\statement;

/**
 * @group totara_xapi
 */
class totara_xapi_userdata_testcase extends testcase {

    public function test_statement_userdata_handling(): void {
        $context = context_system::instance();

        $user1 = generator::instance()->create_user();
        $user1_statement1 = [
            'user_id' => $user1->id,
            'statement' => '{"foo": "bar1"}',
            'time_created' => 123,
        ];
        $user1_statement1_entity = (new xapi_statement($user1_statement1))->save();
        $user1_statement2 = [
            'user_id' => $user1->id,
            'statement' => '{"foo": "bar2"}',
            'time_created' => 456,
        ];
        $user1_statement2_entity = (new xapi_statement($user1_statement2))->save();

        $user2 = generator::instance()->create_user();
        $user2_statement1 = [
            'user_id' => $user2->id,
            'statement' => '{"foo": "bar3"}',
            'time_created' => 789,
        ];
        $user2_statement1_entity = (new xapi_statement($user2_statement1))->save();

        $this->assertEquals(2, statement::execute_count(new target_user($user1), $context));
        $this->assertEquals(1, statement::execute_count(new target_user($user2), $context));

        $user1_export = statement::execute_export(new target_user($user1), $context);
        $this->assertEquals([
            'statement' => [
                [
                    'id' => $user1_statement1_entity->id,
                    'statement' => ['foo' => 'bar1'],
                    'time_created' => 123,
                ],
                [
                    'id' => $user1_statement2_entity->id,
                    'statement' => ['foo' => 'bar2'],
                    'time_created' => 456,
                ],
            ],
        ], $user1_export->data);

        $user2_export = statement::execute_export(new target_user($user2), $context);
        $this->assertEquals([
            'statement' => [
                [
                    'id' => $user2_statement1_entity->id,
                    'statement' => ['foo' => 'bar3'],
                    'time_created' => 789,
                ],
            ],
        ], $user2_export->data);

        $this->assertEquals(2, xapi_statement::repository()->where('user_id', $user1->id)->count());
        $this->assertEquals(1, xapi_statement::repository()->where('user_id', $user2->id)->count());

        $this->assertEquals(statement::RESULT_STATUS_SUCCESS, statement::execute_purge(new target_user($user1), $context));

        $this->assertEquals(0, xapi_statement::repository()->where('user_id', $user1->id)->count());
        $this->assertEquals(1, xapi_statement::repository()->where('user_id', $user2->id)->count());
    }

}
