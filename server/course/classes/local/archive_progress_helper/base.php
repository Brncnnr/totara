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
 * @package core_course
 */

namespace core_course\local\archive_progress_helper;

use context_helper;
use context_program;
use core_course\local\archive_progress_helper\output\page_output;
use core_course\local\archive_progress_helper\output\validator\request_validator;
use stdClass;
use totara_core\event\user_course_progress_archived;

/**
 * Archive progress helper base class.
 *
 * Facilitates archiving activity progress and course and activity completion state.
 * The factory class is used to get an appropriate instance of a class extending base.
 *
 * @internal
 */
abstract class base {

    /**
     * The course we are archiving in
     * @var stdClass
     */
    protected $course;

    /**
     * The names of linked programs certifications, or null if it hasn't been resolved yet.
     *
     * @var array[]
     *
     */
    private $linked_programs_and_certifications;

    /**
     * Returns the reason that the user cannot archive completion. Null if they can.
     *
     * @return string|null
     */
    public function get_unable_to_archive_reason(): ?string {
        global $CFG;

        if (empty($CFG->enablecompletion)) {
            return 'Completion is not enabled for the site';
        }
        if (empty($this->course->enablecompletion)) {
            return 'Completion is not enabled for the course';
        }

        return null;
    }

    /**
     * Archive the course progress and completion state.
     */
    abstract public function archive_and_reset(): void;

    /**
     * Resets activity and course progress and completion state for the given user + course.
     *
     * @param stdClass $course
     * @param int $user_id
     *
     * @return void
     */
    protected function reset_user_progress(stdClass $course, int $user_id): void {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/completion/completion_completion.php');

        // Archive the course completion record before the activities to get the grade
        archive_course_completion($user_id, $course->id);
        archive_course_activities($user_id, $course->id);

        // Purge any leftovers.
        archive_course_purge_gradebook($user_id, $course->id);

        // Trigger an event to ensure that this is logged.
        user_course_progress_archived::create_from_course($course, $user_id)->trigger();
    }

    /**
     * Get page output handler.
     *
     * @return page_output
     */
    abstract public function get_page_output(): page_output;

    /**
     * Gets validator used in validating requests.
     *
     * @return request_validator
     */
    abstract public function get_validator(): request_validator;

    /**
     * Returns any programs and certifications that the course is used within.
     *
     * @return array Program names, and then certification names as strings, already formatted and considered safe HTML.
     */
    protected function get_linked_progs_and_certs(): array {
        $this->ensure_linked_progs_certs_loaded();

        return $this->linked_programs_and_certifications;
    }

    /**
     * Resolves all programs and certifications the associated course is used within.
     *
     * @return void
     */
    private function ensure_linked_progs_certs_loaded(): void {
        if (!is_null($this->linked_programs_and_certifications)) {
            return;
        }
        $programs_and_certifications = data_repository::get_linked_programs_and_certifications_names($this->course->id);

        $program_names = [];
        $certification_names = [];

        /** @var object $instance*/
        foreach ($programs_and_certifications as $instance) {
            // Preload the context - one less database query per program/certification
            context_helper::preload_from_record($instance);
            $name = format_string($instance->fullname, true, ['context' => context_program::instance($instance->id)]);
            if (!empty($instance->certifid)) {
                $certification_names[$instance->id] = $name;
            } else {
                $program_names[$instance->id] = $name;
            }
        }

        $this->linked_programs_and_certifications = [
            'programs' => $program_names,
            'certifications' => $certification_names,
        ];

    }
}