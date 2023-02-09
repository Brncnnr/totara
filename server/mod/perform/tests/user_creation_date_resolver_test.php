<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use core\entity\user;
use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\dynamic\dynamic_source;
use mod_perform\dates\resolvers\dynamic\user_creation_date;

/**
 * Class mod_perform_user_creation_date_resolver_testcase
 *
 * @group perform
 */
class mod_perform_user_creation_date_resolver_testcase extends advanced_testcase {

    public function test_get_option(): void {
        $custom_field_date_resolver = new user_creation_date();
        $options = $custom_field_date_resolver->get_options();
        $this->assertCount(1, $options);

        /** @var dynamic_source $option */
        $option = $options->first();

        $this->assertEquals(user_creation_date::DEFAULT_KEY, $option->get_option_key());
    }

    public function test_resolve(): void {
        $data_generator = self::getDataGenerator();

        $user = user::repository()->find($data_generator->create_user()->id);

        /** @var user $standard_user */
        $user->timecreated = 691848000; // 4 Dec 1991.
        $user->save();

        // Admin is a special case, that has a 0 create date, and will resolve to null.
        $admin = user::repository()->find(2);

        $resolver = (new user_creation_date())->set_parameters(
            new date_offset(0, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
            new date_offset(1, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
            user_creation_date::DEFAULT_KEY,
            [$user->id, $admin->id]
        );

        self::assertEquals(691848000, $resolver->get_start($user->id)); // 4th of December.

        // End dates are adjusted to "end of day".
        self::assertEquals(
            691934400 + DAYSECS,
            $resolver->get_end($user->id)
        ); // 5th of December.

        self::assertNull($resolver->get_start($admin->id)); // 4th of December.
        self::assertNull($resolver->get_end($admin->id)); // 5th of December.
    }

}
