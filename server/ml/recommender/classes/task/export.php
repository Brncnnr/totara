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

namespace ml_recommender\task;

use coding_exception;
use core\task\scheduled_task;
use Exception;
use ml_recommender\local\environment;
use ml_recommender\local\exporter;
use ml_recommender\local\flag;

/**
 * Class export performs all export logic
 */
class export extends scheduled_task {
    /**
     * @var bool
     */
    private $print_output;

    /**
     * export constructor.
     */
    public function __construct() {
        // Only print the output when we are not in phpunit environment.
        $this->print_output = (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST);
    }

    /**
     * @param bool $print_output
     * @return void
     */
    public function set_print_output(bool $print_output): void {
        $this->print_output = $print_output;
    }

    /**
     * @return string
     */
    public function get_name() {
        return get_string('exportdatatask', 'ml_recommender');
    }

    /**
     * @param string $message
     * @return void
     */
    private function output(string $message): void {
        if ($this->print_output) {
            mtrace($message);
        }
    }

    /**
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        $data_path = environment::get_data_path();
        $tmp_path = environment::get_temp_path();
        $backup_path = environment::get_backup_path();

        $this->output("Export directory {$data_path}");
        environment::enforce_data_path_sanity();

        // All processes must not be in progress.
        flag::must_not_in_progress(flag::ML);
        flag::must_not_in_progress(flag::IMPORT);
        flag::must_not_in_progress(flag::EXPORT, $tmp_path);

        static::cleanup();

        if (!mkdir($tmp_path, $CFG->directorypermissions, true)) {
            throw new coding_exception('Error creating temp directory: ' . $tmp_path);
        }

        flag::must_start(flag::EXPORT, $tmp_path);
        $this->output('Starting export...');

        try {
            $tenants = [null];
            if ($CFG->tenantsenabled) {
                $tenants = array_merge($tenants, $DB->get_records('tenant', ['suspended' => 0]));
            }

            $exporter = new exporter($tmp_path);
            foreach ($tenants as $tenant) {
                if ($tenant) {
                    $this->output("Exporting for tenant {$tenant->name}");
                    $exporter->set_tenant($tenant);
                }
                $exporter->export();
            }
            $exporter->export_tenants();
        } catch (Exception $e) {
            flag::complete(flag::EXPORT, $tmp_path);
            throw $e;
        }
        flag::must_complete(flag::EXPORT, $tmp_path);

        if (file_exists($data_path) && !rename($data_path, $backup_path)) {
            throw new coding_exception("Could not move previous export to backup location: " . $backup_path);
        }

        if (!rename($tmp_path, $data_path)) {
            throw new coding_exception('Could not move current export to expected location: ' . $data_path);
        }

        static::cleanup();
        $this->output('Export completed.');
    }

    /**
     * Remove work, and backup paths.
     *
     * @param bool $all Remove also data path files if true
     * @return void
     */
    public static function cleanup(bool $all = false) {
        global $CFG;

        $data_path = environment::get_data_path();
        $tmp_path = environment::get_temp_path();
        $backup_path = environment::get_backup_path();

        require_once $CFG->libdir . '/filelib.php';

        fulldelete($backup_path);
        fulldelete($tmp_path);

        if ($all && !fulldelete($data_path)) {
            throw new coding_exception('Could not cleanup data path (ml_recommender/data_path)');
        }
    }
}