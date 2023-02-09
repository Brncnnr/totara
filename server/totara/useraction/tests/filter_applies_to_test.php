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

use core\entity\user;
use core\orm\collection;
use core_phpunit\testcase;
use totara_useraction\filter\applies_to;
use totara_useraction\model\scheduled_rule\execution_data;

/**
 * @group totara_useraction
 */
class totara_useraction_filter_applies_to_testcase extends testcase {

    public function setUp(): void {
        for ($i = 1; $i <= 5; $i++) {
            $this->getDataGenerator()->create_user();
        }
    }

    public function test_apply_to_all_users() {
        $filter = new applies_to(true, collection::new([]));
        $users = $filter->apply(user::repository(), execution_data::instance())->get();

        // includes 5 created users, admin & guest user
        $this->assertCount(7, $users);
    }

    public function test_apply_to_audiences() {
        $cohort_1 = $this->getDataGenerator()->create_cohort();
        $cohort_2 = $this->getDataGenerator()->create_cohort();
        $cohort_1_members = [];
        $cohort_2_members = [];

        // Create 3 users and add to audience.
        for ($i = 1; $i <= 3; $i++) {
            $user_id = $this->getDataGenerator()->create_user()->id;
            $cohort_1_members[] = $user_id;
            cohort_add_member($cohort_1->id, $user_id);

            $user_id = $this->getDataGenerator()->create_user()->id;
            $cohort_2_members[] = $user_id;
            cohort_add_member($cohort_2->id, $user_id);
        }

        $filter = applies_to::create_from_input([
            'audiences' => [$cohort_1->id, $cohort_2->id]
        ]);
        $user_ids = $filter->apply(user::repository(), execution_data::instance())->get()->pluck('id');
        $this->assertEqualsCanonicalizing(array_merge($cohort_1_members, $cohort_2_members), $user_ids);
    }

    public function test_apply_to_empty_audience_list() {
        $filter = new applies_to(false, collection::new([]));

        $users = $filter->apply(user::repository(), execution_data::instance())->get();
        $this->assertEmpty($users->all());
    }
}
