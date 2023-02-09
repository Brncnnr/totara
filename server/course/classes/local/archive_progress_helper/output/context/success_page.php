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
 * Page properties used after successfully archiving course progress.
 */
class success_page {

    /**
     * @var moodle_url
     */
    private $redirect_url;

    /**
     * @var string
     */
    private $success_message;

    /**
     * @param moodle_url $redirect_url
     * @param string $success_message
     */
    public function __construct(moodle_url $redirect_url, string $success_message) {
        $this->redirect_url = $redirect_url;
        $this->success_message = $success_message;
    }

    /**
     * Get redirect url when course progress is successfully archived.
     *
     * @return moodle_url
     */
    public function redirect_url(): moodle_url {
        return $this->redirect_url;
    }

    /**
     * Get success message.
     *
     * @return string
     */
    public function message(): string {
        return $this->success_message;
    }
}