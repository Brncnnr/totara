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

use totara_tenant\local\util;
use core\record\tenant;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests covering tenant utility class.
 */
class totara_tenant_local_util_testcase extends advanced_testcase {
    public function test_is_valid_name() {
        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();
        $this->setAdminUser();

        $tenant = $generator->create_tenant(['name' => 'pokus']);
        $this->assertTrue(util::is_valid_name('pokusnik', null));
        $this->assertTrue(util::is_valid_name('Hokus Pokus', null));
        $this->assertTrue(util::is_valid_name('pokus', $tenant->id));
        $this->assertTrue(util::is_valid_name('Pokus', $tenant->id));
        // Duplicates
        $this->assertSame('Tenant with the same name already exists', util::is_valid_name('pokus', null));
        $this->assertSame('Tenant with the same name already exists', util::is_valid_name('Pokus', null));
        // Invalid
        $this->assertSame('Required', util::is_valid_name('', null));
        $this->assertSame('Required', util::is_valid_name(' ', null));
        $this->assertSame('Invalid tenant name', util::is_valid_name('Hokus ', null));
        $this->assertSame('Invalid tenant name', util::is_valid_name('Pok<script>us', null));
    }

    public function test_is_valid_idnumber() {
        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();
        $this->setAdminUser();

        $tenant = $generator->create_tenant(['idnumber' => 'pokus']);
        $this->assertTrue(util::is_valid_idnumber('pokusnik', null));
        $this->assertTrue(util::is_valid_idnumber('pokus', $tenant->id));
        // Duplicate
        $this->assertSame('Tenant with the same identifier already exists', util::is_valid_idnumber('pokus', null));
        // Invalid
        $this->assertSame('Required', util::is_valid_idnumber('', null));
        $this->assertSame('Required', util::is_valid_idnumber(' ', null));
        $this->assertSame('Invalid tenant identifier, use only lower case letters (a-z) and numbers', util::is_valid_idnumber('pokus ', null));
        $this->assertSame('Invalid tenant identifier, use only lower case letters (a-z) and numbers', util::is_valid_idnumber('Pokus', null));
        $this->assertSame('Invalid tenant identifier, use only lower case letters (a-z) and numbers', util::is_valid_idnumber('1a', null));
        $this->assertSame('Invalid tenant identifier, use only lower case letters (a-z) and numbers', util::is_valid_idnumber('a-b', null));
        $this->assertSame('Invalid tenant identifier, use only lower case letters (a-z) and numbers', util::is_valid_idnumber('a_b', null));
        $this->assertSame('Invalid tenant identifier, use only lower case letters (a-z) and numbers', util::is_valid_idnumber('šk', null));
    }

