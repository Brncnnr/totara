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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package core
*/

namespace core\webapi\resolver\mutation;

use coding_exception;
use completion_info;
use container_course\course;
use context_module;
use core\event\course_module_viewed;
use core\webapi\execution_context;
use core\webapi\middleware\require_login_course_via_coursemodule;
use core\webapi\mutation_resolver;
use moodle_exception;

class completion_activity_view extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        global $DB;

        // Unsupported activities. Some activities still have the {module_name}_view function
        // but accept different params, be careful.
        $unsupported = [
            'assign', 'book', 'glossary', 'label', 'perform', 'survey', 'workshop'
        ];
        /**
         * Supported activities:
         * $supported = [
         *  'certificate', 'chat', 'choice', 'data', 'facetoface', 'feedback', 'folder', 'forum', 'imscp',
         *  'lesson', 'lti', 'page', 'quiz', 'resource', 'scorm', 'url', 'wiki'
         * ];
         */

        // Get course module and course (provided by middleware)
        $cm = $args['cm'];
        $course = $args['course'];
        $module_name = $args['activity'];

        $modules = course::get_module_types_supported();
        if (!isset($modules[$module_name])) {
            throw new moodle_exception('moduledoesnotexist', 'error');
        }

        if (in_array($module_name, $unsupported)) {
            return false;
        }

        $class = "\\mod_{$module_name}\\event\\course_module_viewed";
        if (!class_exists($class, true)) {
            return false;
        }

        $module = $DB->get_record($module_name, ['id' => $cm->instance]);
        if (!$module) {
            throw new coding_exception('Specified module could not be found.');
        }
        $context = context_module::instance($cm->id);

        // Trigger events.
        self::module_viewed($module_name, $module, $course, $cm, $context);

        return true;
    }

    public static function get_middleware(): array {
        return [
            new require_login_course_via_coursemodule('cmid')
        ];
    }

    private static function module_viewed($module_name, $module, $course, $cm, $context): void {
        /** @var course_module_viewed $class */
        $class = "\\mod_{$module_name}\\event\\course_module_viewed";

        // Trigger course_module_viewed event.
        $params = [
            'context' => $context,
            'objectid' => $module->id
        ];
        if ($module_name === 'feedback') {
            $params['other'] = ['anonymous' => $module->anonymous];
            $params['anonymous'] = ($module->anonymous == 1);
        }

        $event = $class::create($params);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot($module_name, $module);
        $event->trigger();

        // Completion.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);
    }
}