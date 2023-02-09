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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace core_course\local\archive_progress_helper\output;

use context_course;
use core_course\local\archive_progress_helper\output\context\confirmation_page;
use core_course\local\archive_progress_helper\output\context\error_page;
use core_course\local\archive_progress_helper\output\context\success_page;
use core_course\local\archive_progress_helper\output\validator\request_validator;
use moodle_page;
use moodle_url;
use stdClass;

/**
 * Page output handler for archiving course progress of the current user
 */
class current_user extends page_output {

    /**
     * User.
     *
     * @var stdClass
     */
    private $user;

    /**
     * Linked programs and certifications.
     *
     * @var array
     */
    private $linked_programs_and_certifications;

    /**
     * Constructor.
     *
     * @param bool $confirming_reset
     * @param stdClass $course
     * @param request_validator $request_validator
     * @param array $linked_programs_and_certifications
     * @param stdClass $user
     */
    public function __construct(
        bool $confirming_reset,
        stdClass $course,
        request_validator $request_validator,
        array $linked_programs_and_certifications,
        stdClass $user
    ) {
        $this->user = $user;
        $this->linked_programs_and_certifications = $linked_programs_and_certifications;
        parent::__construct($confirming_reset, $course, $request_validator);
    }

    /**
     * @inheritDoc
     */
    protected function set_page_url(moodle_page $page): void {
        $url = new moodle_url(
            '/course/archivecompletions.php',
            [
                'id' => $this->course->id,
                'userid' => $this->user->id,
            ]
        );
        $page->set_url($url);
    }

    /**
     * @inheritDoc
     */
    protected function get_archive_completion_url_params(): array {
        return  [
            'id' => $this->course->id,
            'userid' => $this->user->id,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function get_confirmation_page_context(): confirmation_page {
        $heading = $this->get_header_text();
        $confirmation_message = markdown_to_html(
            get_string('confirm_archive_current_user_progress', 'completion')
        );

        return new confirmation_page(
            $heading,
            $confirmation_message,
            $this->get_confirmation_url(),
            $this->get_course_url()
        );
    }

    /**
     * @inheritDoc
     */
    protected function get_error_page_context(): error_page {
        return new error_page(
            $this->get_header_text(),
            get_string('archive_current_user_no_completions', 'completion'),
            $this->linked_programs_and_certifications['programs'],
            $this->linked_programs_and_certifications['certifications'],
            $this->get_course_url(),
        );
    }

    /**
     * @inheritDoc
     */
    public function get_success_page_context(): success_page {
        return new success_page(
            $this->get_course_url(),
            get_string('successfully_archived_current_user_progress', 'completion')
        );
    }

    /**
     * Get Page heading.
     *
     * @return string
     */
    private function get_header_text(): string {
        return get_string('archive_current_user_heading', 'completion');
    }

    /**
     * Get the course url.
     *
     * @return moodle_url
     */
    private function get_course_url(): moodle_url {
        return new moodle_url('/course/view.php', ['id' => $this->course->id]);
    }
}