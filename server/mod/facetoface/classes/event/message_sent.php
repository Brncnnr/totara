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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a message have been sent.
 */
class message_sent extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /**
     * Create from session.
     *
     * @param \stdClass $session
     * @param \context_module $context
     * @param string $section The section viewed
     * @return attendees_viewed
     */
    public static function create_from_session(\mod_facetoface\seminar_event $seminarevent, \context_module $context, $section) {
        $data = array(
            'context' => $context,
            'other' => array(
                'sessionid' => $seminarevent->get_id(),
                'section' => $section
            )
        );

        self::$preventcreatecall = false;
        /** @var attendees_viewed $event */
        $event = self::create($data);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Init method
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Return localised event name.
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventmessagesent', 'mod_facetoface');
    }

    /**
     * Returns description of what happened.
     * @return string
     */
    public function get_description(): string {
        return "The user with id {$this->userid} sent message to attendees for session with id {$this->other['sessionid']}. ";
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        $params = ['s' => $this->other['sessionid']];
        return new \moodle_url('/mod/facetoface/attendees/messageusers.php', $params);
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
            'message sent',
            "attendees/messageusers.php?s=$s",
            $this->other['sessionid'],
            $this->contextinstanceid
        ];
    }

    /**
     * Custom validation.
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_session() instead.');
        }

        if (!isset($this->other['sessionid'])) {
            throw new \coding_exception('sessionid must be set in $other.');
        }

        parent::validate_data();
    }
}