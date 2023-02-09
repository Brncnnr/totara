<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\event;

use coding_exception;
use context_course;
use core\event\base;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Fired when a user's course progress and completion gets reset.
 */
class user_course_progress_archived extends base {
    /**
     * Create instance of event.
     *
     * @param stdClass $course
     * @param int $userid
     * @return user_course_progress_archived|base
     */
    public static function create_from_course(stdClass $course, int $userid): self {
        $data = array(
            'context' => context_course::instance($course->id),
            'objectid' => $course->id,
            'relateduserid' => $userid,
        );
        $event = self::create($data);
        $event->add_record_snapshot('course', $course);

        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'course';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventuserprogressarchived', 'core_completion');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description(): string {
        return "User {$this->userid} archived progress of user {$this->relateduserid} in course {$this->courseid}";
    }

    /**
     * Returns relevant URL.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        return new moodle_url('/course/archivecompletions.php', ['id' => $this->courseid, 'userid' => $this->relateduserid]);
    }

    /**
     * Validate the event has all necessary data.
     *
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new coding_exception('The \'relateduserid\' must be set.');
        }
    }
}
