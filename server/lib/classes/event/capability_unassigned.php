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
 * Capability unassigned event.
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
 * Capability unassigned event class.
 *
 * @package    core
 * @since      Moodle 3.8
 * @depreacted since Totara 13.0, not triggered at all
 * @copyright  2019 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capability_unassigned extends base {
    /**
     * Initialise event parameters.
     */
    protected function init() {
        $this->data['objecttable'] = 'role'; // Totara: this MUST match the $this->objectid, this is NOT "Affected table" as incorrectly stated in event monitor tool.
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    protected function validate_data() {
        debugging('capability_unassigned event was deprecated and should not be triggered anywhere, it is displayed in historic logs only', DEBUG_DEVELOPER);
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
        return get_string('eventcapabilityunassigned', 'role');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $capability = $this->other['capability'];

        return "The user id id '$this->userid' has unassigned the '$capability' capability for role '$this->objectid'";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        if ($this->contextlevel == CONTEXT_SYSTEM) {
            return new \moodle_url('/admin/roles/define.php', ['action' => 'view', 'roleid' => $this->objectid]);
        } else {
            return new \moodle_url('/admin/roles/override.php', ['contextid' => $this->contextid, 'roleid' => $this->objectid]);
        }
    }
}
