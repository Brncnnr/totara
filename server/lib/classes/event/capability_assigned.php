<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Capability assigned event.
 *
 * @package    core
 * @since      Moodle 3.8
 * @depreacted since Totara 13.0, not triggered at all
 * @copyright  2019 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Capability assigned event class.
 *
 * @package    core
 * @since      Moodle 3.8
 * @depreacted since Totara 13.0, not triggered at all
 * @copyright  2019 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capability_assigned extends base {
    /**
     * Initialise event parameters.
     */
    protected function init() {
        $this->data['objecttable'] = 'role'; // Totara: this MUST match the $this->objectid, this is NOT "Affected table" as incorrectly stated in event monitor tool.
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    protected function validate_data() {
        debugging('capability_assigned event was deprecated and should not be triggered anywhere, it is displayed in historic logs only', DEBUG_DEVELOPER);
    }

    /**
     * This event has been deprecated.
     *
     * @return boolean
     */
    public static function is_deprecated() {
        return true;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcapabilityassigned', 'role');
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
}
