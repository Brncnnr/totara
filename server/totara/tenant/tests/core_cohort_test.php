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
 * Tests covering tenancy changes in cohort related code.
 */
class totara_tenant_core_cohort_testcase extends advanced_testcase {
    public function test_cohort_update_cohort() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();
        $cohort = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);

        $record = clone($cohort);
        $record->cohorttype = 2;
        $record->component = '';
        $record->contextid = context_system::instance()->id;

        cohort_update_cohort($record);

        $record = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $record->timemodified = $cohort->timemodified; // Ignore update timestamp.
        $this->assertEquals($cohort, $record);
    }

    public function test_cohort_delete_cohort() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();
        $cohort = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);

        try {
            cohort_delete_cohort($cohort);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Tenant audience cannot be deleted', $e->getMessage());
        }
    }

    public function test_cohort_get_available_cohorts() {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);

        $tenant1 = $tenantgenerator->create_tenant(['name' => 'First tenant']);
        $tenantcategory1 = coursecat::get($tenant1->categoryid);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);

        $tenant2 = $tenantgenerator->create_tenant(['name' => 'Second tenant']);
        $tenantcategory2 = coursecat::get($tenant2->categoryid);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);

        $cohort0 = $this->getDataGenerator()->create_cohort(['name' => 'Top cohort']);

        $result = cohort_get_available_cohorts(context_course::instance($course0_1->id));
        $this->assertEquals([$cohort0->id], array_keys($result));

        $result = cohort_get_available_cohorts(context_course::instance($course1_1->id));
        $this->assertEquals([$tenant1->cohortid], array_keys($result));

        $result = cohort_get_available_cohorts(context_course::instance($course2_1->id));
        $this->assertEquals([$tenant2->cohortid], array_keys($result));
    }
}
