<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests covering user APIs.
 */
class totara_tenant_userlib_testcase extends advanced_testcase {
    public function test_user_create_user() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();
        $tenant = $generator->create_tenant(null);

        $this->setAdminUser();

        $data = new stdClass();
        $data->username = 'someusername';
        $data->firstname = 'First';
        $data->lastname = 'Last';
        $data->email = 'fl@example.com';
        $data->confirmed = 1;
        $data->tenantid = $tenant->id;

        $userid = user_create_user($data, false, true);
        $user = $DB->get_record('user', ['id' => $userid]);
        $usercontext = context_user::instance($user->id);
        $this->assertSame($tenant->id, $user->tenantid);
        $this->assertSame($tenant->id, $usercontext->tenantid);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id]));
        $this->assertSame(1, $DB->count_records('cohort_members', ['cohortid' => $tenant->cohortid]));
    }
}
