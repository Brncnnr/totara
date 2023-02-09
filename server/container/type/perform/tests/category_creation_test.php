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
 * @package container_perform
 */

use container_perform\perform;
use core\orm\query\builder;
use core_container\container_category_helper;
use container_perform\task\create_missing_categories;
use totara_tenant\local\util;

class container_perform_category_creation_testcase extends advanced_testcase {

    public function test_task_creates_perform_categories(): void {
        // When tasks are executed they always have the admin user set as logged in.
        self::setAdminUser();

        // The perform category should exist upon a fresh installation.
        $this->assertNotNull(container_category_helper::get_default_category_id(perform::get_type(), false));

        set_config('tenantsenabled', 1);

        $tenant1 = util::create_tenant([
            'name' => 'Test 1',
            'idnumber' => 'test1',
        ]);
        $tenant2 = util::create_tenant([
            'name' => 'Test 2',
            'idnumber' => 'test2',
        ]);

        // Delete the container categories for testing.
        builder::table('course_categories')->where_not_null('idnumber')->delete();

        $this->assertFalse(builder::table('course_categories')
            ->where('idnumber', perform::get_type() . '-0')
            ->exists()
        );
        $this->assertFalse(builder::table('course_categories')
            ->where('idnumber', perform::get_type() . '-' . $tenant1->categoryid)
            ->exists()
        );
        $this->assertFalse(builder::table('course_categories')
            ->where('idnumber', perform::get_type() . '-' . $tenant2->categoryid)
            ->exists()
        );

        container_category_helper::create_container_category(perform::get_type(), $tenant2->categoryid);

        $this->assertFalse(builder::table('course_categories')
            ->where('idnumber', perform::get_type() . '-0')
            ->exists()
        );
        $this->assertFalse(builder::table('course_categories')
            ->where('idnumber', perform::get_type() . '-' . $tenant1->categoryid)
            ->exists()
        );
        $this->assertTrue(builder::table('course_categories')
            ->where('idnumber', perform::get_type() . '-' . $tenant2->categoryid)
            ->exists()
        );

        (new create_missing_categories())->execute();

        $this->assertTrue(builder::table('course_categories')
            ->where('idnumber', perform::get_type() . '-0')
            ->exists()
        );
        $this->assertTrue(builder::table('course_categories')
            ->where('idnumber', perform::get_type() . '-' . $tenant1->categoryid)
            ->exists()
        );
        $this->assertTrue(builder::table('course_categories')
            ->where('idnumber', perform::get_type() . '-' . $tenant2->categoryid)
            ->exists()
        );
    }

    /**
     * @group approval_workflow
     */
    public function test_perform_category_is_created_when_tenant_is_created(): void {
        set_config('tenantsenabled', 1);

        $this->assertEquals(1, builder::table('course_categories')->where_like('idnumber', perform::get_type())->count());

        $tenant1 = util::create_tenant([
            'name' => 'Test 1',
            'idnumber' => 'test1',
        ]);

        $this->assertEquals(2, builder::table('course_categories')->where_like('idnumber', perform::get_type())->count());
        $this->assertEquals(3, builder::table('course_categories')->where('parent', $tenant1->categoryid)->count());

        $tenant2 = util::create_tenant([
            'name' => 'Test 2',
            'idnumber' => 'test2',
        ]);

        $this->assertEquals(3, builder::table('course_categories')->where_like('idnumber', perform::get_type())->count());
        $this->assertEquals(3, builder::table('course_categories')->where('parent', $tenant2->categoryid)->count());
    }

}
