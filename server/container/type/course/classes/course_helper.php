<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_course
 */
namespace container_course;

use container_course\hook\remove_module_hook;
use container_course\interactor\course_interactor;
use container_course\output\enrolment_banner;
use core_container\factory;
use totara_core\identifier\component_area;
use totara_core\identifier\component_area as ca;
use stdClass;
use renderer_base;
use coding_exception;

final class course_helper {
    /**
     * Keeping track of which module has been warned or not
     * Array<string, int>
     * @var array
     */
    private static $warned;

    /**
     * Preventing any construction on this class.
     * course_helper constructor.
     */
    private function __construct() {
    }

    /**
     * Checking the capability of an actor whether he/she is able to add the module to the course or not.
     *
     * @param string    $modname
     * @param course    $course
     * @param int       $userid
     *
     * @return bool
     */
    public static function is_module_addable(string $modname, course $course, int $userid = 0): bool {
        global $USER;

        if (!$course->is_module_allowed($modname)) {
            return false;
        }

        if (0 == $userid) {
            // Including null check.
            $userid = $USER->id;
        }

        if (!isset(self::$warned)) {
            self::$warned = [];
        }

        $capabilityname = "mod/{$modname}:addinstance";
        $capability = get_capability_info($capabilityname);

        if (!$capability) {
            $archetype = plugin_supports('mod', $modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
            if (!isset(self::$warned[$modname]) && MOD_ARCHETYPE_SYSTEM !== $archetype) {
                // Debug warning that the capability does not exist, but no more than once per page.

                debugging(
                    "The module {$modname} does not define the standard capability '{$capabilityname}'",
                    DEBUG_DEVELOPER
                );

                self::$warned[$modname] = 1;
            }

            // If the capability does not exist, the module can always be added.
            return true;
        }

        $context = $course->get_context();
        return has_capability($capabilityname, $context, $userid);
    }

    /**
     * @param \stdClass $data
     * @param array|null $editoroptions
     *
     * @return course
     */
    public static function create_course(\stdClass $data, ?array $editoroptions = null): course {
        global $CFG, $DB;
        $record = fullclone($data);

        if (!empty($editoroptions)) {
            // summary text is updated later, we need context to store the files first
            $record->summary = '';
            $record->summaryformat = FORMAT_HTML;
        }

        /** @var course $course */
        $course = course::create($data);

        if (!empty($editoroptions)) {
            // Save the files used in the summary editor and store.
            require_once("{$CFG->dirroot}/lib/filelib.php");
            $context = $course->get_context();

            $record = file_postupdate_standard_editor(
                $record,
                'summary',
                $editoroptions,
                $context,
                'course',
                'summary',
                0
            );

            $params = ['id' => $course->id];

            $DB->set_field('course', 'summary', $record->summary, $params);
            $DB->set_field('course', 'summaryformat', $record->summaryformat, $params);

            $course->rebuild_cache(true);
        }

        return $course;
    }

    /**
     * @param int       $courseid
     * @param \stdClass $data
     * @param array|null $editoroptions
     *
     * @return course
     */
    public static function update_course(int $courseid, \stdClass $data, ?array $editoroptions = null): course {
        /** @var course $course */
        $course = factory::from_id($courseid);
        $data = fullclone($data);

        if (!empty($editoroptions)) {
            // Modifying $data with course's summary
            $data = file_postupdate_standard_editor(
                $data,
                'summary',
                $editoroptions,
                $course->get_context(),
                'course',
                'summary',
                0
            );
        }

        $course->update($data);
        return $course;
    }

    /**
     * Returns the list of supported modules within the course.
     *
     * @param bool $plural              If true returns the plural forms of the names.
     * @param bool $include_disabled    If true then all the disabled modules will also be included in the list.
     * @param bool $execute_hook        If false then the hook will not be executed. And the modules that normally
     *                                  removed from hooked will be kept and returned.
     *
     * @param ca|null $component_area   If this component and area is provided, then the hook's watcher is able to identify
     *                                  more of the context around whether it should include or exclude the modules.
     * @return array
     */
    public static function get_all_modules(
        bool $plural = false,
        bool $include_disabled = false,
        bool $execute_hook = true,
        ?ca $component_area = null
    ): array {
        // The list of modules returned from course::get_module_types_supported
        // includes those activity modules that also does not support creation via
        // interfaces.
        $modules = course::get_module_types_supported($plural, $include_disabled);
        if (!$execute_hook) {
            return $modules;
        }

        $hook = new remove_module_hook($modules);

        if (null !== $component_area) {
            $hook->set_component_area($component_area);
        }

        $hook->execute();

        return $hook->get_modules();
    }

    /**
     * @param string $component
     * @param string $area
     * @param bool $plural
     * @param bool $include_disabled
     *
     * @return array
     */
    public static function get_all_modules_with_context_component_area(
        string $component,
        string $area = "",
        bool $plural = false,
        bool $include_disabled = false
    ): array {
        $component_area = new component_area($component, $area);

        return self::get_all_modules(
            $plural,
            $include_disabled,
            true,
            $component_area
        );
    }

    /**
     * A helper method that helps to identify that whether we should render enrolment banner or not.
     *
     * @param course $course
     * @param int|null $user_id
     *
     * @return bool
     */
    public static function should_render_enrolment_banner(course $course, ?int $user_id = null): bool {
        $interactor = new course_interactor($course, $user_id);
        if (!$interactor->can_access() || !$interactor->can_view()) {
            // Nothing to display, because user cannot access nor view it.
            // Hence, no point to render anything.
            return false;
        }

        if ($interactor->is_enrolled()) {
            // When user actor is already enrolled into a course.
            return false;
        }

        return true;
    }

    /**
     * A helper function that would help to conditionally render enrolment banner for
     * the user within a course view.
     *
     * @param renderer_base $renderer
     * @param stdClass      $course
     * @param int|null      $user_id    The user that we would want to check against. If null, the
     *                                  the current user in session will be used.
     *
     * @return string
     */
    public static function render_enrolment_banner(
        renderer_base $renderer,
        stdClass $course,
        ?int $user_id = null
    ): string {
        /** @var course $container */
        $container = factory::from_record($course);
        if (!$container->is_typeof(course::get_type())) {
            throw new coding_exception("Expecting an instance of container course");
        }

        if (!self::should_render_enrolment_banner($container, $user_id)) {
            // Nope, nothing to render, because the current user does not meet any criteria
            // to see the enrolment banner.
            return "";
        }

        $widget = enrolment_banner::create_from_course($container, $user_id);
        return $renderer->render($widget);
    }
}