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

use completion_info;
use context_course;
use core_course\local\archive_progress_helper\output\current_user as current_user_page_output;
use core_course\local\archive_progress_helper\output\page_output;
use core_course\local\archive_progress_helper\output\validator\request_validator;
use core_course\local\archive_progress_helper\output\validator\single_user as single_user_validator;
use stdClass;

/**
 * Helper to aid in archiving and resetting progress and completion state for the current user.
 *
 * @internal
 */
final class current_user extends base {

    /**
     * The logged-in user we are archiving for.
     *
     * @var stdClass
     */
    protected $user;

    /**
     * Constructor.
     *
     * @param stdClass $course The course we are archiving for.
     */
    public function __construct(stdClass $course) {
        global $USER;
        $this->course = $course;
        $this->user =  $USER;
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

        if (isguestuser($this->user)) {
            return 'Guest users cannot archive their progress.';
        }
        $capability = 'totara/core:archivemycourseprogress';

        if (!has_capability($capability, $context)) {
            return 'Missing capability: ' . $capability;
        }

        $info = new completion_info($this->course);
        if (!$info->is_tracked_user($this->user->id)) {
            return 'User does not have a completion tracked role';
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function archive_and_reset(): void {
        $this->reset_user_progress($this->course, $this->user->id);
    }

    /**
     * @inheritDoc
     */
    public function get_validator(): request_validator {
        return new single_user_validator($this->course, $this->user->id);
    }

    /**
     * @inheritDoc
     */
    public function get_page_output(): page_output {
        return new current_user_page_output(
            data_repository::user_has_progress($this->course->id, $this->user->id),
            $this->course,
            $this->get_validator(),
            $this->get_linked_progs_and_certs(),
            $this->user
        );
    }
}
