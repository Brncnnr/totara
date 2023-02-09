<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event signalling change of role capability or override.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - string capability: capability name
 *      - int oldpermission: previous permission value
 *      - int permission: new permission value
 * }
 *
 * @since Totara 13.0
 */
class role_capability_updated extends base {
    /**
     * Initialise event parameters.
     */
    protected function init() {
        $this->data['objecttable'] = 'role';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventrolecapabilityupdated', 'role');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $capability = $this->other['capability'];
        $permission = $this->other['permission'];

        if ($this->contextlevel == CONTEXT_SYSTEM) {
            if ($permission == CAP_ALLOW) {
                $description = "The user id '$this->userid' assigned the '$capability' capability to role '$this->objectid'";
            } else if ($permission == CAP_PROHIBIT) {
                $description = "The user id '$this->userid' prohibited the '$capability' capability from role '$this->objectid'";
            } else {
                $description = "The user id '$this->userid' unassigned the '$capability' capability from role '$this->objectid'";
            }
        } else {
            if ($permission == CAP_ALLOW) {
                $description = "The user id '$this->userid' overrode the '$capability' capability with 'Allow' permission for role '$this->objectid'";
            } else if ($permission == CAP_INHERIT) {
                $description = "The user id '$this->userid' removed override for the '$capability' capability for role '$this->objectid'";
            } else if ($permission == CAP_PROHIBIT) {
                $description = "The user id '$this->userid' overrode the '$capability' capability with 'Prohibit' permission for role '$this->objectid'";
            } else {
                $description = "The user id '$this->userid' overrode the '$capability' capability with 'Prevent' permission for role '$this->objectid'";
            }
        }

        return $description;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        if ($this->contextlevel == CONTEXT_SYSTEM) {
            return new \moodle_url('/admin/roles/define.php', ['action' => 'edit', 'roleid' => $this->objectid]);
        } else {
            return new \moodle_url('/admin/roles/override.php', ['contextid' => $this->contextid, 'roleid' => $this->objectid]);
        }
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['capability'])) {
            throw new \coding_exception('The \'objecttable\' value must be set in other.');
        }
        if (!isset($this->other['oldpermission'])) {
            throw new \coding_exception('The \'oldpermission\' value must be set in other.');
        }
        if (!isset($this->other['permission'])) {
            throw new \coding_exception('The \'permission\' value must be set in other.');
        }
    }
}
