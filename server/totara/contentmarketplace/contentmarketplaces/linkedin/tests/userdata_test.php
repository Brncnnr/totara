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
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\entity\user_progress;
use contentmarketplace_linkedin\testing\generator as linkedin_generator;
use contentmarketplace_linkedin\userdata\progress;
use core\testing\generator as core_generator;
use core_phpunit\testcase;
use totara_userdata\userdata\target_user;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_userdata_testcase extends testcase {

    public function test_progress_userdata_handling(): void {
        $context = context_system::instance();

        $learning_object1 = linkedin_generator::instance()->create_learning_object('one', [
            'title' => '<script>One</script>',
        ]);
        $learning_object2 = linkedin_generator::instance()->create_learning_object('two', [
            'title' => 'Two',
        ]);

        $user1 = core_generator::instance()->create_user();
        $user2 = core_generator::instance()->create_user();

        $user1_progress1 = [
            'user_id' => $user1->id,
            'learning_object_urn' => $learning_object1->urn,
            'progress' => 20,
            'time_created' => 123,
            'time_updated' => 123,
        ];
        $user1_progress1_entity = (new user_progress($user1_progress1))->save();
        $user1_progress2 = [
            'user_id' => $user1->id,
            'learning_object_urn' => $learning_object2->urn,
            'progress' => 25,
            'time_created' => 456,
            'time_updated' => 456,
        ];
        $user1_progress2_entity = (new user_progress($user1_progress2))->save();

        $user2_progress1 = [
            'user_id' => $user2->id,
            'learning_object_urn' => $learning_object2->urn,
            'progress' => 100,
            'time_created' => 789,
            'time_updated' => 789,
            'time_completed' => 999,
        ];
        $user2_progress1_entity = (new user_progress($user2_progress1))->save();

        $this->assertEquals(2, progress::execute_count(new target_user($user1), $context));
        $this->assertEquals(1, progress::execute_count(new target_user($user2), $context));

        $user1_export = progress::execute_export(new target_user($user1), $context);
        $this->assertEquals([
            'progress' => [
                [
                    'id' => $user1_progress1_entity->id,
                    'learning_object_urn' => 'one',
                    'learning_object_title' => 'One',
                    'progress' => 20,
                    'completed' => false,
                    'time_created' => 123,
                    'time_updated' => 123,
                    'time_completed' => null,
                ],
                [
                    'id' => $user1_progress2_entity->id,
                    'learning_object_urn' => 'two',
                    'learning_object_title' => 'Two',
                    'progress' => 25,
                    'completed' => false,
                    'time_created' => 456,
                    'time_updated' => 456,
                    'time_completed' => null,
                ],
            ],
        ], $user1_export->data);

        $user2_export = progress::execute_export(new target_user($user2), $context);
        $this->assertEquals([
            'progress' => [
                [
                    'id' => $user2_progress1_entity->id,
                    'learning_object_urn' => 'two',
                    'learning_object_title' => 'Two',
                    'progress' => 100,
                    'completed' => true,
                    'time_created' => 789,
                    'time_updated' => 789,
                    'time_completed' => 999,
                ],
            ],
        ], $user2_export->data);

        $this->assertEquals(2, user_progress::repository()->where('user_id', $user1->id)->count());
        $this->assertEquals(1, user_progress::repository()->where('user_id', $user2->id)->count());

        $this->assertEquals(progress::RESULT_STATUS_SUCCESS, progress::execute_purge(new target_user($user1), $context));

        $this->assertEquals(0, user_progress::repository()->where('user_id', $user1->id)->count());
        $this->assertEquals(1, user_progress::repository()->where('user_id', $user2->id)->count());
    }

}
