<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totara.com>
 * @package totara_job
 */

use core_phpunit\testcase;
use totara_job\data_provider\job_assignments;
use totara_job\job_assignment;

/**
 * Test the job_assignments data_provider class
 */
class totara_job_data_provider_job_assignments_testcase extends testcase {
    /**
     * @return void
     */
    public function test_data_provider(): void {
        // Create job assignment.
        $user = $this->getDataGenerator()->create_user();
        $test_job = job_assignment::create_default($user->id);

        // Load it via data provider.
        $provider = new job_assignments();
        $items = $provider->get();

        $this->assertCount(1, $items);
        $first = $items->first();
        $this->assertInstanceOf(job_assignment::class, $first);
        $this->assertEquals($test_job->id, $first->id);
    }

    /**
     * @return void
     */
    public function test_offset_pagination(): void {
        // Create 14 job assignments.
        $user = $this->getDataGenerator()->create_user();
        $test_jobs = [];
        for ($i = 0; $i < 14; $i++) {
            $test_jobs[] = job_assignment::create(['userid' => $user->id, 'idnumber' => 'job_' . $i]);
        }

        // Load them via data provider.
        $provider = new job_assignments();
        $result1 = $provider->get_offset_page(5, 1);
        $result2 = $provider->get_offset_page(5, 2);
        $result3 = $provider->get_offset_page(5, 3);

        // Expected page sizes.
        $this->assertCount(5, $result1->items);
        $this->assertCount(5, $result2->items);
        $this->assertCount(4, $result3->items);

        // Expected next cursors (not strictly necessary)
        $this->assertNotEmpty($result1->next_cursor);
        $this->assertNotEmpty($result2->next_cursor);
        $this->assertEmpty($result3->next_cursor);

        // Expected totals.
        $this->assertEquals(14, $result1->total);
        $this->assertEquals(14, $result2->total);
        $this->assertEquals(14, $result3->total);

        // Not the same items.
        $foundkeys = [];
        foreach ($result1->items->keys() as $id) {
            $this->assertNotContains($id, $foundkeys);
            $foundkeys[] = $id;
        }
        foreach ($result2->items->keys() as $id) {
            $this->assertNotContains($id, $foundkeys);
            $foundkeys[] = $id;
        }
        foreach ($result3->items->keys() as $id) {
            $this->assertNotContains($id, $foundkeys);
            $foundkeys[] = $id;
        }

        // Test with no arguments.
        $result = $provider->get_offset_page();
        $this->assertCount(14, $result->items);
        $this->assertEquals(14, $result->total);
        $this->assertEmpty($result->next_cursor);
    }

    /**
     * @return void
     */
    public function test_cursor_pagination(): void {
        // Create 14 job assignments.
        $user = $this->getDataGenerator()->create_user();
        $test_jobs = [];
        for ($i = 0; $i < 14; $i++) {
            $test_jobs[] = job_assignment::create(['userid' => $user->id, 'idnumber' => 'job_' . $i]);
        }

        // Load them via data provider.
        $provider = new job_assignments();
        $result1 = $provider->get_page(null, 5);
        $result2 = $provider->get_page($result1->next_cursor, 5);
        $result3 = $provider->get_page($result2->next_cursor, 5);

        // Expected page sizes.
        $this->assertCount(5, $result1->items);
        $this->assertCount(5, $result2->items);
        $this->assertCount(4, $result3->items);

        // Expected next cursors.
        $this->assertNotEmpty($result1->next_cursor);
        $this->assertNotEmpty($result2->next_cursor);
        $this->assertEmpty($result3->next_cursor);

        // Expected totals.
        $this->assertEquals(14, $result1->total);
        $this->assertEquals(14, $result2->total);
        $this->assertEquals(14, $result3->total);

        // Not the same items.
        $foundkeys = [];
        foreach ($result1->items->keys() as $id) {
            $this->assertNotContains($id, $foundkeys);
            $foundkeys[] = $id;
        }
        foreach ($result2->items->keys() as $id) {
            $this->assertNotContains($id, $foundkeys);
            $foundkeys[] = $id;
        }
        foreach ($result3->items->keys() as $id) {
            $this->assertNotContains($id, $foundkeys);
            $foundkeys[] = $id;
        }

        // Test with no arguments.
        $result = $provider->get_page();
        $this->assertCount(14, $result->items);
        $this->assertEquals(14, $result->total);
        $this->assertEmpty($result->next_cursor);
    }

