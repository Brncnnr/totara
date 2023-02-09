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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Tenant related steps
 */
class behat_totara_tenant extends behat_base {
    /**
     * Make sure tenant support is enabled and tenant isolation is disabled.
     *
     * @Given /^tenant support is enabled without tenant isolation$/
     */
    public function tenant_support_is_enabled_without_isolation() {
        global $CFG;
        if (!$CFG->tenantsenabled) {
            set_config('tenantsenabled', 1);
            totara_tenant\local\util::check_roles_exist();
        }
        if ($CFG->tenantsisolated) {
            set_config('tenantsisolated', 0);
        }
    }

    /**
     * Make sure tenant support is enabled with full tenant isolation is also enabled.
     *
     * @Given /^tenant support is enabled with full tenant isolation$/
     */
    public function tenant_support_is_enabled_with_isolation() {
        global $CFG;
        if (!$CFG->tenantsenabled) {
            set_config('tenantsenabled', 1);
            totara_tenant\local\util::check_roles_exist();
        }
        if (!$CFG->tenantsisolated) {
            set_config('tenantsisolated', 1);
        }
    }
}
