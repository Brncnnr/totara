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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_completioneditor
 */

namespace totara_completioneditor\event;

use core\event\base;

defined('MOODLE_INTERNAL') || die();

/**
 * Course completion for a course and user got updated.
 * This is relevant when someone manually edits a completion record in the completion editor
 */
class course_completion_edited extends base {

    /**
     * Create event from course_completion record.
     * @param \stdClass $completion
     * @return course_completion_edited
     */
    public static function create_for_completion_record(\stdClass $completion) {
        $event = self::create(
            array(
                'objectid' => $completion->id,
                'relateduserid' => $completion->userid,
                'context' => \context_course::instance($completion->course),
                'courseid' => $completion->course,
            )
        );
        $event->add_record_snapshot('course_completions', $completion);
        return $event;
    }

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['objecttable'] = 'course_completions';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_course_completion_edited', 'totara_completioneditor');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The completion record for user with id '$this->relateduserid' and course '$this->courseid' got edited.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url(
            '/totara/completioneditor/edit_course_completion.php',
            array('courseid' => $this->courseid, 'userid' => $this->relateduserid)
        );
    }

    /**
     * Return name of the legacy event, which is replaced by this event.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'course_completion_edited';
    }

    /**
     * Return course_completed legacy event data.
     *
     * @return \stdClass completion data.
     */
    protected function get_legacy_eventdata() {
        return $this->get_record_snapshot('course_completions', $this->objectid);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
    }

    public static function get_objectid_mapping() {
        // Sorry - there is no mapping available for completion records.
        return array('db' => 'course_completions', 'restore' => base::NOT_MAPPED);
    }

    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['relateduserid'] = array('db' => 'user', 'restore' => 'user');
        return $othermapped;
    }
}
