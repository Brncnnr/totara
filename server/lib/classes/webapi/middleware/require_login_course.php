<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * Use this middleware if your request contains a course id argument.
 * This middleware will automatically try to resolve the course and set
 * the correct parameters for require login.
 *
 * If the course id is not correctly set, means empty or not an existing course an exception will be thrown.
 */
class require_login_course implements middleware {

    /**
     * @var string
     */
    protected $course_id_argument_name;

    protected $auto_login_guest = false;

    /**
     * @param string $course_id_argument_name the argument name for the course id
     * @param bool $auto_login_guest
     */
    public function __construct(string $course_id_argument_name, bool $auto_login_guest = false) {
        $this->course_id_argument_name = $course_id_argument_name;
        $this->auto_login_guest = $auto_login_guest;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        $course_id = $payload->get_variable($this->course_id_argument_name);
        if (empty($course_id)) {
            throw new \moodle_exception('invalidcourse');
        }

        try {
            $course = get_course($course_id);
        } catch (\Exception $exception) {
            throw new \moodle_exception('invalidcourse');
        }

        // Always prevent redirects for GraphQL requests
        // and we do not need to set the wantsurl to the current url
        \require_login($course, $this->auto_login_guest, null, false, true);

        return $next($payload);
    }

}