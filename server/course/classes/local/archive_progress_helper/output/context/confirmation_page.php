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
 * Page properties used to render confirmation of archive modal.
 */
class confirmation_page {

    /**
     * @var string
     */
    private $heading;

    /**
     * @var string
     */
    private $message;

    /**
     * @var moodle_url
     */
    private $confirmation_url;

    /**
     * @var moodle_url
     */
    private $cancel_url;

    /**
     * @param string $heading
     * @param string $message
     * @param moodle_url $confirmation_url
     * @param moodle_url $cancel_url
     * @param array $linked_programs
     * @param array $linked_certifications
     */
    public function __construct(
        string $heading,
        string $message,
        moodle_url $confirmation_url,
        moodle_url $cancel_url
    ) {
        $this->heading = $heading;
        $this->message = $message;
        $this->confirmation_url = $confirmation_url;
        $this->cancel_url = $cancel_url;
    }

    /**
     * Get page heading.
     *
     * @return string
     */
    public function get_heading(): string {
        return $this->heading;
    }

    /**
     * Modal confirmation message displayed explaining course reset action.
     *
     * @return string
     */
    public function get_message(): string {
        return $this->message;
    }

    /**
     * Get url to go to when the user confirms course reset.
     *
     * @return moodle_url
     */
    public function get_confirmation_url(): moodle_url {
        return $this->confirmation_url;
    }

    /**
     * Get url to go to when the user cancels course reset.
     *
     * @return moodle_url
     */
    public function get_cancel_url(): moodle_url {
        return $this->cancel_url;
    }
}