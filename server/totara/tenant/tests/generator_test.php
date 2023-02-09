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
 * Tests covering tenant generator.
 */
class totara_tenant_generator_testcase extends advanced_testcase {
    public function test_enable_tenants() {
        global $CFG;

        $generator = \totara_tenant\testing\generator::instance();

        $this->assertSame('0', $CFG->tenantsenabled);
        $generator->enable_tenants();
        $this->assertSame('1', $CFG->tenantsenabled);
    }

    public function test_disable_tenants() {
        global $CFG;

        $generator = \totara_tenant\testing\generator::instance();

        $generator->enable_tenants();
        $this->assertSame('1', $CFG->tenantsenabled);
        $generator->disable_tenants();
        $this->assertSame('0', $CFG->tenantsenabled);
    }

    public function test_create_tenant() {
        global $DB, $USER;

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $this->setAdminUser();

        $this->setCurrentTimeStart();
        $tenant = $generator->create_tenant(null);
        $coursecat = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $audience = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $this->assertTrue($DB->record_exists('tenant', ['id' => $tenant->id]));
        $this->assertMatchesRegularExpression('/^Tenant \d+$/', $tenant->name);
        $this->assertMatchesRegularExpression('/^tenantidnumber\d+$/', $tenant->idnumber);
        $this->assertSame('', $tenant->description);
        $this->assertSame(FORMAT_HTML, $tenant->descriptionformat);
        $this->assertSame('0', $tenant->suspended);
        $this->assertTimeCurrent($tenant->timecreated);
        $this->assertSame($USER->id, $tenant->usercreated);
        $this->assertMatchesRegularExpression('/^Tenant \d+ category$/', $coursecat->name);
        $this->assertSame('', $coursecat->description);
        $this->assertSame($tenant->idnumber, $coursecat->idnumber);
        $this->assertMatchesRegularExpression('/^Tenant \d+ audience/', $audience->name);
        $this->assertSame('', $audience->description);
        $this->assertSame($audience->idnumber, $audience->idnumber);

        $data = new stdClass();
        $data->name = 'Prvni tenant';
        $data->idnumber = 'tenant1';
        $data->description = 'Nejaky tenant';
        $data->descriptionformat = FORMAT_MARKDOWN;
        $data->suspended = '1';
        $data->categoryname = 'Kategorie pro prvniho tenanta';
        $data->cohortname = 'Skupina pro prvniho tenanta';
        $this->setCurrentTimeStart();
        $tenant = $generator->create_tenant($data);
        $coursecat = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $audience = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $this->assertTrue($DB->record_exists('tenant', ['id' => $tenant->id]));
        $this->assertSame($data->name, $tenant->name);
        $this->assertSame($data->idnumber, $tenant->idnumber);
        $this->assertSame($data->description, $tenant->description);
        $this->assertSame($data->descriptionformat, $tenant->descriptionformat);
        $this->assertSame($data->suspended, $tenant->suspended);
        $this->assertTimeCurrent($tenant->timecreated);
        $this->assertSame($USER->id, $tenant->usercreated);
        $this->assertSame($data->categoryname, $coursecat->name);
        $this->assertSame('', $coursecat->description);
        $this->assertSame($tenant->idnumber, $coursecat->idnumber);
        $this->assertSame($data->cohortname, $audience->name);
        $this->assertSame('', $audience->description);
        $this->assertSame($audience->idnumber, $audience->idnumber);
    }

