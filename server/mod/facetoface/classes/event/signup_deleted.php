<?php
/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\event;
use \mod_facetoface\signup;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a user's signup has been deleted.
 */
class signup_deleted extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /** @var signup */
    protected $signup;

    /**
     * Create instance of event.
     *
     * @param signup $signup
     * @param \context_module $context
     * @return self
     */
    public static function create_from_signup(signup $signup, \context_module $context) : self {

        $data = [
            'context' => $context,
            'objectid' => $signup->get_id(),
            'other' => [
                'userid' => $signup->get_userid(),
                'sessionid' => $signup->get_sessionid(),
            ]
        ];

        self::$preventcreatecall = false;
        /** @var signup_deleted $event */
        $event = self::create($data);
        $event->add_record_snapshot('facetoface_signups', $signup->to_record());
        self::$preventcreatecall = true;
        $event->signup = $signup;
        return $event;
    }

    /**
     * Get instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return signup
     */
    public function get_signup() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_signup is intended for event observers only');
        }

        return $this->signup;
    }

    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'facetoface_signups';
    }

    public static function get_name() {
        return get_string('eventsignupdeleted', 'facetoface');
    }

    public function get_description() {
        return "The signup with the id {$this->objectid} was successfully deleted.";
    }

    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_instance() instead.');
        }

        if (!isset($this->other['userid'])) {
            throw new \coding_exception('userid must be set in $other.');
        }

        if (!isset($this->other['sessionid'])) {
            throw new \coding_exception('sessionid must be set in $other.');
        }

        parent::validate_data();
    }
}
