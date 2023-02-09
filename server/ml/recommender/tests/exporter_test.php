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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;
use ml_recommender\local\environment;
use ml_recommender\local\export\item_data;
use ml_recommender\local\export\user_data;
use ml_recommender\local\export\user_interactions;
use ml_recommender\local\exporter;

class ml_recommender_exporter_testcase extends testcase {
    /**
     * This test is to annoy someone who add new export types. So that it can remind that
     * person to know it can cause regression.
     *
     * @return void
     */
    public function test_get_export_classes(): void {
        $classes = exporter::get_export_classes();

        self::assertNotEmpty($classes);

        // Default to 3, bump this number if you add more.
        self::assertCount(3, $classes);
        foreach ($classes as $export_class) {
            self::assertContainsEquals(
                $export_class,
                [item_data::class, user_data::class, user_interactions::class]
            );
        }
    }

    /**
     * Assert the response from get_list_of_files is what we expect.
     * Here to trap any new export types added & to make sure we know exactly what's happening.
     *
     * @return void
     */
    public function test_get_list_of_files(): void {
        $expected = [
            [
                'path' => exporter::get_tenant_csv_file_path('/m/p/'),
                'group' => 'tenants',
            ],
            [
                'path' => exporter::get_export_csv_file_path('item_data', 0, '/m/p/'),
                'group' => 'item_data',
            ],
            [
                'path' => exporter::get_export_csv_file_path('user_data', 0, '/m/p/'),
                'group' => 'user_data',
            ],
            [
                'path' => exporter::get_export_csv_file_path('user_interactions', 0, '/m/p/'),
                'group' => 'user_interactions',
            ],
        ];

        self::assertEqualsCanonicalizing($expected, exporter::get_list_of_files('/m/p/'));
    }

    /**
     * @return void
     */
    public function test_get_csv_path(): void {
        $tmp_path = environment::get_temp_path();
        $tmp_path = rtrim($tmp_path, "/\\");

        // Default falling back to temp path of environment.
        self::assertEquals(
            "{$tmp_path}/this_is_name_42.csv",
            exporter::get_export_csv_file_path("this_is_name", 42)
        );

        self::assertEquals(
            "/boom/x/o/me/begining_42.csv",
            exporter::get_export_csv_file_path("begining", "42", "/boom/x/o/me/")
        );

        self::assertEquals(
            "/boom/x/o/me/begining_42.csv",
            exporter::get_export_csv_file_path("begining", "42", "/boom/x/o/me")
        );

        self::assertEquals(
            "/boom/x/o/me/begining_42.csv",
            exporter::get_export_csv_file_path("begining", "42", "/boom/x/o/me\/")
        );
    }

    /**
     * @return void
     */
    public function test_get_tenant_csv_path(): void {
        $tmp_path = environment::get_temp_path();
        $tmp_path = rtrim($tmp_path, "/\\");

        self::assertEquals(
            "{$tmp_path}/tenants.csv",
            exporter::get_tenant_csv_file_path()
        );

        self::assertEquals(
            "/boom/x/o/me/tenants.csv",
            exporter::get_tenant_csv_file_path("/boom/x/o/me/")
        );

        self::assertEquals(
            "/boom/x/o/me/tenants.csv",
            exporter::get_tenant_csv_file_path("/boom/x/o/me")
        );

        self::assertEquals(
            "/boom/x/o/me/tenants.csv",
            exporter::get_tenant_csv_file_path("/boom/x/o/me\/")
        );
    }

    /**
     * Ensure that tenant 0 is written to csv when no tenants registered on site.
     *
     * @return void
     */
    public function test_tenant_csv_file_no_tenants(): void {
        // Set up exporter.
        $data_path = $this->make_temp_dir();
        $exporter = new exporter($data_path);
        $path = $exporter::get_tenant_csv_file_path();

        // Check that we have a tenant record for tenant 0.
        $exporter->export_tenants();
        $lines = file($path);
        $line = explode("\n", $lines[1]);
        $tenant_id = (int) $line[0];

        // Heading line and tenant id 0 line.
        self::assertEquals(2, count($lines));

        // Second line should have tenant id 0.
        self::assertEquals(0, $tenant_id);
    }

    /**
     * Ensure that tenant 0 is written to csv when there are tenants registered on site.
     *
     * @return void
     */
    public function test_tenant_csv_file_with_tenants(): void {
        // Set up exporter.
        $data_path = $this->make_temp_dir();
        $exporter = new exporter($data_path);
        $path = $exporter::get_tenant_csv_file_path();

        // Set up reflected class.
        $reflection = new ReflectionClass($exporter);
        $property = $reflection->getProperty('tenantids');
        $property->setAccessible(true);

        // Create some tenants.
        $tenant_count = 2;
        $tenants = $this->create_tenants($tenant_count);

        // Set list of registered tenant ids.
        $property->setValue($exporter, $this->get_tenant_ids());
        $exporter->export_tenants();

        // Get file contents into array.
        $lines = file($path);
        $line = explode("\n", $lines[1]);
        $tenant_id = (int) $line[0];

        // Heading line and tenant id 0 line plus number of registered tenants.
        self::assertEquals(2 + $tenant_count, count($lines));

        // Second line should still have tenant id 0.
        self::assertEquals(0, $tenant_id);

        // Last line should have highest tenant id.
        $line = explode("\n", end($lines));
        $tenant_id = (int) $line[0];
        $last_tenant = end($tenants);
        self::assertEquals($last_tenant->id, $tenant_id);
    }

    /**
     * Make a temp directory to run test in.
     *
     * @return string
     */
    public function make_temp_dir(): string {
        $data_path = environment::get_temp_path();
        mkdir($data_path, 0777, true);

        return $data_path;
    }

    /**
     * Retrieve all tenant ids.
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_tenant_ids(): array {
        global $DB;

        $sql = 'SELECT t.id FROM "ttr_tenant" t ORDER BY t.id ASC';
        return $DB->get_fieldset_sql($sql);
    }

    /**
     * Generate a specified number of tenants.
     *
     * @param int $count
     * @return array
     * @throws coding_exception
     */
    protected function create_tenants(int $count): array {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenants = [];
        for ($i = 0; $i < $count; $i++) {
            $tenants[] = $tenant_generator->create_tenant();
        }

        return $tenants;
    }
}