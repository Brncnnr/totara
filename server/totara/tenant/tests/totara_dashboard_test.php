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
 * Tests covering multitenancy changes in totara_dashboard class.
 */
class totara_tenant_totara_dashboard_testcase extends advanced_testcase {
    public function test_create() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/dashboard/lib.php');

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();
        $this->assertCount(2, $DB->get_records('totara_dashboard'));

        $newdash = new totara_dashboard(0);
        $newdash->tenantid = $tenant->id;
        $newdash->name = 'Test dashboard';
        $newdash->published = totara_dashboard::AUDIENCE;
        $newdash->locked = 0;
        $newdash->set_cohorts([$tenant->cohortid]);

        $newdash->save();

        $record = $DB->get_record('totara_dashboard', ['id' => $newdash->get_id()], '*', MUST_EXIST);
        $this->assertSame($tenant->id, $record->tenantid);
        $this->assertSame('Test dashboard', $record->name);
        $this->assertEquals(totara_dashboard::AUDIENCE, $record->published);
        $this->assertEquals(0, $record->locked);
        $this->assertEquals(2, $record->sortorder);

        $newdash = new totara_dashboard($record->id);
        $this->assertSame($record->tenantid, $newdash->tenantid);
        $this->assertSame($record->name, $newdash->name);
        $this->assertSame($record->published, $newdash->published);
        $this->assertSame($record->locked, $newdash->locked);
        $this->assertSame($record->sortorder, $newdash->sortorder);
        $this->assertTrue($newdash->is_last());
        $this->assertSame([$tenant->cohortid], $newdash->get_cohorts());
    }

    public function test_clone_dashboard() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/dashboard/lib.php');

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $this->assertCount(3, $DB->get_records('totara_dashboard'));

        $defaultdash = $DB->get_record('totara_dashboard', ['sortorder' => 0], '*', MUST_EXIST);
        $this->assertNull($defaultdash->tenantid);
        $tenantdash1 = $DB->get_record('totara_dashboard', ['sortorder' => 1], '*', MUST_EXIST);
        $this->assertSame($tenant1->id, $tenantdash1->tenantid);
        $tenantdash2 = $DB->get_record('totara_dashboard', ['sortorder' => 2], '*', MUST_EXIST);
        $this->assertSame($tenant2->id, $tenantdash2->tenantid);

        $cloned1 = (new totara_dashboard($defaultdash->id))->clone_dashboard('Test dashboard', $tenant1->id);
        $cloned1 = $DB->get_record('totara_dashboard', ['id' => $cloned1], '*', MUST_EXIST);
        $this->assertSame($tenant1->id, $cloned1->tenantid);
        $this->assertSame('Test dashboard', $cloned1->name);
        $this->assertEquals(totara_dashboard::AUDIENCE, $cloned1->published);
        $this->assertEquals(0, $cloned1->locked);
        $this->assertEquals(3, $cloned1->sortorder);
        $cloned1dash = new totara_dashboard($cloned1->id);
        $this->assertSame([$tenant1->cohortid], $cloned1dash->get_cohorts());

        $cloned2 = (new totara_dashboard($tenantdash1->id))->clone_dashboard('Another dashboard', $tenant2->id);
        $cloned2 = $DB->get_record('totara_dashboard', ['id' => $cloned2], '*', MUST_EXIST);
        $this->assertSame($tenant2->id, $cloned2->tenantid);
        $this->assertSame('Another dashboard', $cloned2->name);
        $this->assertEquals(totara_dashboard::AUDIENCE, $cloned2->published);
        $this->assertEquals(0, $cloned2->locked);
        $this->assertEquals(4, $cloned2->sortorder);
        $cloned2dash = new totara_dashboard($cloned2->id);
        $this->assertSame([$tenant2->cohortid], $cloned2dash->get_cohorts());

        $cloned3 = (new totara_dashboard($tenantdash1->id))->clone_dashboard('X dashboard');
        $cloned3 = $DB->get_record('totara_dashboard', ['id' => $cloned3], '*', MUST_EXIST);
        $this->assertSame($tenant1->id, $cloned3->tenantid);
        $this->assertSame('X dashboard', $cloned3->name);
        $this->assertEquals(totara_dashboard::AUDIENCE, $cloned3->published);
        $this->assertEquals(0, $cloned3->locked);
        $this->assertEquals(5, $cloned3->sortorder);
        $cloned3dash = new totara_dashboard($cloned3->id);
        $this->assertSame([$tenant1->cohortid], $cloned3dash->get_cohorts());

        $cloned4 = (new totara_dashboard($defaultdash->id))->clone_dashboard('Y dashboard');
        $cloned4 = $DB->get_record('totara_dashboard', ['id' => $cloned4], '*', MUST_EXIST);
        $this->assertNull($cloned4->tenantid);
        $this->assertSame('Y dashboard', $cloned4->name);
        $this->assertEquals(totara_dashboard::ALL, $cloned4->published);
        $this->assertEquals(0, $cloned4->locked);
        $this->assertEquals(6, $cloned4->sortorder);
        $cloned4dash = new totara_dashboard($cloned4->id);
        $this->assertSame([], $cloned4dash->get_cohorts());
    }

    public function test_get_user_dashboards() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/dashboard/lib.php');

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant3 = $tenantgenerator->create_tenant(['suspended' => 1]);
        $this->assertCount(4, $DB->get_records('totara_dashboard'));

        $defaultdash = $DB->get_record('totara_dashboard', ['sortorder' => 0], '*', MUST_EXIST);
        $this->assertNull($defaultdash->tenantid);
        $tenantdash1 = $DB->get_record('totara_dashboard', ['sortorder' => 1], '*', MUST_EXIST);
        $this->assertSame($tenant1->id, $tenantdash1->tenantid);
        $tenantdash2 = $DB->get_record('totara_dashboard', ['sortorder' => 2], '*', MUST_EXIST);
        $this->assertSame($tenant2->id, $tenantdash2->tenantid);
        $tenantdash3 = $DB->get_record('totara_dashboard', ['sortorder' => 3], '*', MUST_EXIST);
        $this->assertSame($tenant3->id, $tenantdash3->tenantid);
        $tenantdash1b = (new totara_dashboard($tenantdash1->id))->clone_dashboard('Test dashboard', $tenant1->id);
        $tenantdash1b = $DB->get_record('totara_dashboard', ['id' => $tenantdash1b], '*', MUST_EXIST);

        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantparticipant' => "$tenant1->idnumber,$tenant3->idnumber"]);

        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $admin = get_admin();
        $guest = guest_user();

        $this->setUser(0);

        set_config('tenantsisolated', '0');

        $dashboards = totara_dashboard::get_user_dashboards(0);
        $this->assertEmpty($dashboards);

        $dashboards = totara_dashboard::get_user_dashboards($guest->id);
        $this->assertEmpty($dashboards);

        $dashboards = totara_dashboard::get_user_dashboards($admin->id);
        $this->assertSame([(int)$defaultdash->id], array_keys($dashboards));

        $dashboards = totara_dashboard::get_user_dashboards($user0_1->id);
        $this->assertSame([(int)$defaultdash->id], array_keys($dashboards));

        $dashboards = totara_dashboard::get_user_dashboards($user0_2->id);
        $this->assertSame([(int)$defaultdash->id, (int)$tenantdash1->id, (int)$tenantdash1b->id], array_keys($dashboards));

        $dashboards = totara_dashboard::get_user_dashboards($user1_1->id);
        $this->assertSame([(int)$tenantdash1->id, (int)$tenantdash1b->id, (int)$defaultdash->id], array_keys($dashboards));

        $dashboards = totara_dashboard::get_user_dashboards($user2_1->id);
        $this->assertSame([(int)$tenantdash2->id, (int)$defaultdash->id], array_keys($dashboards));

        set_config('tenantsisolated', '1');
        (cache::make_from_params(cache_store::MODE_REQUEST, 'totara_core', 'dashboard'))->purge();

        $dashboards = totara_dashboard::get_user_dashboards(0);
        $this->assertEmpty($dashboards);

        $dashboards = totara_dashboard::get_user_dashboards($guest->id);
        $this->assertEmpty($dashboards);

        $dashboards = totara_dashboard::get_user_dashboards($admin->id);
        $this->assertSame([(int)$defaultdash->id], array_keys($dashboards));

        $dashboards = totara_dashboard::get_user_dashboards($user0_1->id);
        $this->assertSame([(int)$defaultdash->id], array_keys($dashboards));

        $dashboards = totara_dashboard::get_user_dashboards($user0_2->id);
        $this->assertSame([(int)$defaultdash->id, (int)$tenantdash1->id, (int)$tenantdash1b->id], array_keys($dashboards));

        $dashboards = totara_dashboard::get_user_dashboards($user1_1->id);
        $this->assertSame([(int)$tenantdash1->id, (int)$tenantdash1b->id], array_keys($dashboards));

        $dashboards = totara_dashboard::get_user_dashboards($user2_1->id);
        $this->assertSame([(int)$tenantdash2->id], array_keys($dashboards));
    }
}
