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

use context_course;
use core_course\local\archive_progress_helper\output\completed_users as completed_users_page_output;
use core_course\local\archive_progress_helper\output\page_output;
use core_course\local\archive_progress_helper\output\validator\completed_users as completed_users_validator;
use core_course\local\archive_progress_helper\output\validator\request_validator;
use stdClass;
use totara_core\event\course_completion_archived;

/**
 * Helper to aid in archiving and resetting progress and completion state for all completed users in the course.
 *
 * @internal
 */
final class completed_users extends base {

    /**
     * Number of user course completions we reset.
     * @var int
     */
    private $user_completions_reverted = 0;

    /**
     * Constructor.
     *
     * You can instantiate a specific instance, however we recommend using the factory.
     *
     * @param stdClass $course The course we are archiving for.
     */
    public function __construct(stdClass $course) {
        $this->course = $course;
    }

    /**
     * @inheritDoc
     */
    public function get_unable_to_archive_reason(): ?string {
        $reason = parent::get_unable_to_archive_reason();

        if (!is_null($reason)) {
            return $reason;
        }
        $context = context_course::instance($this->course->id);

        $capability = 'totara/core:archiveenrolledcourseprogress';
        if (!has_capability($capability, $context)) {
            return 'Missing capability: ' . $capability;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function archive_and_reset(): void {
        $userids = data_repository::get_course_completed_users($this->course->id);

        foreach ($userids as $userid) {
            $this->reset_user_progress($this->course, $userid);
            $this->increment_completions_reverted_count();
        }
        course_completion_archived::create_from_course($this->course)->trigger();
    }

    /**
     * Increments the user completions reverted.
     *
     * @return void
     */
    private function increment_completions_reverted_count(): void {
        $this->user_completions_reverted++;
    }

    /**
     * @inheritDoc
     */
    public function get_validator(): request_validator {
        return new completed_users_validator($this->course);
    }

    /**
     * @inheritDoc
     */
    public function get_page_output(): page_output {
        return new completed_users_page_output(
            data_repository::get_course_completed_users_count($this->course->id) > 0,
            $this->course,
            $this->get_validator(),
            $this->get_linked_progs_and_certs(),
            $this->get_and_reset_user_completions_reverted()
        );
    }

    /**
     * Get the user completions reverted and resets the count.
     * @return int
     */
    private function get_and_reset_user_completions_reverted(): int {
        $user_completions_reverted = $this->user_completions_reverted;
        $this->user_completions_reverted = 0;

        return $user_completions_reverted;
    }
}