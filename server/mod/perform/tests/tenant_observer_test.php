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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\util as perform_util;
use totara_tenant\local\util as tenant_util;


/**
 * @group perform
 */
class mod_perform_tenant_observer_testcase extends advanced_testcase {

    public function test_tenant_deleted(): void {
        global $DB;

        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $generator->get_plugin_generator('mod_perform');

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant(null);
        $tenant_user1 = $generator->create_user(
            ['tenantid' => $tenant1->id, 'tenantusermanager' => $tenant1->idnumber, 'tenantdomainmanager' => $tenant1->idnumber]
        );

        $this->setUser($tenant_user1);
        $perform_category_id = perform_util::get_default_category_id();
        $this->setAdminUser();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_tenant_id($tenant1->id)
            ->set_category_id($perform_category_id)
            ->set_number_of_activities(2)
            ->set_number_of_users_per_user_group_type(1)
            ->disable_user_assignments()
            ->disable_subject_instances();

        $activities = $perform_generator->create_full_activities($configuration);

        $this->assertSame(2, $DB->count_records('course', ['category' => $perform_category_id, 'visible' => 1]));

        tenant_util::delete_tenant($tenant1->id, tenant_util::DELETE_TENANT_USER_DELETE);

        $this->assertSame(0, $DB->count_records('course', ['category' => $perform_category_id, 'visible' => 1]));
        $this->assertSame(2, $DB->count_records('course', ['category' => $perform_category_id, 'visible' => 0]));
    }

}