    public function test_create() {
        global $USER, $DB;
        $this->setAdminUser();

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $data = new stdClass();
        $data->name = 'Prvni tenant';
        $data->idnumber = 'tenant1';
        $data->description = 'Nejaky tenant';
        $data->descriptionformat = FORMAT_MARKDOWN;
        $data->suspended = '0';
        $data->categoryname = 'Kategorie pro prvniho tenanta';
        $data->cohortname = 'Skupina pro prvniho tenanta';

        $sink = $this->redirectEvents();
        $this->setCurrentTimeStart();
        $tenant = util::create_tenant((array)$data);
        $this->assertInstanceOf(tenant::class, $tenant);
        $this->assertTrue($DB->record_exists('tenant', ['id' => $tenant->id]));
        $this->assertSame($data->name, $tenant->name);
        $this->assertSame($data->idnumber, $tenant->idnumber);
        $this->assertSame($data->description, $tenant->description);
        $this->assertSame($data->descriptionformat, $tenant->descriptionformat);
        $this->assertSame($data->suspended, $tenant->suspended);
        $this->assertTimeCurrent($tenant->timecreated);
        $this->assertSame($USER->id, $tenant->usercreated);
        $events = $sink->get_events();
        $sink->close();
        $this->assertInstanceOf(core\event\course_category_created::class, $events[0]);
        $this->assertEquals($tenant->categoryid, $events[0]->objectid);
        $this->assertSame('course_categories', $events[0]->objecttable);
        $this->assertInstanceOf(core\event\cohort_created::class, $events[1]);
        $this->assertEquals($tenant->cohortid, $events[1]->objectid);
        $this->assertSame('cohort', $events[1]->objecttable);
        $this->assertInstanceOf(core\event\tenant_created::class, $events[2]);
        $this->assertEquals($tenant->id, $events[2]->objectid);
        $this->assertSame('tenant', $events[2]->objecttable);
        $this->assertSame(context_tenant::instance($tenant->id)->id, $events[2]->contextid);
        $this->assertSame('c', $events[2]->crud);
        $this->assertSame(0, $events[2]->edulevel);
        $this->assertSame('Tenant created', $events[2]::get_name());
        $this->assertSame("The user with id '$USER->id' created tenant with id '$tenant->id'.", $events[2]->get_description());
        $this->assertCount(3, $events);

        $coursecat = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $coursecatcontext = context_coursecat::instance($coursecat->id);
        $this->assertSame($data->categoryname, $coursecat->name);
        $this->assertSame('1', $coursecat->depth);
        $this->assertSame('0', $coursecat->parent);
        $this->assertSame('1', $coursecat->visible);
        $this->assertSame($tenant->id, $coursecatcontext->tenantid);

        $audience = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $this->assertSame($data->cohortname, $audience->name);
        $this->assertSame('1', $audience->cohorttype);
        $this->assertSame('1', $audience->visible);
        $this->assertSame('1', $audience->active);
        $this->assertSame('totara_tenant', $audience->component);
        $this->assertSame((string)$coursecatcontext->id, $audience->contextid);

        $data = new stdClass();
        $data->name = 'Druhy tenant';
        $data->idnumber = 'tenant2';
        $data->description = 'Nejaky jiny tenant';
        $data->descriptionformat = FORMAT_MARKDOWN;
        $data->suspended = '1';
        $data->categoryname = 'Kategorie pro druheho tenanta';
        $data->cohortname = 'Skupina pro druheho tenanta';

        $this->setCurrentTimeStart();
        $tenant = util::create_tenant((array)$data);
        $this->assertTrue($DB->record_exists('tenant', ['id' => $tenant->id]));
        $this->assertSame($data->name, $tenant->name);
        $this->assertSame($data->idnumber, $tenant->idnumber);
        $this->assertSame($data->description, $tenant->description);
        $this->assertSame($data->descriptionformat, $tenant->descriptionformat);
        $this->assertSame($data->suspended, $tenant->suspended);
        $this->assertTimeCurrent($tenant->timecreated);
        $this->assertSame($USER->id, $tenant->usercreated);

        $coursecat = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $coursecatcontext = context_coursecat::instance($coursecat->id);
        $this->assertSame($data->categoryname, $coursecat->name);
        $this->assertSame('', $coursecat->description);
        $this->assertSame('1', $coursecat->depth);
        $this->assertSame('0', $coursecat->parent);
        $this->assertSame('0', $coursecat->visible);
        $this->assertSame($tenant->id, $coursecatcontext->tenantid);

        $audience = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $this->assertSame($data->cohortname, $audience->name);
        $this->assertSame('', $audience->description);
        $this->assertSame('1', $audience->cohorttype);
        $this->assertSame('0', $audience->visible);
        $this->assertSame('1', $audience->active);
        $this->assertSame('totara_tenant', $audience->component);
        $this->assertSame((string)$coursecatcontext->id, $audience->contextid);
    }

    public function test_update() {
        global $USER, $DB;
        $this->setAdminUser();

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $data = new stdClass();
        $data->name = 'Prvni tenant';
        $data->idnumber = 'tenant1';
        $data->description = 'Nejaky tenant';
        $data->descriptionformat = FORMAT_MARKDOWN;
        $data->suspended = '1';
        $data->categoryname = 'Kategorie pro prvniho tenanta';
        $data->cohortname = 'Skupina pro prvniho tenanta';
        $oldtenant = util::create_tenant((array)$data);

        $coursecat = $DB->get_record('course_categories', ['id' => $oldtenant->categoryid], '*', MUST_EXIST);
        $this->assertSame('0', $coursecat->visible);
        $course = $this->getDataGenerator()->create_course(['category' => $oldtenant->categoryid, 'visible' => 1]);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $sink = $this->redirectEvents();
        $data = new stdClass();
        $data->id = $oldtenant->id;
        $data->name = 'First tenant';
        $data->idnumber = 't1';
        $data->description = 'Some tennant';
        $data->descriptionformat = FORMAT_HTML;
        $data->suspended = '0';
        $data->categoryname = 'First tenant category';
        $data->cohortname = 'First tenant audience';
        $tenant = util::update_tenant((array)$data);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(3, $events);

        $this->assertInstanceOf(tenant::class, $tenant);
        $this->assertSame($data->name, $tenant->name);
        $this->assertSame($data->idnumber, $tenant->idnumber);
        $this->assertSame($data->description, $tenant->description);
        $this->assertSame($data->descriptionformat, $tenant->descriptionformat);
        $this->assertSame($data->suspended, $tenant->suspended);
        $this->assertSame($oldtenant->timecreated, $tenant->timecreated);
        $this->assertSame($oldtenant->usercreated, $tenant->usercreated);
        $this->assertInstanceOf(core\event\tenant_updated::class, $events[2]);
        $this->assertEquals($tenant->id, $events[2]->objectid);
        $this->assertSame('tenant', $events[2]->objecttable);
        $this->assertSame(context_tenant::instance($tenant->id)->id, $events[2]->contextid);
        $this->assertSame('u', $events[2]->crud);
        $this->assertSame(0, $events[2]->edulevel);
        $this->assertSame('Tenant updated', $events[2]::get_name());
        $this->assertSame("The user with id '$USER->id' updated tenant with id '$tenant->id'.", $events[2]->get_description());

        $coursecat = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $coursecatcontext = context_coursecat::instance($coursecat->id);
        $this->assertSame($data->categoryname, $coursecat->name);
        $this->assertSame('1', $coursecat->depth);
        $this->assertSame('0', $coursecat->parent);
        $this->assertSame('1', $coursecat->visible);
        $this->assertSame($tenant->id, $coursecatcontext->tenantid);
        $this->assertInstanceOf(core\event\course_category_updated::class, $events[0]);
        $this->assertEquals($tenant->categoryid, $events[0]->objectid);
        $this->assertSame('course_categories', $events[0]->objecttable);

        $audience = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $this->assertSame($data->cohortname, $audience->name);
        $this->assertSame('1', $audience->cohorttype);
        $this->assertSame('1', $audience->visible);
        $this->assertSame('1', $audience->active);
        $this->assertSame('totara_tenant', $audience->component);
        $this->assertSame((string)$coursecatcontext->id, $audience->contextid);
        $this->assertInstanceOf(core\event\cohort_updated::class, $events[1]);
        $this->assertEquals($tenant->cohortid, $events[1]->objectid);
        $this->assertSame('cohort', $events[1]->objecttable);

        $course = $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);
        $this->assertSame('1', $course->visible);

