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

use core\task\scheduled_task;
use Exception;
use ml_recommender\local\environment;
use ml_recommender\local\flag;
use ml_recommender\local\importer;

/**
 * Class import performs all import logic
 * @deprecated since Totara 17.0 ml_recommender importer has been deprecated.
 */
class import extends scheduled_task {
    /**
     * @var bool
     */
    private $print_output;

    /**
     * import constructor.
     */
    public function __construct() {
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
        return get_string('importdatatask', 'ml_recommender');
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

        $this->output("Import directory {$data_path}");
        environment::enforce_data_path_sanity();

        flag::must_not_in_progress(flag::ML);
        flag::must_not_in_progress(flag::IMPORT);

        flag::must_start(flag::IMPORT);

        $this->output('Starting import...');
        try {
            $tenants = [null];
            if ($CFG->tenantsenabled) {
                $tenants = array_merge($tenants, $DB->get_records('tenant', ['suspended' => 0]));
            }

            $importer = new importer($data_path, time());
            $tenants_csv = $importer->load_tenants();
            foreach ($tenants as $tenant) {
                if ($tenant) {
                    $this->output("Importing for tenant {$tenant->name}");

                    if (!in_array($tenant->id, $tenants_csv) && !PHPUNIT_TEST) {
                        debugging("Tenant {$tenant->name} not found in CSV. Skipping.");
                        continue;
                    }

                    $importer->set_tenant($tenant);
                }
                $importer->import();
            }
            $this->output("Cleaning up old recommendations...");
            $importer->clean();
        } catch (Exception $e) {
            flag::complete(flag::IMPORT);
            throw $e;
        }

        flag::must_complete(flag::IMPORT);
        $this->output('Import completed');
    }
}