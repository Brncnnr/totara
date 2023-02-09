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

namespace core_course\local\archive_progress_helper\output\context;

use moodle_url;

/**
 * Page properties used when unable to perform the archive and reset action.
 */
class error_page {

    /**
     * @var string
     */
    private $heading;

    /**
     * @var string
     */
    private $no_progress_message;

    /**
     * @var array
     */
    private $linked_programs;

    /**
     * @var array
     */
    private $linked_certifications;

    /**
     * Url for the button.
     *
     * @var moodle_url
     */
    private $button_url;

    /**
     * Constructor.
     *
     * @param string $heading
     * @param string $no_progress_message
     * @param array $linked_programs
     * @param array $linked_certifications
     * @param moodle_url $button_url
     */
    public function __construct(
        string $heading,
        string $no_progress_message,
        array $linked_programs,
        array $linked_certifications,
        moodle_url $button_url
    ) {
        $this->heading = $heading;
        $this->no_progress_message = $no_progress_message;
        $this->linked_programs = $linked_programs;
        $this->linked_certifications = $linked_certifications;
        $this->button_url = $button_url;
    }

    /**
     * Get heading.
     *
     * @return string
     */
    public function get_heading(): string {
        return $this->heading;
    }

    /**
     * Get message when there are no linked programs or certifications.
     *
     * @return string
     */
    public function get_no_progress_message(): string {
        return $this->has_linked_programs_or_certifications()
            ? ''
            : $this->no_progress_message;
    }

    /**
     * Are there linked programs or certifications.
     *
     * @return bool
     */
    private function has_linked_programs_or_certifications(): bool {
        return !empty($this->linked_programs) || !empty($this->linked_certifications);
    }

    /**
     * Get data related to programs and certifications linked to the course.
     *
     * @return array
     */
    public function get_linked_programs_and_certifications_data(): array {
        if (!$this->has_linked_programs_or_certifications()) {
            return [];
        }

        return [
            'error' => [
                'message' => get_string('error:cannotarchiveprogcourse', 'completion'),
            ],
            'programs' => [
                'names' => array_values($this->linked_programs),
                'has_conflicts' => !empty($this->linked_programs), // are there conflicts with programs.
            ],
            'certifications' => [
                'names' => array_values($this->linked_certifications),
                'has_conflicts' => !empty($this->linked_certifications), // are there conflicts with certifications.
            ]
        ];
    }

    /**
     * Get button url.
     *
     * @return moodle_url
     */
    public function get_button_url(): moodle_url {
        return $this->button_url;
    }
}