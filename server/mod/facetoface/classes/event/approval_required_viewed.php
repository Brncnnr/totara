<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when the attendees approval required page is viewed.
 */
class approval_required_viewed extends attendees_viewed {

    /**
     * Return localised event name.
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventapprovalrequiredviewed', 'mod_facetoface');
    }

    /**
     * Returns description of what happened.
     * @return string
     */
    public function get_description(): string {
        return "The user with id {$this->userid} viewed approval required page for session with id {$this->other['sessionid']}";
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        $params = ['s' => $this->other['sessionid']];
        return new \moodle_url('/mod/facetoface/attendees/approvalrequired.php', $params);
    }

    /**
     * Return the legacy event log data.
     * @return array
     */
    public function get_legacy_logdata(): array {
        $s = $this->other['sessionid'];
        return [
            $this->courseid,
            'facetoface',
            'approval required view',
            "attendees/approvalrequired.php?s=$s",
            $this->other['sessionid'],
            $this->contextinstanceid
        ];
    }
}