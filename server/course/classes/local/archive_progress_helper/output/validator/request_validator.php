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

namespace core_course\local\archive_progress_helper\output\validator;

use moodle_exception;
use moodle_url;

/**
 * This is a base class for generating secret keys and validating them to reset course progress.
 */
abstract class request_validator {

    /**
     * Secret key query parameter name.
     *
     * @var string
     */
    public const SECRET_KEY = 'archive';

    /**
     * Checks if the key is provided matches the generated key.
     * Throws an exception on failure.
     *
     * @throws moodle_exception
     */
    public function validate(string $value): void {
        if ($this->generate_secret() !== $value) {
            throw new moodle_exception(
                'invalidaccess',
                'error',
                '',
                null,
                'Archive confirmation secret does not match expected value.'
            );
        }
    }

    /**
     * Adds secret as param to the url.
     *
     * @param moodle_url $moodle_url
     */
    public function add_secret_as_param(moodle_url $moodle_url): void {
        $moodle_url->param(self::SECRET_KEY, $this->generate_secret());
    }

    /**
     * Generates confirmation secret used for validating requests.
     *
     * @return string
     */
    abstract protected function generate_secret(): string;
}
