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

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Class for enabling of multitenancy support
 *
 * NOTE: This is not a public API - do not use in plugins or 3rd party code!
 */
final class totara_tenant_admin_setting_enable extends admin_setting_configcheckbox {
    public function __construct() {
        parent::__construct('tenantsenabled', new lang_string('tenantsenabled', 'totara_tenant'),
            new lang_string('tenantsenabled_desc', 'totara_tenant'), 0);
        $this->set_updatedcallback(['totara_tenant\local\util', 'check_roles_exist']);
    }

    /**
     * Sets the value for the setting
     *
     * Sets the value for the setting to either the yes or no values
     * of the object by comparing $data to yes
     *
     * @param mixed $data Gets converted to str for comparison against yes value
     * @return string empty string or error
     */
    public function write_setting($data) {
        global $DB, $CFG;
        if (!empty($CFG->tenantsenabled)) {
            if ((string)$data !== $this->yes) {
                if ($DB->record_exists('tenant', [])) {
                    return get_string('cannotdisable', 'totara_tenant');
                }
            }
        }
        return parent::write_setting($data);
    }
}
