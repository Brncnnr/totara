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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class totara_reportbuilder_rb_content_certification_visibility_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_certification_report_source_without_tenants() {
        global $DB;

        /** @var \totara_program\testing\generator $program_gen */
        $program_gen = self::getDataGenerator()->get_plugin_generator('totara_program');

        self::setAdminUser();

        $audience = self::getDataGenerator()->create_cohort();

        $learner1 = self::getDataGenerator()->create_user();
        $learner2 = self::getDataGenerator()->create_user();
        $learner3 = self::getDataGenerator()->create_user();

        cohort_add_member($audience->id, $learner3->id);

        $cert1 = $program_gen->create_certification(['visible' => 1, 'audiencevisible' => COHORT_VISIBLE_ALL]);
        $cert2 = $program_gen->create_certification(['visible' => 0, 'audiencevisible' => COHORT_VISIBLE_NOUSERS]);
        $cert3 = $program_gen->create_certification(['visible' => 1, 'audiencevisible' => COHORT_VISIBLE_AUDIENCE]);
        totara_cohort_add_association($audience->id, $cert3->id, COHORT_ASSN_ITEMTYPE_CERTIF, COHORT_ASSN_VALUE_VISIBLE);

        $program_gen->assign_program($cert1->id, [$learner1->id, $learner2->id, $learner3->id]);
        $program_gen->assign_program($cert2->id, [$learner2->id, $learner3->id]);
        $program_gen->assign_program($cert3->id, [$learner3->id]);

        // Note: users don't actually have to be complete in a program to have completion records for it.

        $allcompletions = [$learner1->username, $learner2->username, $learner2->username, $learner3->username, $learner3->username, $learner3->username];

        $reportid = self::create_report('certification_completion', 'Test Certification Completion Report');
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $this->add_column($report, 'certif', 'shortname', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);

        $record = $DB->get_record('report_builder', ['id' => $reportid]);
        self::assertEquals(REPORT_BUILDER_ACCESS_MODE_NONE, $record->accessmode);
        self::assertEquals(REPORT_BUILDER_CONTENT_MODE_NONE, $record->contentmode);

        // Disable audience based visibility.
        set_config('audiencevisibility', 0);

        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        // Enable audience based visibility.
        set_config('audiencevisibility', 1);

        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        // Disable audience based visibility.
        set_config('audiencevisibility', 0);

        // Enable the content restriction.
        reportbuilder::update_setting($reportid, 'certification_visibility_content', 'enable', 1);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL);

        self::setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));

        // Enable audience based visibility.
        set_config('audiencevisibility', 1);

        self::setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(3, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));
    }

    public function test_certification_report_source_with_nonisolated_tenants() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = self::getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenantgenerator->enable_tenants();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        set_config('tenantsisolated', 0);
        $tenantgenerator->enable_tenants();

        /** @var \totara_program\testing\generator $program_gen */
        $program_gen = self::getDataGenerator()->get_plugin_generator('totara_program');

        self::setAdminUser();

        $audience = self::getDataGenerator()->create_cohort();

        $learner1 = self::getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $learner2 = self::getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $learner3 = self::getDataGenerator()->create_user(['tenantparticipant' => $tenant1->idnumber]);
        $learner4 = self::getDataGenerator()->create_user();

        cohort_add_member($audience->id, $learner3->id);

        $cert1 = $program_gen->create_certification(['visible' => 1, 'audiencevisible' => COHORT_VISIBLE_ALL, 'category' => $tenant1->categoryid]);
        $cert2 = $program_gen->create_certification(['visible' => 0, 'audiencevisible' => COHORT_VISIBLE_NOUSERS]);
        $cert3 = $program_gen->create_certification(['visible' => 1, 'audiencevisible' => COHORT_VISIBLE_AUDIENCE, 'category' => $tenant2->categoryid]);
        totara_cohort_add_association($audience->id, $cert3->id, COHORT_ASSN_ITEMTYPE_CERTIF, COHORT_ASSN_VALUE_VISIBLE);

        $program_gen->assign_program($cert1->id, [$learner1->id, $learner2->id, $learner3->id]);
        $program_gen->assign_program($cert2->id, [$learner2->id, $learner3->id]);
        $program_gen->assign_program($cert3->id, [$learner3->id]);

        // Note: users don't actually have to be complete in a program to have completion records for it.

        $allcompletions = [$learner1->username, $learner2->username, $learner2->username, $learner3->username, $learner3->username, $learner3->username];

        $reportid = self::create_report('certification_completion', 'Test Certification Completion Report');
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $this->add_column($report, 'certif', 'shortname', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);

        $record = $DB->get_record('report_builder', ['id' => $reportid]);
        self::assertEquals(REPORT_BUILDER_ACCESS_MODE_NONE, $record->accessmode);
        self::assertEquals(REPORT_BUILDER_CONTENT_MODE_NONE, $record->contentmode);

        // Disable audience based visibility.
        set_config('audiencevisibility', 0);

        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner2);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        // Enable audience based visibility.
        set_config('audiencevisibility', 1);

        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner2);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        // Disable audience based visibility.
        set_config('audiencevisibility', 0);

        // Enable the content restriction.
        reportbuilder::update_setting($reportid, 'certification_visibility_content', 'enable', 1);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL);

        self::setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(3, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner2);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(1, $records);
        self::assertEqualsCanonicalizing([$learner3->username], array_column($records, 'user_username'));

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));

        // Enable audience based visibility.
        set_config('audiencevisibility', 1);

        self::setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(3, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(3, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner2);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(0, $records);
        self::assertEmpty($records);

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(3, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username], array_column($records, 'user_username'));
    }

    public function test_certification_report_source_with_isolated_tenants() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = self::getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenantgenerator->enable_tenants();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        set_config('tenantsisolated', 1);
        $tenantgenerator->enable_tenants();

        /** @var \totara_program\testing\generator $program_gen */
        $program_gen = self::getDataGenerator()->get_plugin_generator('totara_program');

        self::setAdminUser();

        $audience = self::getDataGenerator()->create_cohort();

        $learner1 = self::getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $learner2 = self::getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $learner3 = self::getDataGenerator()->create_user(['tenantparticipant' => $tenant1->idnumber]);
        $learner4 = self::getDataGenerator()->create_user();

        cohort_add_member($audience->id, $learner3->id);

        $cert1 = $program_gen->create_certification(['visible' => 1, 'audiencevisible' => COHORT_VISIBLE_ALL, 'category' => $tenant1->categoryid]);
        $cert2 = $program_gen->create_certification(['visible' => 0, 'audiencevisible' => COHORT_VISIBLE_NOUSERS]);
        $cert3 = $program_gen->create_certification(['visible' => 1, 'audiencevisible' => COHORT_VISIBLE_AUDIENCE, 'category' => $tenant2->categoryid]);
        totara_cohort_add_association($audience->id, $cert3->id, COHORT_ASSN_ITEMTYPE_CERTIF, COHORT_ASSN_VALUE_VISIBLE);

        $program_gen->assign_program($cert1->id, [$learner1->id, $learner2->id, $learner3->id]);
        $program_gen->assign_program($cert2->id, [$learner2->id, $learner3->id]);
        $program_gen->assign_program($cert3->id, [$learner3->id]);

        // Note: users don't actually have to be complete in a program to have completion records for it.

        $allcompletions = [$learner1->username, $learner2->username, $learner2->username, $learner3->username, $learner3->username, $learner3->username];

        $reportid = self::create_report('certification_completion', 'Test Certification Completion Report');
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        $this->add_column($report, 'certif', 'shortname', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);

        $record = $DB->get_record('report_builder', ['id' => $reportid]);
        self::assertEquals(REPORT_BUILDER_ACCESS_MODE_NONE, $record->accessmode);
        self::assertEquals(REPORT_BUILDER_CONTENT_MODE_NONE, $record->contentmode);

        // Enable audience based visibility.
        set_config('audiencevisibility', 1);

        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner2);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        // Enable audience based visibility.
        set_config('audiencevisibility', 1);

        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner2);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setUser($learner4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        // Disable audience based visibility.
        set_config('audiencevisibility', 0);

        // Enable the content restriction.
        reportbuilder::update_setting($reportid, 'certification_visibility_content', 'enable', 1);
        $DB->set_field('report_builder', 'contentmode', REPORT_BUILDER_CONTENT_MODE_ALL);

        self::setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(3, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner2);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(1, $records);
        self::assertEqualsCanonicalizing([$learner3->username], array_column($records, 'user_username'));

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));

        // Enable audience based visibility.
        set_config('audiencevisibility', 1);

        self::setAdminUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(6, $records);
        self::assertEqualsCanonicalizing($allcompletions, array_column($records, 'user_username'));

        self::setGuestUser();
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true));
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(3, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner1);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(3, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner2);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(0, $records);
        self::assertEmpty($records);

        self::setUser($learner3);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(4, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username, $learner3->username], array_column($records, 'user_username'));

        self::setUser($learner4);
        $report = reportbuilder::create($reportid, (new rb_config())->set_nocache(true), false);
        list($sql, $params,) = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        self::assertCount(3, $records);
        self::assertEqualsCanonicalizing([$learner1->username, $learner2->username, $learner3->username], array_column($records, 'user_username'));
    }
}