        $data = new stdClass();
        $data->id = $oldtenant->id;
        $data->name = 'First tenant';
        $data->idnumber = 't1';
        $data->description = 'Some tennant';
        $data->descriptionformat = FORMAT_HTML;
        $data->suspended = '1';
        $data->categoryname = 'First tenant category';
        $data->cohortname = 'First tenant audience';
        $tenant = util::update_tenant((array)$data);

        $this->assertSame($data->name, $tenant->name);
        $this->assertSame($data->idnumber, $tenant->idnumber);
        $this->assertSame($data->description, $tenant->description);
        $this->assertSame($data->descriptionformat, $tenant->descriptionformat);
        $this->assertSame($data->suspended, $tenant->suspended);
        $this->assertSame($oldtenant->timecreated, $tenant->timecreated);
        $this->assertSame($oldtenant->usercreated, $tenant->usercreated);

        $coursecat = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $coursecatcontext = context_coursecat::instance($coursecat->id);
        $this->assertSame($data->categoryname, $coursecat->name);
        $this->assertSame('1', $coursecat->depth);
        $this->assertSame('0', $coursecat->parent);
        $this->assertSame('0', $coursecat->visible);
        $this->assertSame($tenant->id, $coursecatcontext->tenantid);

        $audience = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $this->assertSame($data->cohortname, $audience->name);
        $this->assertSame('1', $audience->cohorttype);
        $this->assertSame('0', $audience->visible);
        $this->assertSame('1', $audience->active);
        $this->assertSame('totara_tenant', $audience->component);
        $this->assertSame((string)$coursecatcontext->id, $audience->contextid);

