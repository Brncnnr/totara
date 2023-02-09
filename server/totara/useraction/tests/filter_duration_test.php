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
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_useraction\filter\duration;
use totara_useraction\model\scheduled_rule\execution_data;

/**
 * @group totara_useraction
 */
class totara_useraction_filter_duration_testcase extends testcase {

    private $suspended_user_ids;

    public function setUp(): void {
        for ($i = 1; $i <= 5; $i++) {
            $this->getDataGenerator()->create_user();
        }

        $suspended_users = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $suspended_users[] = $user->id;
            user_suspend_user($user->id);
        }
        $this->suspended_user_ids = $suspended_users;
    }

    protected function tearDown(): void {
        $this->suspended_user_ids = null;
        parent::tearDown();
    }

    public function test_suspended_status_filter() {
        $filter = duration::create_from_input([
            'source' => duration::ENUM_SUSPENDED,
            'unit' => duration::ENUM_UNIT_DAYS,
            'value' => 2,
        ]);
        $execution_data = execution_data::instance();
        $users = $filter->apply(user::repository(), $execution_data)->get();
        $this->assertEmpty($users);

        // Backdate suspended_users to 2 days ago.
        $two_days_in_seconds = duration::unit_to_seconds(3, duration::UNIT_DAYS);
        builder::table('totara_userdata_user')
            ->where_in('userid', $this->suspended_user_ids)
            ->update([
                'timesuspended' => $execution_data->get_timestamp() - $two_days_in_seconds,
            ]);

        $users = $filter->apply(user::repository(), $execution_data)->get()->pluck('id');
        $this->assertEqualsCanonicalizing($this->suspended_user_ids, $users);
    }
}
