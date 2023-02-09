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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_scorm
 */

namespace mod_scorm\webapi\resolver\query;

use core\webapi\query_resolver;
use core\webapi\execution_context;
use core\webapi\middleware\require_login_course_via_module_instance;
use coding_exception;

class current_status extends query_resolver {
    public static function resolve(array $args, execution_context $ec) {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');

        // Check that scormid has been handed through and that the middleware has provided course info.
        if (empty($args['cm']) || empty($args['course']) || empty($args['scormid'])) {
            throw new \coding_exception('Invalid SCORM request');
        }

        // Note: the id sent through will have to be the instanceid not the cmid.
        $scorm = $DB->get_record('scorm', ['id' => $args['scormid']]);

        if (!$scorm || empty($scorm->course)) {
            throw new \coding_exception('Invalid SCORM request');
        }

        // Get course module and course (provided by middleware)
        $cm = $args['cm'];
        $course = $args['course'];
        $context = \context_module::instance($cm->id);
        $ec->set_relevant_context($context);

        return $scorm;
    }

    public static function get_middleware(): array {
        return [
            new require_login_course_via_module_instance('scorm', 'scormid')
        ];
    }
}