    /**
     * @return void
     */
    public function test_sort_by_id(): void {
        // Create 5 job assignments.
        $user = $this->getDataGenerator()->create_user();
        $test_job_ids = [];
        for ($i = 0; $i < 5; $i++) {
            $test_job = job_assignment::create(['userid' => $user->id, 'idnumber' => 'job_' . $i]);
            $test_job_ids[] = $test_job->id;
        }

        // Load them via data provider, default sorting.
        $provider = new job_assignments();
        $items = $provider->get();
        $this->assertEquals($test_job_ids, $items->keys());

        // Sort by id descending.
        $provider = new job_assignments();
        $provider->sort_by(['column' => 'id', 'direction' => 'DESC']);
        $items = $provider->get();
        $this->assertEquals(array_reverse($test_job_ids), $items->keys());

        // Sort by id ascending.
        $provider = new job_assignments();
        $provider->sort_by(['column' => 'id', 'direction' => 'ASC']);
        $items = $provider->get();
        $this->assertEquals($test_job_ids, $items->keys());
    }

    /**
     * @return void
     */
    public function test_invalid_sorting(): void {
        $provider = new job_assignments();

        // No column.
        try {
            $provider->sort_by(['direction' => 'ASC']);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            $this->assertStringContainsString("Sort parameter must have a 'column' key", $e->getMessage());
        }

        // Invalid column.
        try {
            $provider->sort_by(['column' => 'totara']);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            $this->assertStringContainsString('Unknown sort column', $e->getMessage());
        }

        // Invalid direction.
        try {
            $provider->sort_by(['column' => 'id', 'direction' => 'FOO']);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            $this->assertStringContainsString("Invalid sort direction", $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_filter_query_by_tenant_id(): void {
        // Create 5 job assignments for system user.
        $system_user = $this->getDataGenerator()->create_user();
        $test_jobs = ['system' => []];
        $total_jobs = 0;
        for ($i = 0; $i < 5; $i++) {
            $test_job = job_assignment::create(['userid' => $system_user->id, 'idnumber' => 'job_' . $i]);
            $test_jobs['system'][] = $test_job->id;
            $total_jobs++;
        }

        // Load them via data provider, no filter.
        $provider = new job_assignments();
        $items = $provider->get();
        $this->assertCount($total_jobs, $items);

        // Load them with tenant_id filter set to non-existent tenant.
        $provider = new job_assignments();
        $provider->add_filters(['tenant_id' => 42]);
        $items = $provider->get();
        $this->assertCount(0, $items);
    }

    /**
     * @return void
     */
    public function test_filter_query_with_actual_tenant_id(): void {
        // Set up.
        global $CFG;
        $original_config = $CFG->tenantsenabled;

        self::setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $tenant_generator = self::getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        // Create a Sales Assistant job assignment for tenant1.
        $test_user = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $ja_params_tenant1 = ['userid' => $test_user->id, 'idnumber' => uniqid()];
        $test_job_assignment_tenant1 = job_assignment::create($ja_params_tenant1);

        $tenant2 = $tenant_generator->create_tenant();
        // Create a Sales Assistant job assignment for tenant2.
        $test_user = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $ja_params_tenant2 = ['userid' => $test_user->id, 'idnumber' => uniqid()];
        $test_job_assignment_tenant2 = job_assignment::create($ja_params_tenant2);

        // Operate.
        $provider = new job_assignments();
        $provider->add_filters(['tenant_id' => $tenant1->id]);
        $items = $provider->get();

        // Assert. Only the record from tenant1 should have been returned.
        $this->assertCount(1, $items);
        $job_assignment_record = $items->first();
        $this->assertEquals($test_job_assignment_tenant1->id, $job_assignment_record->id);

        // Tear down.
        set_config('tenantsenabled', $original_config);
    }

    /**
     * @return void
     */
    public function test_filter_with_invalid_tenant(): void {
        $provider = new job_assignments();
        $provider->add_filters(['tenant_id' => -999]);

        $this->expectExceptionMessage('tenant filter must have an id for value');
        // Operate.
        $items = $provider->get();
    }
}
