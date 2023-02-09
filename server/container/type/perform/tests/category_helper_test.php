<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package core_container
 */

use container_perform\perform;
use core\orm\query\builder;
use core_container\container_category_helper;
use totara_tenant\local\util;

class container_perform_category_helper_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_default_category(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();

        // Container categories are created upon installation, but for testing purposes we delete it here to simulate lazy creation.
        builder::table('course_categories')->where_like('idnumber', perform::get_type())->delete();

        $category_id = container_category_helper::get_default_category_id(
            perform::get_type(),
            true,
            null,
            $user->id
        );

        $this->assertNotEmpty($category_id);
        $this->assertTrue(
            $DB->record_exists('course_categories', ['id' => $category_id])
        );

        // The first run is where the creation happen if the record is not found.
        // However check if the api is returning the same result if there is actual record.
        $second_category_id = container_category_helper::get_default_category_id(
            perform::get_type(),
            false,
            null,
            $user->id
        );

        $this->assertEquals($category_id, $second_category_id);
    }

    public function test_get_default_category_with_invalid_params(): void {
        global $DB;

        $top_level_category_id = $DB->get_field('course_categories', 'id', ['idnumber' => perform::get_type() . '-0']);
        $this->assertEquals($top_level_category_id, container_category_helper::get_default_category_id(perform::get_type()));

        set_config('tenantsenabled', 1);

        $user = self::getDataGenerator()->create_user();
        // user_id is specified
        $this->assertNotNull(container_category_helper::get_default_category_id(
            perform::get_type(), true, null, $user->id
        ));

        $tenant = util::create_tenant(['name' => 'Test', 'idnumber' => 'test']);
        $tenant_user = self::getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        $tenant_category_id = $DB->get_field('course_categories', 'id', [
            'idnumber' => perform::get_type() . '-' . $tenant->categoryid
        ]);
        $top_level_category_id = $DB->get_field('course_categories', 'id', ['idnumber' => perform::get_type() . '-0']);

        // tenant_id is specified
        $this->assertEquals($tenant_category_id, container_category_helper::get_default_category_id(
            perform::get_type(), true, null, null, $tenant->id
        ));
        self::setUser($tenant_user);
        // User is now logged in so should be alright
        $this->assertEquals($tenant_category_id, container_category_helper::get_default_category_id(perform::get_type()));

        self::setUser(null);
        unset($tenant_user->tenantid);
        self::setUser($tenant_user);
        // Logged in user doesn't have a tenantid in their $USER object, so fetch it from the DB.
        $this->assertEquals($tenant_category_id, container_category_helper::get_default_category_id(perform::get_type()));

        self::setUser($user);
        // Logged in user doesn't have a tenantid at all, so fall back to top level category ID.
        $this->assertEquals($top_level_category_id, container_category_helper::get_default_category_id(perform::get_type()));

        // No user logged in to get tenantid from
        self::setUser(null);
        $this->assertEquals($top_level_category_id, container_category_helper::get_default_category_id(perform::get_type()));
    }

}
