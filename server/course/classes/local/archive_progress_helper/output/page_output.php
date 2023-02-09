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
use core_course\output\archive_progress_confirmation_renderer;
use core_course\output\archive_progress_error_renderer;
use moodle_page;
use moodle_url;
use stdClass;

/**
 * Handles rendering the confirmation page or error page when it's not possible to perform the action.
 */
abstract class page_output {

    /**
     * Confirmation renderer.
     */
    private const CONFIRMATION_RENDERER_TYPE = 'archive_progress_confirmation';

    /**
     *
     */
    private const ERROR_RENDERER_TYPE = 'archive_progress_error';

    /**
     * Can the course progress be reset. Used for deciding which template to use.
     *
     * @var bool
     */
    private $confirming_reset;

    /**
     * Course object.
     *
     * @var stdClass
     */
    protected $course;

    /**
     * Instance of request validator.
     *
     * @var request_validator
     */
    private $request_validator;

    /**
     * @param bool $confirming_reset
     * @param stdClass $course
     * @param request_validator $request_validator
     */
    public function __construct(bool $confirming_reset, stdClass $course, request_validator $request_validator) {
        $this->confirming_reset = $confirming_reset;
        $this->course = $course;
        $this->request_validator = $request_validator;
    }

    /**
     * Sets up the page url and layout.
     *
     * @param moodle_page $page
     */
    abstract protected function set_page_url(moodle_page $page): void;

    /**
     * Returns the URL to the archive completions page.
     *
     * @return moodle_url
     */
    final public function get_archive_completion_url(): moodle_url {
        $params = $this->get_archive_completion_url_params();

        return new moodle_url('/course/archivecompletions.php', $params);
    }

    /**
     * Archive completion url parameters.
     *
     * @return array
     */
    abstract protected function get_archive_completion_url_params(): array;

    /**
     * Returns the URL used to confirm the action.
     *
     * @return moodle_url
     */
    protected function get_confirmation_url(): moodle_url {
        $url = $this->get_archive_completion_url();
        $url->param('sesskey', sesskey());
        $this->request_validator->add_secret_as_param($url);

        return $url;
    }

    /**
     * Renders the archive progress page.
     *
     * @param moodle_page $page
     * @return string
     */
    public function render(moodle_page $page): string {
        // Set up the page.
        $this->set_page_title_and_heading($page);
        $this->set_page_url($page);

        // Render the page.
        /** @var archive_progress_confirmation_renderer|archive_progress_error_renderer $renderer */
        if ($this->confirming_reset) {
            $renderer = $page->get_renderer('core_course', self::CONFIRMATION_RENDERER_TYPE);
            $page_properties = $this->get_confirmation_page_context();
        } else {
            $renderer = $page->get_renderer('core_course', self::ERROR_RENDERER_TYPE);
            $page_properties = $this->get_error_page_context();
        }

        $html = $renderer->header();
        $html .= $renderer->page($page_properties);
        $html .= $renderer->footer();

        return $html;
    }

    /**
     * Page context for course reset confirmation.
     *
     * @return confirmation_page
     */
    abstract protected function get_confirmation_page_context(): confirmation_page;

    /**
     * Page context when there's an error preventing course reset.
     *
     * @return error_page
     */
    abstract protected function get_error_page_context(): error_page;

    /**
     * Get success page properties.
     *
     * @return success_page
     */
    abstract public function get_success_page_context(): success_page;

    /**
     * Set page title and heading.
     *
     * @param moodle_page $page
     */
    private function set_page_title_and_heading(moodle_page $page): void {
        $course_context = context_course::instance($this->course->id);
        $course_fullname = format_string($this->course->fullname, true, ['context' => $course_context]);
        $page->set_title($course_fullname);
        $page->set_heading($course_fullname);
    }
}