    public function test_create_user() {
        global $DB;

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $tenantusermanagerrole = $DB->get_record('role', ['shortname' => 'tenantusermanager']);
        $tenantdomainmanagerrole = $DB->get_record('role', ['shortname' => 'tenantdomainmanager']);

        $tenant1 = $generator->create_tenant(null);
        $tenant2 = $generator->create_tenant(null);
        $tenant3 = $generator->create_tenant(null);

        $this->setAdminUser();

        $data = ['tenantid' => $tenant1->id];
        $user1 = $this->getDataGenerator()->create_user($data);
        $usercontext = context_user::instance($user1->id);
        $this->assertSame($tenant1->id, $user1->tenantid);
        $this->assertSame($tenant1->id, $usercontext->tenantid);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant1->cohortid, 'userid' => $user1->id]));
        $this->assertSame(1, $DB->count_records('cohort_members', ['cohortid' => $tenant1->cohortid]));

        $data = ['tenantmember' => $tenant2->idnumber];
        $user2 = $this->getDataGenerator()->create_user($data);
        $usercontext = context_user::instance($user2->id);
        $this->assertSame($tenant2->id, $user2->tenantid);
        $this->assertSame($tenant2->id, $usercontext->tenantid);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant2->cohortid, 'userid' => $user2->id]));
        $this->assertSame(1, $DB->count_records('cohort_members', ['cohortid' => $tenant2->cohortid]));

        $user3 = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user3->id);
        $this->assertNull($user3->tenantid);
        $this->assertNull($usercontext->tenantid);

        $data = ['tenantparticipant' => "{$tenant1->idnumber}, {$tenant3->idnumber}"];
        $user4 = $this->getDataGenerator()->create_user($data);
        $usercontext = context_user::instance($user4->id);
        $this->assertNull($user4->tenantid);
        $this->assertNull($usercontext->tenantid);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant1->cohortid, 'userid' => $user4->id]));
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant3->cohortid, 'userid' => $user4->id]));
        $this->assertSame(2, $DB->count_records('cohort_members', ['cohortid' => $tenant1->cohortid]));
        $this->assertSame(1, $DB->count_records('cohort_members', ['cohortid' => $tenant2->cohortid]));
        $this->assertSame(1, $DB->count_records('cohort_members', ['cohortid' => $tenant3->cohortid]));

        $functionroleid = function ($ra) {
            return $ra->roleid;
        };

        $data = ['tenantusermanager' => $tenant2->idnumber . ', '. $tenant3->idnumber];
        $user5 = $this->getDataGenerator()->create_user($data);
        $result = get_user_roles(context_tenant::instance($tenant1->id), $user5->id, false);
        $this->assertSame([], array_values(array_map($functionroleid, $result)));
        $result = get_user_roles(context_tenant::instance($tenant2->id), $user5->id, false);
        $this->assertSame([$tenantusermanagerrole->id], array_values(array_map($functionroleid, $result)));
        $result = get_user_roles(context_tenant::instance($tenant3->id), $user5->id, false);
        $this->assertSame([$tenantusermanagerrole->id], array_values(array_map($functionroleid, $result)));
        $result = get_user_roles(context_coursecat::instance($tenant1->categoryid), $user5->id, false);
        $this->assertSame([], array_values(array_map($functionroleid, $result)));
        $result = get_user_roles(context_coursecat::instance($tenant2->categoryid), $user5->id, false);
        $this->assertSame([], array_values(array_map($functionroleid, $result)));
        $result = get_user_roles(context_coursecat::instance($tenant3->categoryid), $user5->id, false);
        $this->assertSame([], array_values(array_map($functionroleid, $result)));

        $data = ['tenantdomainmanager' => $tenant1->idnumber . ', '. $tenant3->idnumber];
        $user5 = $this->getDataGenerator()->create_user($data);
        $result = get_user_roles(context_tenant::instance($tenant1->id), $user5->id, false);
        $this->assertSame([], array_values(array_map($functionroleid, $result)));
        $result = get_user_roles(context_tenant::instance($tenant2->id), $user5->id, false);
        $this->assertSame([], array_values(array_map($functionroleid, $result)));
        $result = get_user_roles(context_tenant::instance($tenant3->id), $user5->id, false);
        $this->assertSame([], array_values(array_map($functionroleid, $result)));
        $result = get_user_roles(context_coursecat::instance($tenant1->categoryid), $user5->id, false);
        $this->assertSame([$tenantdomainmanagerrole->id], array_values(array_map($functionroleid, $result)));
        $result = get_user_roles(context_coursecat::instance($tenant2->categoryid), $user5->id, false);
        $this->assertSame([], array_values(array_map($functionroleid, $result)));
        $result = get_user_roles(context_coursecat::instance($tenant3->categoryid), $user5->id, false);
        $this->assertSame([$tenantdomainmanagerrole->id], array_values(array_map($functionroleid, $result)));
    }

    public function test_set_user_participation(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant(null);
        $tenant_two = $tenant_generator->create_tenant(null);

        // Add user to two tenants.
        $tenant_generator->set_user_participation(
            $user_one->id,
            [
                $tenant_one->id,
                $tenant_two->id
            ]
        );

        // Make sure that this user is existing within two different cohorts from two different tenants.
        $sql = '
            SELECT t.id, t.name FROM "ttr_tenant" t
            INNER JOIN "ttr_cohort" c ON t.cohortid = c.id
            INNER JOIN "ttr_cohort_members" cm ON cm.cohortid = c.id
            WHERE cm.userid = :user_id
        ';

        $records = $DB->get_records_sql($sql, ['user_id' => $user_one->id]);
        $this->assertNotEmpty($records);
        $this->assertCount(2, $records);

        $this->assertArrayHasKey($tenant_one->id, $records);
        $this->assertArrayHasKey($tenant_two->id, $records);

        $first_tenant = $records[$tenant_one->id];
        $second_tenant = $records[$tenant_two->id];

        $this->assertSame($tenant_one->id, $first_tenant->id);
        $this->assertSame($tenant_two->id, $second_tenant->id);

        $this->assertSame($tenant_one->name, $first_tenant->name);
        $this->assertSame($tenant_two->name, $second_tenant->name);

        // Create new tenant and set the user to that tenant, and make sure that user will not existing within two
        // other cohorts that are from tenant_one and tenant_two.
        $tenant_three = $tenant_generator->create_tenant(null);
        $tenant_generator->set_user_participation($user_one->id, [$tenant_three->id]);

        $new_records = $DB->get_records_sql($sql, ['user_id' => $user_one->id]);
        $this->assertNotEmpty($new_records);
        $this->assertCount(1, $new_records);

        $third_tenant = reset($new_records);
        $this->assertNotEquals($tenant_one->id, $third_tenant->id);
        $this->assertNotEquals($tenant_two->id, $third_tenant->id);

        $this->assertSame($tenant_three->id, $third_tenant->id);

        $user_participant_sql = '
            SELECT t.id FROM "ttr_tenant" t
            INNER JOIN "ttr_cohort" c ON t.cohortid = c.id
            INNER JOIN "ttr_cohort_members" cm ON c.id = cm.cohortid
            WHERE cm.userid = :user_id AND t.id = :tenant_id
        ';

        // Check if the user is within tenant_one.
        $this->assertFalse(
            $DB->record_exists_sql(
                $user_participant_sql,
                [
                    'user_id' => $user_one->id,
                    'tenant_id' => $tenant_one->id
                ]
            )
        );

        // Check if the user is within tenant_two
        $this->assertFalse(
            $DB->record_exists_sql(
                $user_participant_sql,
                [
                    'user_id' => $user_one->id,
                    'tenant_id' => $tenant_two->id
                ]
            )
        );
    }

    public function test_migrate_user_to_tenant(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        // Creating two tenants in order to switch between two tenants for the user.
        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user->id, $tenant_one->id);
        $cohort_sql = '
            SELECT u.id FROM "ttr_user" u
            INNER JOIN "ttr_cohort_members" cm ON u.id = cm.userid
            WHERE cm.cohortid = :cohort_id
            AND u.id = :user_id
        ';

        // Check if the user is added to the tenant_one.
        $this->assertTrue(
            $DB->record_exists('user', ['id' => $user->id, 'tenantid' => $tenant_one->id])
        );

        $this->assertFalse(
            $DB->record_exists('user', ['id' => $user->id, 'tenantid' => $tenant_two->id])
        );

        $this->assertTrue(
            $DB->record_exists_sql(
                $cohort_sql,
                [
                    'cohort_id' => $tenant_one->cohortid,
                    'user_id' => $user->id
                ]
            )
        );

        $this->assertFalse(
            $DB->record_exists_sql(
                $cohort_sql,
                [
                    'cohort_id' => $tenant_two->cohortid,
                    'user_id' => $user->id
                ]
            )
        );

        // Migrate user to tenant_two
        $tenant_generator->migrate_user_to_tenant($user->id, $tenant_two->id);

        // Check if the user is added to tenant_two and removed from tenant_one
        $this->assertFalse(
            $DB->record_exists('user', ['id' => $user->id, 'tenantid' => $tenant_one->id])
        );

        $this->assertTrue(
            $DB->record_exists('user', ['id' => $user->id, 'tenantid' => $tenant_two->id])
        );

        $this->assertFalse(
            $DB->record_exists_sql(
                $cohort_sql,
                [
                    'cohort_id' => $tenant_one->cohortid,
                    'user_id' => $user->id
                ]
            )
        );

        $this->assertTrue(
            $DB->record_exists_sql(
                $cohort_sql,
                [
                    'cohort_id' => $tenant_two->cohortid,
                    'user_id' => $user->id
                ]
            )
        );
    }
}
