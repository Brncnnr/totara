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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class totara_reportbuilder_rb_content_user_visibility_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_user_source_without_tenants() {
        global $DB;

        $this->setAdminUser();

        $admin = get_admin();
        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user(['deleted' => 0, 'confirmed' => 1]);
        $user2 = $this->getDataGenerator()->create_user(['deleted' => 1, 'confirmed' => 1]);
        $user3 = $this->getDataGenerator()->create_user(['deleted' => 0, 'confirmed' => 0]);

        $reportid = $this->create_report('user', 'Test User Report');
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);

        $record = $DB->get_record('report_builder', ['id' => $reportid]);
        $this->assertEquals(REPORT_BUILDER_ACCESS_MODE_NONE, $record->accessmode);
        $this->assertEquals(REPORT_BUILDER_CONTENT_MODE_NONE, $record->contentmode);

        // NOTE: user source has hardcoded deleted=0 in define_sourcewhere(), we can hack around it for now here.
        $this->setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user3->id], array_keys($records));

        $this->setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = ''; // Hack!
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id], array_keys($records));

        $this->setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id], array_keys($records));

        $this->setUser(null);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id], array_keys($records));

        // Enable the content restriction.
        reportbuilder::update_setting($reportid, 'user_visibility_content', 'enable', 1);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL);

        $this->setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id], array_keys($records));

        $this->setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id], array_keys($records));

        $this->setUser(null);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id], array_keys($records));
    }

    public function test_user_source_with_nonisolated_tenants() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        set_config('tenantsisolated', '0');
        $tenantgenerator->enable_tenants();

        $this->setAdminUser();

        $admin = get_admin();
        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user(['deleted' => 0, 'confirmed' => 1]);
        $user2 = $this->getDataGenerator()->create_user(['deleted' => 1, 'confirmed' => 1]);
        $user3 = $this->getDataGenerator()->create_user(['deleted' => 0, 'confirmed' => 0]);
        $user4 = $this->getDataGenerator()->create_user(['deleted' => 0, 'confirmed' => 1, 'tenantid' => $tenant1->id]);
        $user5 = $this->getDataGenerator()->create_user(['deleted' => 0, 'confirmed' => 1, 'tenantid' => $tenant2->id]);
        $user6 = $this->getDataGenerator()->create_user(['tenantparticipant' => $tenant1->idnumber]);

        $reportid = $this->create_report('user', 'Test User Report');
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);

        $record = $DB->get_record('report_builder', ['id' => $reportid]);
        $this->assertEquals(REPORT_BUILDER_ACCESS_MODE_NONE, $record->accessmode);
        $this->assertEquals(REPORT_BUILDER_CONTENT_MODE_NONE, $record->contentmode);

        // NOTE: user source has hardcoded deleted=0 in define_sourcewhere()

        $this->setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser(null);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser($user1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser($user4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser($user5);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser($user6);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        // Enable the content restriction.
        reportbuilder::update_setting($reportid, 'user_visibility_content', 'enable', 1);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL);

        $this->setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user6->id], array_keys($records));

        $this->setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user6->id], array_keys($records));

        $this->setUser(null);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user6->id], array_keys($records));

        $this->setUser($user1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user6->id], array_keys($records));

        $this->setUser($user4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user4->id, $user6->id], array_keys($records));

        $this->setUser($user5);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser($user6);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user4->id, $user6->id], array_keys($records));
    }

    public function test_user_source_with_isolated_tenants() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        set_config('tenantsisolated', '1');
        $tenantgenerator->enable_tenants();

        $this->setAdminUser();

        $admin = get_admin();
        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user(['deleted' => 0, 'confirmed' => 1]);
        $user2 = $this->getDataGenerator()->create_user(['deleted' => 1, 'confirmed' => 1]);
        $user3 = $this->getDataGenerator()->create_user(['deleted' => 0, 'confirmed' => 0]);
        $user4 = $this->getDataGenerator()->create_user(['deleted' => 0, 'confirmed' => 1, 'tenantid' => $tenant1->id]);
        $user5 = $this->getDataGenerator()->create_user(['deleted' => 0, 'confirmed' => 1, 'tenantid' => $tenant2->id]);
        $user6 = $this->getDataGenerator()->create_user(['tenantparticipant' => $tenant1->idnumber]);

        $reportid = $this->create_report('user', 'Test User Report');
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);

        $record = $DB->get_record('report_builder', ['id' => $reportid]);
        $this->assertEquals(REPORT_BUILDER_ACCESS_MODE_NONE, $record->accessmode);
        $this->assertEquals(REPORT_BUILDER_CONTENT_MODE_NONE, $record->contentmode);

        // NOTE: user source has hardcoded deleted=0 in define_sourcewhere()

        $this->setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser(null);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser($user1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser($user4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser($user5);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        $this->setUser($user6);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $guest->id, $user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id], array_keys($records));

        // Enable the content restriction.
        reportbuilder::update_setting($reportid, 'user_visibility_content', 'enable', 1);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL);

        $this->setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user6->id], array_keys($records));

        $this->setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user6->id], array_keys($records));

        $this->setUser(null);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user6->id], array_keys($records));

        $this->setUser($user1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user6->id], array_keys($records));

        $this->setUser($user4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$user4->id, $user6->id], array_keys($records));

        $this->setUser($user5);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$user5->id], array_keys($records));

        $this->setUser($user6);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        $report->src->sourcewhere = '';
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $this->assertEqualsCanonicalizing([$admin->id, $user1->id, $user4->id, $user6->id], array_keys($records));
    }
}
