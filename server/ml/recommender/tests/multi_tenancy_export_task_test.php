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

use ml_recommender\local\environment;
use ml_recommender\local\exporter;
use ml_recommender\local\flag;
use ml_recommender\task\export;
use ml_recommender\local\export\export as export_abstract;

class ml_recommender_multi_tenancy_export_task_testcase extends advanced_testcase {
    /**
     * @var stdClass|null
     */
    private $tenant_one;

    /**
     * @var stdClass|null
     */
    private $tenant_two;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $this->tenant_one = $tenant_generator->create_tenant();
        $this->tenant_two = $tenant_generator->create_tenant();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_one = null;
        $this->tenant_two = null;

        export::cleanup(true);
    }


    /**
     * @return void
     */
    public function test_export_happy_paths(): void {
        $task = new export();
        $task->execute();

        $data_path = environment::get_data_path();
        self::assertTrue(file_exists($data_path));
        self::assertTrue(is_dir($data_path));

        $export_classes = exporter::get_export_classes();

        foreach ($export_classes as $export_class) {
            /** @var export_abstract $export */
            $export = new $export_class();

            // Tenant one file path.
            $file_tenant_one = exporter::get_export_csv_file_path(
                $export->get_name(),
                $this->tenant_one->id,
                $data_path
            );

            self::assertTrue(file_exists($file_tenant_one));
            self::assertFalse(is_dir($file_tenant_one));

            // Tenant two file path.
            $file_tenant_two = exporter::get_export_csv_file_path(
                $export->get_name(),
                $this->tenant_two->id,
                $data_path
            );

            self::assertTrue(file_exists($file_tenant_two));
            self::assertFalse(is_dir($file_tenant_two));
        }

        // Check for tenant file
        self::assertTrue(file_exists(exporter::get_tenant_csv_file_path($data_path)));
    }

    /**
     * @return void
     */
    public function test_export_with_locks(): void {
        $tmp_path = environment::get_temp_path();
        if (!is_dir($tmp_path)) {
            make_writable_directory($tmp_path);
        }

        // Create a lock file for export.
        $result = flag::start(flag::EXPORT, $tmp_path);
        self::assertTrue($result);

        $export_task = new export();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Possibly parallel process is still running");

        $export_task->execute();
    }
}