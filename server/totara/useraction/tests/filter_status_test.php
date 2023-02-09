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
use core_phpunit\testcase;
use totara_useraction\filter\status;
use totara_useraction\model\scheduled_rule\execution_data;

/**
 * @group totara_useraction
 */
class totara_useraction_filter_status_testcase extends testcase {

    public function setUp(): void {
        for ($i = 1; $i <= 5; $i++) {
            $this->getDataGenerator()->create_user();
        }

        for ($i = 1; $i <= 3; $i++) {
            $user = $this->getDataGenerator()->create_user();
            user_suspend_user($user->id);
        }
    }

    public function test_suspended_status_filter() {
        $filter = new status(status::STATUS_SUSPENDED);
        $users = $filter->apply(user::repository(), execution_data::instance())->get();

        // includes 3 suspended users
        $this->assertCount(3, $users);
    }
}
