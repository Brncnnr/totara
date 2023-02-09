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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\local;

use core_component;
use ml_recommender\local\csv\writer;
use ml_recommender\local\export\export;
use stdClass;

class exporter {
    /**
     * @var stdClass
     */
    private $tenant = null;

    /**
     * Track of all processed tenants
     * @var int[]
     */
    private $tenantids = [];

    /**
     * @var export[]
     */
    private $exports = [];

    /**
     * @var string directory path for CSV files
     */
    private $data_path;

    /**
     * exporter constructor.
     *
     * @param string $data_path directory path for CSV files
     */
    public function __construct(string $data_path) {
        $this->data_path = $data_path;
    }

    /**
     * Returning a class name.
     * @return string[]
     */
    public static function get_export_classes(): array {
        return core_component::get_namespace_classes(
            'local\\export',
            export::class,
            'ml_recommender'
        );
    }

    /**
     * Get array of exporter instances
     * @return export[]
     */
    public function get_exports(): array {
        if (!empty($this->exports)) {
            return $this->exports;
        }

        $classes = static::get_export_classes();

        foreach ($classes as $class) {
            $this->exports[] = new $class();
        }

        return $this->exports;
    }

    /**
     * Limit export to one tenant only
     * @param stdClass $tenant
     */
    public function set_tenant(stdClass $tenant) {
        $this->tenant = $tenant;
    }

    /**
     * Run all exporters and save their results into CSV files in given folder
     */
    public function export() {
        $exporters = $this->get_exports();

        foreach ($exporters as $exporter) {
            $id = 0;
            if (!empty($this->tenant)) {
                $id = $this->tenant->id;
                $this->tenantids[$id] = $id;
            }

            $csv_path = static::get_export_csv_file_path($exporter->get_name(), $id, $this->data_path);
            $csv_writer = new writer($csv_path);

            if (!empty($this->tenant)) {
                $exporter->set_tenant($this->tenant);
            }
            $exporter->export($csv_writer);
        }
    }

    /**
     * A metadata helper function to build a full file path for any csv.
     * If $data_path is not provided, we will use the environment temp path,
     * because it is too risky to use the actual environment data path.
     *
     * @param string      $export_name
     * @param int         $id_number
     * @param string|null $data_path
     *
     * @return string
     */
    public static function get_export_csv_file_path(string $export_name, int $id_number, ?string $data_path = null): string {
        if (empty($data_path)) {
            $data_path = environment::get_temp_path();
        }

        $data_path = rtrim($data_path, "/\\");
        return "{$data_path}/{$export_name}_{$id_number}.csv";
    }

    /**
     * Save all registered tenants during export into CSV file
     */
    public function export_tenants() {
        $csv_file = static::get_tenant_csv_file_path($this->data_path);

        $writer = new writer($csv_file);
        $writer->add_headings(['tenants']);

        // The nil tenant is where we find users who are not (yet) assigned to any tenant.
        $writer->add_data([0]);

        // List the registered tenants.
        foreach ($this->tenantids as $tenantid) {
            $writer->add_data([$tenantid]);
        }
        $writer->close();
    }

    /**
     * If $data_path has not yet provided, we fallback to the temporary path.
     *
     * @param string|null $data_path
     * @return string
     */
    public static function get_tenant_csv_file_path(?string $data_path = null): string {
        if (empty($data_path)) {
            $data_path = environment::get_temp_path();
        }

        $data_path = rtrim($data_path, "/\\");
        return "{$data_path}/tenants.csv";
    }

    /**
     * Return a single list of all potential exporter files.
     *
     * @param string|null $data_path Use a specific data path instead
     * @return array
     */
    public static function get_list_of_files(string $data_path = null): array {
        global $CFG, $DB;
        $data_path = $data_path ?? environment::get_data_path();

        $exporter = new static($data_path);
        $exporters = $exporter->get_exports();

        $files = [];
        $tenants = [(object)['id' => 0]];

        // We accept the tenants.csv file even if multi-tenants are disabled
        $path = self::get_tenant_csv_file_path($data_path);
        $files[] = [
            'path' => $path,
            'group' => 'tenants',
        ];
        if ($CFG->tenantsenabled) {
            $tenants = array_merge($tenants, $DB->get_records('tenant', ['suspended' => 0]));
        }

        foreach ($exporters as $export) {
            foreach ($tenants as $tenant) {
                $path = self::get_export_csv_file_path($export->get_name(), $tenant->id, $data_path);
                $files[] = [
                    'path' => $path,
                    'group' => $export->get_name(),
                ];
            }
        }

        return $files;
    }
}