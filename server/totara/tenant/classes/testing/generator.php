<?php
/*
 * This file is part of Totara LMearn
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

namespace totara_tenant\testing;

use stdClass;
use totara_tenant\local\util;
use core\record\tenant;

defined('MOODLE_INTERNAL') || die();

/**
 * Tenant generator.
 */
final class generator extends \core\testing\component_generator {
    /** @var number of created tenant instances */
    protected $tenantcount = 0;

    /**
     * To be called from data reset code only, do not use in tests.
     *
     * @return void
     */
    public function reset() {
        $this->tenantcount = 0;
    }

    /**
     * Utility method to enable multitenancy,
     * tenant roles are installed the same way as if enabled via admin settings.
     */
    public function enable_tenants() {
        set_config('tenantsenabled', 1);
        util::check_roles_exist();
    }

    /**
     * Utility method to disable multitenancy.
     */
    public function disable_tenants() {
        set_config('tenantsenabled', 0);
    }

    /**
     * Create tenant.
     *
     * @param array|stdClass|null $record
     * @return tenant the created tenant record
     */
    public function create_tenant($record = null) {
        global $DB;

        $record = (array)$record;
        $this->tenantcount++;

        $tenant = new \stdClass();
        $tenant->name = empty($record['name']) ? "Tenant {$this->tenantcount}" : $record['name'];
        $tenant->idnumber = empty($record['idnumber']) ? "tenantidnumber{$this->tenantcount}" : $record['idnumber'];
        $tenant->description = $record['description'] ?? '';
        $tenant->descriptionformat = $record['descriptionformat'] ?? FORMAT_HTML;
        $tenant->suspended = !isset($record['suspended']) ? 0 : (int)(bool)$record['suspended'];
        $tenant->categoryname = empty($record['categoryname']) ? "{$tenant->name} category" : $record['categoryname'];
        $tenant->categoryidnumber = $tenant->idnumber;
        $tenant->cohortname = empty($record['cohortname']) ? "{$tenant->name} audience" : $record['cohortname'];
        $tenant->cohortidnumber = $tenant->idnumber;

        // Clone first dashboard.
        if (isset($record['clonedashboard'])) {
            $tenant->clonedashboard = $record['clonedashboard'];
        } else {
            $dashboards = $DB->get_records_menu('totara_dashboard', [], 'sortorder ASC', 'id, name');
            reset($dashboards);
            $tenant->clonedashboard = key($dashboards);
        }
        $tenant->dashboardname = empty($record['dashboardname']) ? "{$tenant->name} dashboard" : $record['dashboardname'];

        return util::create_tenant((array)$tenant);
    }

    /**
     * @param int   $userid
     * @param int[] $tenantids
     *
     * @return void
     */
    public function set_user_participation(int $userid, array $tenantids): void {
        util::set_user_participation($userid, $tenantids);
    }

    /**
     * @param int $user_id
     * @param int $tenant_id
     *
     * @return void
     */
    public function migrate_user_to_tenant(int $user_id, int $tenant_id): void {
        util::migrate_user_to_tenant($user_id, $tenant_id);
    }
}
