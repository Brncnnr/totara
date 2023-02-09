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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\exception;

use moodle_exception;

/**
 * Access denied.
 */
class access_denied_exception extends moodle_exception {
    /**
     * @param string $type
     * @param string|null $debug_info additional debug information
     */
    private function __construct(string $type, string $debug_info = null) {
        parent::__construct("access_check_error:{$type}", 'mod_approval', '', null, $debug_info);
    }

    /**
     * Create an exception for a workflow assignment.
     *
     * @param string $debug_info
     * @return self
     */
    public static function assignment(string $debug_info = null): self {
        return new self('assignment', $debug_info);
    }

    /**
     * Create an exception for an application.
     *
     * @param string $debug_info
     * @return self
     */
    public static function application(string $debug_info = null): self {
        return new self('application', $debug_info);
    }

    /**
     * Create an exception for an application submission.
     *
     * @param string $debug_info
     * @return self
     */
    public static function submission(string $debug_info = null): self {
        return new self('application_submission', $debug_info);
    }

    /**
     * Create an exception for a workflow.
     *
     * @param string $debug_info
     * @return self
     */
    public static function workflow(string $debug_info = null): self {
        return new self('workflow', $debug_info);
    }

    /**
     * Create an exception for a manage_workflows.
     *
     * @param string $debug_info
     * @return self
     */
    public static function manage_workflows(string $debug_info = null): self {
        return new self('manage_workflows', $debug_info);
    }
}
