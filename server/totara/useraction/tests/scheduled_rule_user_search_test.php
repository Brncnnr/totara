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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_useraction
 */

use core\collection;
use core\orm\collection as orm_collection;
use core_phpunit\testcase;
use totara_useraction\filter\applies_to;
use totara_useraction\filter\status;
use totara_useraction\model\scheduled_rule\execution_data;
use totara_useraction\model\user_search;

/**
 * @group totara_useraction
 */
class totara_useraction_scheduled_rule_user_search_testcase extends testcase {

    public function test_get_all() {
        $user_ids = [2];

        for ($i = 1; $i <= 5; $i++) {
            $user_ids[] = $this->getDataGenerator()->create_user()->id;
        }
        $searched_users = (new user_search(context_system::instance()))->get_all()->to_array();
        $searched_users_ids = collection::new($searched_users)->pluck('id');
        $this->assertEqualsCanonicalizing($user_ids, $searched_users_ids);
    }

    public function test_apply_filters() {
        $user_ids = [2];

        for ($i = 1; $i <= 5; $i++) {
            $user_ids[] = $this->getDataGenerator()->create_user()->id;
        }
        // suspend a user:
        user_suspend_user(array_pop($user_ids));

        $user_search = new user_search(context_system::instance());
        $users = $user_search->apply_filters(
            [
                new applies_to(true, orm_collection::new([])),
                new status(status::STATUS_SUSPENDED),
            ],
            execution_data::instance()
        )->get_all()->to_array();

        $user_ids_fetched = collection::new($users)->pluck('id');
        $this->assertNotEqualsCanonicalizing($user_ids, $user_ids_fetched);
    }

    public function test_applying_the_same_filter_twice() {
        for ($i = 1; $i <= 5; $i++) {
            $user_ids[] = $this->getDataGenerator()->create_user()->id;
        }
        // suspend a user:
        user_suspend_user(array_pop($user_ids));

        $user_search = new user_search(context_system::instance());
        $status_filter = new status(status::STATUS_SUSPENDED);
        $execution_data = execution_data::instance();
        $user_search->apply_filter($status_filter, $execution_data);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Filter already applied");
        $user_search->apply_filter($status_filter, $execution_data);
    }
}
