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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\exception;

/**
 * Exception for model-related errors, state errors, and the like
 *
 * @package mod_approval\exception
 */
class model_exception extends \moodle_exception {

    /**
     * @param string $error_info additional error information for developers
     */
    public function __construct(string $error_info) {
        parent::__construct('model_state_error', 'mod_approval', '', $error_info);
        // Set debuginfo here to avoid getting "Generic failure: Oopsy daisy (Oopsy daisy)" in unit test
        // while keeping backward compatibility with test cases that assert debuginfo.
        $this->debuginfo = $error_info;
    }

}