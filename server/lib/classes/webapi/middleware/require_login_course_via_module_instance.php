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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * Use this middleware if your request contains an instance of a module.
 * This middleware will automatically try to resolve the course and set
 * the correct parameters for require login.
 *
 * If the course id is not correctly set, means empty or not an existing course an exception will be thrown.
 */
class require_login_course_via_module_instance implements middleware {

    /**
     * @var string
     */
    protected $mod_type;

    /**
     * @var string
     */
    protected $instanceid_argument_name;

    protected $auto_login_guest = false;

    /**
     * @param string $modtype the module type to go with the instance, e.g. quiz or assignment
     * @param string $cmid_argument_name the argument name for the course id
     * @param bool $auto_login_guest
     */
    public function __construct(string $modtype, string $instanceid_argument_name, bool $auto_login_guest = false) {
        $this->mod_type = $modtype;
        $this->instanceid_argument_name = $instanceid_argument_name;
        $this->auto_login_guest = $auto_login_guest;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        global $DB;

        $instanceid = $payload->get_variable($this->instanceid_argument_name);
        if (empty($instanceid)) {
            throw new \moodle_exception('invalidcoursemodule');
        }

        try {
            $cm = get_coursemodule_from_instance($this->mod_type, $instanceid, null, true, MUST_EXIST);
        } catch (\Exception $exception) {
            throw new \moodle_exception('invalidcoursemodule');
        }

        try {
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        } catch (\Exception $exception) {
            throw new \moodle_exception('invalidcourse');
        }

        // Always prevent redirects for GraphQL requests
        // and we do not need to set the wantsurl to the current url
        \require_login($course, $this->auto_login_guest, $cm, false, true);

        $payload->set_variable('cm', $cm);
        $payload->set_variable('course', $course);

        return $next($payload);
    }
}
