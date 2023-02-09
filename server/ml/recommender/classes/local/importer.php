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
use ml_recommender\local\csv\reader;
use ml_recommender\local\import\import;
use stdClass;

/**
 * @deprecated since Totara 17.0 ml_recommender importer has been deprecated.
 */
class importer {
    /**
     * @var stdClass
     */
    private $tenant = null;

    /**
     * @var import[]
     */
    private $imports = [];

    /**
     * @var string directory path for CSV files
     */
    private $data_path = '';

    /**
     * @var int Time when processing started
     */
    private $time = 0;

    /**
     * importer constructor.
     *
     * @param string $data_path directory path for CSV files
     * @param int    $time      adjust timestamp
     */
    public function __construct(string $data_path, int $time = 0) {
        $this->data_path = $data_path;
        $this->time = $time ?: time();
    }

    /**
     * Returning an array of importer class's name.
     * @return string[]
     */
    public static function get_import_classes(): array {
        return core_component::get_namespace_classes(
            'local\\import',
            import::class,
            'ml_recommender'
        );
    }

    /**
     * Get array of importer instances
     * @return import[]
     */
    public function get_imports(): array {
        if (!empty($this->imports)) {
            return $this->imports;
        }

        $classes = static::get_import_classes();

        foreach ($classes as $class) {
            $this->imports[] = new $class();
        }

        return $this->imports;
    }

    /**
     * Limit import to one tenant only
     * @param stdClass $tenant
     */
    public function set_tenant(stdClass $tenant) {
        $this->tenant = $tenant;
    }

    /**
     * Run all importers and save their results into CSV files in given folder
     */
    public function import() {
        $imports = $this->get_imports();

        foreach ($imports as $import) {
            $id = 0;
            if (!empty($this->tenant)) {
                $id = $this->tenant->id;
            }
            $csvpath = static::get_import_csv_file($import->get_name(), $id, $this->data_path);
            if (!file_exists($csvpath)) {
                if (!PHPUNIT_TEST) {
                    debugging('No import CSV found for ' . $import->get_name() . '. Skipping.');
                }
                continue;
            }
            $csv_reader = new reader($csvpath);
            $import->import($csv_reader, $this->time);
        }
    }

    /**
     * A helper function to construct the csv file path to import. Default the $data_path to
     * environment data path, only when it is not provided.
     *
     * @param string      $import_name
     * @param int         $id_number
     * @param string|null $data_path
     * @return string
     */
    public static function get_import_csv_file(string $import_name, int $id_number, ?string $data_path = null): string {
        if (empty($data_path)) {
            $data_path = environment::get_data_path();
        }

        $data_path = rtrim($data_path, "/\\");
        return "{$data_path}/{$import_name}_{$id_number}.csv";
    }

    /**
     * A helper function to construct the tenant csv file path. Default the $data_path
     * to environment data path, only when it is not provided.
     *
     * @param string|null $data_path
     * @return string
     */
    public static function get_tenant_csv_file(?string $data_path = null): string {
        if (empty($data_path)) {
            $data_path = environment::get_data_path();
        }

        $data_path = rtrim($data_path, "/\\");
        return "{$data_path}/tenants.csv";
    }

    /**
     * Load all tenants that were used during export
     */
    public function load_tenants() {
        $csv_reader = new reader(static::get_tenant_csv_file($this->data_path));
        $tenants = [];
        foreach ($csv_reader as $tenant) {
            $tenants[] = $tenant['tenants'];
        }
        return $tenants;
    }

    /**
     * Run all importers and remove old recommendations (previous imports)
     */
    public function clean() {
        $imports = $this->get_imports();
        foreach ($imports as $import) {
            $import->clean($this->time);
        }
    }
}