        $course = $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);
        $this->assertSame('0', $course->visible);

        // Make sure the category visibility cannot get out of sync.
        $updatecategory = \coursecat::get($tenant->categoryid, MUST_EXIST, true);
        $updatecategory->update(['visible' => 1]);
        $this->assertSame('0', $updatecategory->visible);
        $this->assertFalse($updatecategory->can_change_visibility());

        $data = new stdClass();
        $data->id = $oldtenant->id;
        $data->suspended = '0';
        $tenant = util::update_tenant((array)$data);

        $this->assertSame($data->suspended, $tenant->suspended);

        $coursecat = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $this->assertSame('1', $coursecat->visible);

        $audience = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $this->assertSame('1', $audience->visible);

        $course = $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);
        $this->assertSame('1', $course->visible);

        // Make sure the category visibility cannot get out of sync.
        $updatecategory = \coursecat::get($tenant->categoryid, MUST_EXIST, true);
        $updatecategory->update(['visible' => 0]);
        $this->assertSame('1', $updatecategory->visible);
        $this->assertFalse($updatecategory->can_change_visibility());
    }

    public function test_delete_tenant_delete() {
        global $DB, $USER;

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $this->setAdminUser();

        $tenant = $generator->create_tenant();
        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantparticipant' => $tenant->idnumber]);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id]));
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user2->id]));

        $sink = $this->redirectEvents();
        util::delete_tenant($tenant->id, util::DELETE_TENANT_USER_DELETE);
        $this->assertFalse($DB->record_exists('tenant', ['id' => $tenant->id]));
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(3, $events);
        $this->assertInstanceOf(core\event\user_deleted::class, $events[0]);
        $this->assertEquals($user->id, $events[0]->objectid);
        $this->assertInstanceOf(core\event\cohort_member_removed::class, $events[1]);
        $this->assertEquals($user2->id, $events[1]->relateduserid);
        $this->assertInstanceOf(core\event\tenant_deleted::class, $events[2]);
        $this->assertEquals($tenant->id, $events[2]->objectid);
        $this->assertSame('tenant', $events[2]->objecttable);
        $this->assertSame('d', $events[2]->crud);
        $this->assertSame(0, $events[2]->edulevel);
        $this->assertSame('Tenant deleted', $events[2]::get_name());
        $this->assertSame("The user with id '$USER->id' deleted tenant with id '$tenant->id'.", $events[2]->get_description());

        $coursecat = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $coursecatcontext = context_coursecat::instance($coursecat->id);
        $this->assertNull($coursecatcontext->tenantid);
        $this->assertSame('0', $coursecat->visible);

        $audience = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $this->assertSame('1', $audience->cohorttype);
        $this->assertSame('', $audience->component);
        $this->assertSame(0, $DB->count_records('cohort_members', ['cohortid' => $tenant->cohortid]));

        $user = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        $this->assertSame('1', $user->deleted);
        $this->assertSame('0', $user->suspended);
        $this->assertNull($user->tenantid);
        $this->assertFalse($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id]));
    }

    public function test_delete_tenant_migrate() {
        global $DB;

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $this->setAdminUser();

        $tenant = $generator->create_tenant();
        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantparticipant' => $tenant->idnumber]);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id]));
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user2->id]));

        util::delete_tenant($tenant->id, util::DELETE_TENANT_USER_MIGRATE);
        $this->assertFalse($DB->record_exists('tenant', ['id' => $tenant->id]));

        $coursecat = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $coursecatcontext = context_coursecat::instance($coursecat->id);
        $this->assertNull($coursecatcontext->tenantid);
        $this->assertSame('0', $coursecat->visible);

        $audience = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $this->assertSame('1', $audience->cohorttype);
        $this->assertSame('', $audience->component);
        $this->assertSame(0, $DB->count_records('cohort_members', ['cohortid' => $tenant->cohortid]));

        $user = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        $usercontext = context_user::instance($user->id);
        $this->assertSame('0', $user->deleted);
        $this->assertSame('0', $user->suspended);
        $this->assertNull($user->tenantid);
        $this->assertNull($usercontext->tenantid);
    }

    public function test_delete_tenant_suspend() {
        global $DB;

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $this->setAdminUser();

        $tenant = $generator->create_tenant();
        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantparticipant' => $tenant->idnumber]);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id]));
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user2->id]));

        util::delete_tenant($tenant->id, util::DELETE_TENANT_USER_SUSPEND);
        $this->assertFalse($DB->record_exists('tenant', ['id' => $tenant->id]));

        $coursecat = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $coursecatcontext = context_coursecat::instance($coursecat->id);
        $this->assertNull($coursecatcontext->tenantid);
        $this->assertSame('0', $coursecat->visible);

        $audience = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $this->assertSame('1', $audience->cohorttype);
        $this->assertSame('', $audience->component);
        $this->assertSame(0, $DB->count_records('cohort_members', ['cohortid' => $tenant->cohortid]));

        $user = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        $usercontext = context_user::instance($user->id);
        $this->assertSame('0', $user->deleted);
        $this->assertSame('1', $user->suspended);
        $this->assertNull($user->tenantid);
        $this->assertNull($usercontext->tenantid);
    }

    public function test_add_other_participant() {
        global $DB;

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $this->setAdminUser();

        $tenant = $generator->create_tenant();
        $user = $this->getDataGenerator()->create_user();

        util::add_other_participant($tenant->id, $user->id);
        $this->assertNull($DB->get_field('user', 'tenantid', ['id' => $user->id]));
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id]));

        // Repeated adding should not cause problems.
        util::add_other_participant($tenant->id, $user->id);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id]));

        // Make sure tenant members cannot be added as extra participants.
        $tenantuser = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $tenantuser->id]));
        try {
            util::add_other_participant($tenant->id, $tenantuser->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Only non-tenant users may be tenant participants', $ex->getMessage());
        }
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $tenantuser->id]));
    }

    public function test_remove_other_participant() {
        global $DB;

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $this->setAdminUser();

        $tenant = $generator->create_tenant();
        $user = $this->getDataGenerator()->create_user();

        util::add_other_participant($tenant->id, $user->id);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id]));

        util::remove_other_participant($tenant->id, $user->id);
        $this->assertFalse($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id]));

        // Repeated removing should not cause problems.
        util::remove_other_participant($tenant->id, $user->id);
        $this->assertFalse($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id]));

        // Make sure tenant members cannot be removed from participants.
        $tenantuser = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $tenantuser->id]));
        try {
            util::remove_other_participant($tenant->id, $tenantuser->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Only non-tenant users may be tenant participants', $ex->getMessage());
        }
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $tenantuser->id]));
    }

    public function test_get_user_participation() {
        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $this->setAdminUser();

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant3->id]);
        $user4 = $this->getDataGenerator()->create_user();

        util::add_other_participant($tenant1->id, $user1->id);
        util::add_other_participant($tenant3->id, $user1->id);
        util::add_other_participant($tenant2->id, $user2->id);

        $result = util::get_user_participation($user1->id);
        $this->assertSame([$tenant1->id => $tenant1->id, $tenant3->id => $tenant3->id], $result);

        $result = util::get_user_participation($user2->id);
        $this->assertSame([$tenant2->id => $tenant2->id], $result);

        $result = util::get_user_participation($user3->id);
        $this->assertSame([$tenant3->id => $tenant3->id], $result);

        $result = util::get_user_participation($user4->id);
        $this->assertSame([], $result);
    }

    public function test_set_user_participation() {
        global $DB;

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $this->setAdminUser();

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant3->id]);
        $user4 = $this->getDataGenerator()->create_user(['tenantid' => $tenant3->id]);

        util::set_user_participation($user1->id, [$tenant1->id, $tenant3->id]);
        $result = util::get_user_participation($user1->id);
        $this->assertSame([$tenant1->id => $tenant1->id, $tenant3->id => $tenant3->id], $result);

        util::set_user_participation($user1->id, [$tenant2->id]);
        $result = util::get_user_participation($user1->id);
        $this->assertSame([$tenant2->id => $tenant2->id], $result);

        util::set_user_participation($user1->id, []);
        $result = util::get_user_participation($user1->id);
        $this->assertSame([], $result);
        $this->assertNull($DB->get_field('user', 'tenantid', ['id' => $user1->id]));

        util::set_user_participation($user3->id, [$tenant2->id]);
        $result = util::get_user_participation($user3->id);
        $this->assertSame([$tenant2->id => $tenant2->id], $result);
        $this->assertNull($DB->get_field('user', 'tenantid', ['id' => $user2->id]));

        util::set_user_participation($user4->id, []);
        $result = util::get_user_participation($user4->id);
        $this->assertSame([], $result);
        $this->assertNull($DB->get_field('user', 'tenantid', ['id' => $user4->id]));
    }

    public function test_migrate_user_to_tenant() {
        global $DB;

        $generator = \totara_tenant\testing\generator::instance();
        $generator->enable_tenants();

        $this->setAdminUser();

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        util::migrate_user_to_tenant($user1->id, $tenant1->id);
        $result = util::get_user_participation($user1->id);
        $this->assertSame([$tenant1->id => $tenant1->id], $result);
        $this->assertSame($tenant1->id, $DB->get_field('user', 'tenantid', ['id' => $user1->id]));
        $context = context_user::instance($user1->id);
        $this->assertSame($tenant1->id, $context->tenantid);

        util::migrate_user_to_tenant($user1->id, $tenant2->id);
        $result = util::get_user_participation($user1->id);
        $this->assertSame([$tenant2->id => $tenant2->id], $result);
        $this->assertSame($tenant2->id, $DB->get_field('user', 'tenantid', ['id' => $user1->id]));
        $context = context_user::instance($user1->id);
        $this->assertSame($tenant2->id, $context->tenantid);

        util::set_user_participation($user2->id, [$tenant1->id, $tenant3->id]);
        util::migrate_user_to_tenant($user2->id, $tenant2->id);
        $result = util::get_user_participation($user2->id);
        $this->assertSame([$tenant2->id => $tenant2->id], $result);
        $this->assertSame($tenant2->id, $DB->get_field('user', 'tenantid', ['id' => $user2->id]));
        $context = context_user::instance($user2->id);
        $this->assertSame($tenant2->id, $context->tenantid);

        $admin = get_admin();
        try {
            util::migrate_user_to_tenant($admin->id, $tenant1->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Admins cannot be migrated to tenant members', $e->getMessage());
        }
    }

    public function test_user_username_exists() {
        global $DB;
        $generator = $this->getDataGenerator();

        $generator->create_user(['username' => 'User1', 'deleted' => 0, 'confirmed' => 1]);
        $generator->create_user(['username' => 'user2', 'deleted' => 1, 'confirmed' => 1]);
        $generator->create_user(['username' => 'user3', 'deleted' => 0, 'confirmed' => 0]);

        $this->assertTrue(util::user_username_exists('User1'));
        $this->assertTrue(util::user_username_exists('uSER1'));

        if ($DB->get_dbfamily() != 'mysql') {
            // Accent insensitive MySQL will fail here, that is to be expected.
            $this->assertFalse(util::user_username_exists('Úser1'));
        }

        $this->assertFalse(util::user_username_exists('user2'));
        $this->assertTrue(util::user_username_exists('user3'));
    }

    public function test_validate_user_username() {
        $generator = $this->getDataGenerator();

        $generator->create_user(['username' => 'User1', 'deleted' => 0, 'confirmed' => 1]);
        $generator->create_user(['username' => 'user2', 'deleted' => 1, 'confirmed' => 1]);
        $generator->create_user(['username' => 'user3', 'deleted' => 0, 'confirmed' => 0]);

        $this->assertSame('Missing username', util::validate_user_username(''));
        $this->assertSame('Missing username', util::validate_user_username(' '));
        $this->assertSame('Missing username', util::validate_user_username('0'));

        $this->assertSame('This username already exists, choose another', util::validate_user_username('User1'));
        $this->assertSame('This username already exists, choose another', util::validate_user_username('user1'));

        $this->assertSame('The username can only contain alphanumeric lowercase characters (letters and numbers), underscore (_), hyphen (-), period (.) or at symbol (@).', util::validate_user_username('user 1'));
        $this->assertSame('Only lowercase letters allowed in username', util::validate_user_username('User'));

        $this->assertSame(null, util::validate_user_username('user'));
    }

    public function test_user_email_exists() {
        global $DB;
        $generator = $this->getDataGenerator();

        $generator->create_user(['email' => 'User1@example.com', 'deleted' => 0, 'confirmed' => 1]);
        $generator->create_user(['email' => 'user2@example.com', 'deleted' => 1, 'confirmed' => 1]);
        $generator->create_user(['email' => 'user3@example.com', 'deleted' => 0, 'confirmed' => 0]);

        $this->assertTrue(util::user_email_exists('User1@example.com'));
        $this->assertTrue(util::user_email_exists('uSER1@example.com'));

        if ($DB->get_dbfamily() != 'mysql') {
            // Accent insensitive MySQL will fail here, that is to be expected.
            $this->assertFalse(util::user_email_exists('Úser1@example.com'));
        }

        $this->assertFalse(util::user_email_exists('user2@example.com'));

        $this->assertTrue(util::user_email_exists('user3@example.com'));
    }

    public function test_validate_user_email() {
        global $CFG;

        $generator = $this->getDataGenerator();

        $generator->create_user([
            'email' => 'User1@example.com', 'deleted' => 0, 'confirmed' => 1
            ]);
        $generator->create_user([
            'email' => 'user2@example.com', 'deleted' => 1, 'confirmed' => 1
            ]);
        $generator->create_user([
            'email' => 'user3@example.com', 'deleted' => 0, 'confirmed' => 0
            ]);

        $this->assertSame('Missing email address', util::validate_user_email(''));
        $this->assertSame('Missing email address', util::validate_user_email(' '));
        $this->assertSame('Missing email address', util::validate_user_email('0'));

        $this->assertSame('This email address is already registered.', util::validate_user_email('User1@example.com'));
        $this->assertSame('This email address is already registered.', util::validate_user_email('user1@Example.com'));

        $this->assertSame('Invalid email address', util::validate_user_email('user example.com'));

        $this->assertSame(null, util::validate_user_email('user@example.com'));

        $CFG->allowaccountssameemail = 1;

        $generator->create_user([
            'email' => 'unique.email@example.com',
        ]);
        $generator->create_user([
            'email' => 'duplicate.email@example.com',
        ]);
        $generator->create_user([
            'email' => 'DupLiCATE.email@example.com',
        ]);

        $this->assertSame(null, util::validate_user_email('duplicate.email@example.com'));
        $this->assertSame(null, util::validate_user_email('DUPLICATE.EMAIL@EXAMPLE.COM'));
        $this->assertSame(null, util::validate_user_email('unique.email@example.com'));
        $this->assertSame('Invalid email address', util::validate_user_email('nonexistent! email@example.com'));
        $this->assertSame('Missing email address', util::validate_user_email(''));
        $this->assertSame('Missing email address', util::validate_user_email('   '));
    }

    public function test_user_idnumber_exists() {
        global $DB;
        $generator = $this->getDataGenerator();

        $generator->create_user(['idnumber' => 'User1', 'deleted' => 0, 'confirmed' => 1]);
        $generator->create_user(['idnumber' => 'user2', 'deleted' => 1, 'confirmed' => 1]);
        $generator->create_user(['idnumber' => 'user3', 'deleted' => 0, 'confirmed' => 0]);
        $generator->create_user(['idnumber' => '', 'deleted' => 0, 'confirmed' => 1]);

        $this->assertTrue(util::user_idnumber_exists('User1'));
        $this->assertTrue(util::user_idnumber_exists('uSER1'));

        if ($DB->get_dbfamily() != 'mysql') {
            // Accent insensitive MySQL will fail here, that is to be expected.
            $this->assertFalse(util::user_idnumber_exists('Úser1'));
        }

        $this->assertFalse(util::user_idnumber_exists('user2'));

        $this->assertTrue(util::user_idnumber_exists('user3'));

        $this->assertFalse(util::user_idnumber_exists(''));
    }

    public function test_validate_user_idnumber() {
        $generator = $this->getDataGenerator();

        $generator->create_user(['idnumber' => 'User1', 'deleted' => 0, 'confirmed' => 1]);
        $generator->create_user(['idnumber' => 'user2', 'deleted' => 1, 'confirmed' => 1]);
        $generator->create_user(['idnumber' => 'user3', 'deleted' => 0, 'confirmed' => 0]);
        $generator->create_user(['idnumber' => '', 'deleted' => 0, 'confirmed' => 1]);

        $this->assertSame(null, util::validate_user_idnumber(''));

        $this->assertSame('ID number is invalid', util::validate_user_idnumber(' '));
        $this->assertSame('ID number is invalid', util::validate_user_idnumber('0'));
        $this->assertSame('ID number is invalid', util::validate_user_idnumber('abc '));
        $this->assertSame('ID number is invalid', util::validate_user_idnumber(' abc'));

        $this->assertSame('This ID number is already in use', util::validate_user_idnumber('User1'));
        $this->assertSame('This ID number is already in use', util::validate_user_idnumber('user1'));

        $this->assertSame(null, util::validate_user_idnumber('user'));
        $this->assertSame(null, util::validate_user_idnumber('úser'));
    }

    public function test_get_csv_required_columns() {
        $required = util::get_csv_required_columns(true);
        $this->assertIsArray($required);
        $this->assertContains('username', $required);
        $this->assertContains('email', $required);
        $this->assertContains('firstname', $required);
        $this->assertContains('lastname', $required);
        $this->assertContains('password', $required);
        $this->assertNotContainsEquals('middlename', $required);

        $required = util::get_csv_required_columns(false);
        $this->assertIsArray($required);
        $this->assertContains('username', $required);
        $this->assertContains('email', $required);
        $this->assertContains('firstname', $required);
        $this->assertContains('lastname', $required);
        $this->assertNotContainsEquals('password', $required);
        $this->assertNotContainsEquals('middlename', $required);
    }

    public function test_get_csv_optional_columns() {
        $optional = util::get_csv_optional_columns(true);
        $this->assertIsArray($optional);
        $this->assertNotContainsEquals('username', $optional);
        $this->assertNotContainsEquals('email', $optional);
        $this->assertNotContainsEquals('firstname', $optional);
        $this->assertNotContainsEquals('lastname', $optional);
        $this->assertNotContainsEquals('password', $optional);
        $this->assertContains('middlename', $optional);

        $optional = util::get_csv_optional_columns(false);
        $this->assertIsArray($optional);
        $this->assertNotContainsEquals('username', $optional);
        $this->assertNotContainsEquals('email', $optional);
        $this->assertNotContainsEquals('firstname', $optional);
        $this->assertNotContainsEquals('lastname', $optional);
        $this->assertContains('password', $optional);
        $this->assertContains('middlename', $optional);
    }

    public function test_validate_users_csv_structure() {
        $generator = $this->getDataGenerator();

        $generator->create_user(['username' => 'User1', 'email' => 'user1@example.com', 'idnumber' => 'iduser1', 'deleted' => 0, 'confirmed' => 1]);
        $generator->create_user(['username' => 'user2', 'email' => 'user2@example.com', 'idnumber' => 'iduser2', 'deleted' => 1, 'confirmed' => 1]);
        $generator->create_user(['username' => 'user3', 'email' => 'user3@example.com', 'idnumber' => 'iduser3', 'deleted' => 0, 'confirmed' => 0]);

        $content = <<<OEF
username,email,firstname,lastname
use4,user4@example.com,Ctvrty,Uzivatel
user1,user1@example.com,First User
OEF;
        $expected = [
            'delimiter' => ',',
            'delimitername' => 'comma',
            'columns' => ['username', 'email', 'firstname', 'lastname'],
            'error' => null,
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', false);
        $this->assertSame($expected, $result);

        $expected = [
            'delimiter' => ',',
            'delimitername' => 'comma',
            'columns' => ['username', 'email', 'firstname', 'lastname'],
            'error' => 'Following columns must be included in the CSV file: password',
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', true);
        $this->assertSame($expected, $result);

        $content = <<<OEF
 username ;email; firstname;lastname ;password
 use4 ;user4@example.com;Ctvrty;Uzivatel;Pokus123!
user1;user1@example.com;First User;Pokus123!
OEF;
        $expected = [
            'delimiter' => ';',
            'delimitername' => 'semicolon',
            'columns' => ['username', 'email', 'firstname', 'lastname', 'password'],
            'error' => null,
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', false);
        $this->assertSame($expected, $result);

        $content = <<<OEF
username,email,firstname,lastname,password
use4,user4@example.com,Ctvrty,Uzivatel,Pokus123!
user1,user1@example.com,First User,Pokus123!
OEF;
        $expected = [
            'delimiter' => ',',
            'delimitername' => 'comma',
            'columns' => ['username', 'email', 'firstname', 'lastname', 'password'],
            'error' => null,
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', true);
        $this->assertSame($expected, $result);

        $content = <<<OEF
username,email,firstname,lastname,password,middlename,description
use4,user4@example.com,Ctvrty,Uzivatel,Pokus123!,,Nothing interesting
user1,user1@example.com,First User,Pokus123!,John,
OEF;
        $expected = [
            'delimiter' => ',',
            'delimitername' => 'comma',
            'columns' => ['username', 'email', 'firstname', 'lastname', 'password', 'middlename', 'description'],
            'error' => null,
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', true);
        $this->assertSame($expected, $result);

        $content = <<<OEF
username,email,firstname,lastname,password,middlename,description,xyz,def
use4,user4@example.com,Ctvrty,Uzivatel,Pokus123!,,Nothing interesting,ff,
user1,user1@example.com,First User,Pokus123!,John,,ii,
OEF;
        $expected = [
            'delimiter' => ',',
            'delimitername' => 'comma',
            'columns' => ['username', 'email', 'firstname', 'lastname', 'password', 'middlename', 'description', 'xyz', 'def'],
            'error' => 'Following unknown columns cannot be present in the CSV file: xyz, def',
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', true);
        $this->assertSame($expected, $result);

        $content = <<<OEF
abc,def,xyz
,,
,,
OEF;
        $expected = [
            'delimiter' => ',',
            'delimitername' => 'comma',
            'columns' => ['abc', 'def', 'xyz'],
            'error' => 'Following columns must be included in the CSV file: username, email, firstname, lastname, password',
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', true);
        $this->assertSame($expected, $result);

        $expected = [
            'delimiter' => ',',
            'delimitername' => 'comma',
            'columns' => ['abc', 'def', 'xyz'],
            'error' => 'Following columns must be included in the CSV file: username, email, firstname, lastname',
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', false);
        $this->assertSame($expected, $result);

        $content = <<<OEF
def,xyz
,
,
OEF;
        $expected = [
            'delimiter' => ',',
            'delimitername' => 'comma',
            'columns' => ['def', 'xyz'],
            'error' => 'Following columns must be included in the CSV file: username, email, firstname, lastname, password',
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', true);
        $this->assertSame($expected, $result);

        $expected = [
            'delimiter' => ',',
            'delimitername' => 'comma',
            'columns' => ['def', 'xyz'],
            'error' => 'Following columns must be included in the CSV file: username, email, firstname, lastname',
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', false);
        $this->assertSame($expected, $result);

        $content = <<<OEF
defxyz

OEF;
        $expected = [
            'delimiter' => null,
            'delimitername' => null,
            'columns' => null,
            'error' => 'There is something wrong with the format of the CSV file. Please check that it includes column names.',
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', true);
        $this->assertSame($expected, $result);

        $expected = [
            'delimiter' => null,
            'delimitername' => null,
            'columns' => null,
            'error' => 'There is something wrong with the format of the CSV file. Please check that it includes column names.',
        ];
        $result = util::validate_users_csv_structure($content, 'UTF-8', false);
        $this->assertSame($expected, $result);
    }

    public function test_validate_users_csv_row() {
        $generator = $this->getDataGenerator();

        $generator->create_user(['username' => 'User1', 'email' => 'user1@example.com', 'idnumber' => 'iduser1', 'deleted' => 0, 'confirmed' => 1]);
        $generator->create_user(['username' => 'user2', 'email' => 'user2@example.com', 'idnumber' => 'iduser2', 'deleted' => 1, 'confirmed' => 1]);
        $generator->create_user(['username' => 'user3', 'email' => 'user3@example.com', 'idnumber' => 'iduser3', 'deleted' => 0, 'confirmed' => 0]);

        // Check for uniqueness in profile fields.
        $profielfieldsdata = [];

        // Create a textinput profile field that requires values to be unique.
        $user_info_field_textinput_unique_data = array(
            'id' => 5, 'shortname' => 'clave', 'name' => 'clave', 'datatype' => 'text', 'description' => '', 'categoryid' => 695000,
            'sortorder' => 1, 'required' => 0, 'locked' => 0, 'visible' => 1, 'forceunique' => 1, 'signup' => 0, 'defaultdata' => '',
            'param1' => 30, 'param2' => 2048, 'param3' => 0, 'param4' => '', 'param5' => '',
        );
        $this->loadDataSet($this->createArrayDataset(array(
            'user_info_field' => array(
                $user_info_field_textinput_unique_data
            )
        )));

        $row = [
            'username' => 'user',
            'email' => 'user@example.com',
            'firstname' => 'Prvni',
            'lastname' => 'Uzivatel',
            'password' => 'Pass123443!',
        ];
        $expected = [];
        $result = util::validate_users_csv_row($row, true, $profielfieldsdata);
        $this->assertSame($expected, $result);

        $row = [
            'username' => 'user',
            'email' => 'user@example.com',
            'firstname' => 'Prvni',
            'lastname' => 'Uzivatel',
            'lang' => 'en',
        ];
        $expected = [];
        $result = util::validate_users_csv_row($row, false, $profielfieldsdata);
        $this->assertSame($expected, $result);

        $row = [
            'username' => 'user1',
            'email' => 'user1@example.com',
            'firstname' => '',
            'lastname' => 'Uzivatel',
            'idnumber' => 'iduser1',
            'lang' => 'xx',
        ];
        $expected = [
            'This username already exists, choose another',
            'This email address is already registered.',
            'This ID number is already in use',
            'Field "firstname" is missing',
            'Field "password" is missing',
            'Cannot find "xx" language pack!',
        ];
        $result = util::validate_users_csv_row($row, true, $profielfieldsdata);
        $this->assertSame($expected, $result);

        $row = [
            'username' => 'user',
            'email' => 'user@example.com',
            'firstname' => 'Prvni',
            'lastname' => 'Uzivatel',
            'lang' => 'en',
            'password' => 'Pass123443!',
            'profile_field_clave' => '123'
        ];
        $expected = [];
        $result = util::validate_users_csv_row($row, false, $profielfieldsdata);
        $this->assertSame($expected, $result);

        $row = [
            'username' => 'userx',
            'email' => 'userx@example.com',
            'firstname' => 'Prvnix',
            'lastname' => 'Uzivatelx',
            'lang' => 'en',
            'password' => 'Pass123443!',
            'profile_field_clave' => '123'
        ];
        $expected = [
            'This value has already been used in file. (profile_field_clave)',
        ];
        $result = util::validate_users_csv_row($row, true, $profielfieldsdata);
        $this->assertSame($expected, $result);
    }
}
