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
 * Role allow override updated event.
 *
 * @package    core
 * @since      Moodle 2.6
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Role allow override updated event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int targetroleid: role that can be overridden by user with role in objectid
 *      - bool allow: true means allowed, false disallowed
 * }
 *
 * @package    core
 * @since      Moodle 2.6
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_allow_override_updated extends base {
    /**
     * Initialise event parameters.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'role'; // Totara: this MUST match the $this->objectid, this is NOT "Affected table" as incorrectly stated in event monitor tool.
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventroleallowoverrideupdated', 'role');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        if ($this->other['allow']) {
            return "The user with id '{$this->userid}' modified the role with id '{$this->objectid}' to allow overriding role '{$this->other['targetroleid']}'";
        } else {
            return "The user with id '{$this->userid}' modified the role with id '{$this->objectid}' to disallow overriding role '{$this->other['targetroleid']}'";
        }
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/roles/define.php', ['action' => 'view', 'roleid' => $this->objectid]);
    }

    /**
     * Returns array of parameters to be passed to legacy add_to_log() function.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return array(SITEID, 'role', 'edit allow override', 'admin/roles/allow.php?mode=override');
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['targetroleid'])) {
            throw new \coding_exception('The \'targetroleid\' value must be set in other.');
        }
        if (!isset($this->other['allow'])) {
            throw new \coding_exception('The \'allow\' value must be set in other.');
        }
    }
}
