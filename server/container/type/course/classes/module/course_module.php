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
namespace container_course\module;

use core\event\course_module_deleted;
use core\event\course_module_updated;
use core\session\manager;
use core\task\manager as task_manager;
use core_availability\tree;
use core_container\cache_helper;
use core_container\module\helper;
use core_container\module\module;
use core_course\task\course_delete_modules;
use totara_core\event\module_completion_unlocked;

/**
 * Module class for container course
 */
class course_module extends module {
    /**
     * @var array
     */
    protected $extra = [];

    /**
     * This is for backward compatibility with the old ways of adding module to a course.
     *
     * @param \stdClass $record
     * @return void
     */
    public function set_extra_fields(\stdClass $record): void {
        $attributes = get_object_vars($record);
        foreach ($attributes as $attribute => $value) {
            if ($this->entity->has_attribute($attribute)) {
                // Skipping those attribute that already existing in the entity.
                continue;
            }

            // If it is not existing in the entity, then add it in the $extra data.
            $this->extra[$attribute] = $value;
        }
    }

    /**
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        $attributes = array_merge(
            $this->entity->to_array(),
            $this->extra
        );

        return (object) $attributes;
    }

    /**
     * @inheritDoc
     * @param \stdClass $data
     * @return \stdClass
     */
    protected function pre_update(\stdClass $data): \stdClass {
        global $CFG;
        require_once("{$CFG->dirroot}/course/lib.php");
        require_once("{$CFG->dirroot}/course/modlib.php");
        require_once("{$CFG->dirroot}/totara/core/lib.php");

        $moduleinfo = parent::pre_update($data);
        $moduleinfo = course_module_helper::set_moduleinfo_defaults($moduleinfo);

        return $moduleinfo;
    }

    /**
     * @param \stdClass $moduleinfo
     * @param null      $mform
     *
     * @return void
     */
    protected function do_update(\stdClass $moduleinfo, $mform = null): void {
        global $CFG;

        $data = new \stdClass();
        if ($mform) {
            $data = $mform->get_data();
        }

        if (property_exists($moduleinfo, 'groupmode')) {
            $this->entity->groupmode = $moduleinfo->groupmode;
        }

        if (isset($moduleinfo->groupingid)) {
            $this->entity->groupingid = $moduleinfo->groupingid;
        }

        $container = $this->get_container();
        $course = $container->to_record();

        $completion = new \completion_info($course);
        if ($completion->is_enabled()) {
            // Completion settings that would affect users who have already completed
            // the activity may be locked; if so, these should not be updated.

            if (!empty($moduleinfo->completionunlocked)) {
                $this->entity->completion = $moduleinfo->completion;
                $this->entity->completiongradeitemnumber = $moduleinfo->completiongradeitemnumber;
                $this->entity->completionview = $moduleinfo->completionview;

                if (!empty($moduleinfo->completionunlockednoreset)) {
                    // TOTARA - Trigger module_completion_unlocked event here.
                    $event = module_completion_unlocked::create_from_module($moduleinfo);
                    $event->trigger();
                }
            }

            // The expected date does not affect users who have completed the activity,
            // so it is safe to update it regardless of the lock status.
            $this->entity->completionexpected = $moduleinfo->completionexpected;
        }

        if (!empty($CFG->enableavailability)) {
            // This code is used both when submitting the form, which uses a long
            // name to avoid clashes, and by unit test code which uses the real
            // name in the table.
            if (property_exists($moduleinfo, 'availabilityconditionsjson')) {
                if ($moduleinfo->availabilityconditionsjson !== '') {
                    $this->entity->availability = $moduleinfo->availabilityconditionsjson;
                } else {
                    $this->entity->availability = null;
                }
            } else if (property_exists($moduleinfo, 'availability')) {
                $this->entity->availability = $moduleinfo->availability;
            }

            // If there is any availability data, verify it.
            if (isset($this->entity->availability)) {
                $tree = new tree(json_decode($this->entity->availability));
                // Save time and database space by setting null if the only data
                // is an empty tree.

                if ($tree->is_empty()) {
                    $this->entity->availability = null;
                }
            }
        }

        if (isset($moduleinfo->showdescription)) {
            $this->entity->showdescription = $moduleinfo->showdescription;
        } else {
            $this->entity->showdescription = 0;
        }

        // Start writing to the DB for table course_module
        $this->entity->save();

        // TOTARA performance improvement - invalidate static caching of course information.
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_course.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

        \completion_criteria_activity::invalidatecache();
        \completion_criteria_course::invalidatecache();

        $modulename = $this->get_modulename();
        $modcontext = \context_module::instance($this->entity->id);

        // Update embedded links and save files.
        if (plugin_supports('mod', $modulename, FEATURE_MOD_INTRO, true)) {
            $moduleinfo->intro = file_save_draft_area_files(
                $moduleinfo->introeditor['itemid'],
                $modcontext->id,
                "mod_{$modulename}",
                'intro',
                0,
                ['subdirs' => true],
                $moduleinfo->introeditor['text']
            );

            $moduleinfo->introformat = $moduleinfo->introeditor['format'];
            unset($moduleinfo->introeditor);
        }

        // Get the a copy of the grade_item before it is modified incase we need to scale the grades.
        $oldgradeitem = null;
        $newgradeitem = null;

        if (!empty($data->grade_rescalegrades) && $data->grade_rescalegrades == 'yes') {
            // Fetch the grade item before it is updated.
            $oldgradeitem = \grade_item::fetch(
                [
                    'itemtype' => 'mod',
                    'itemmodule' => $modulename,
                    'iteminstance' => $this->entity->instance,
                    'itemnumber' => 0,
                    'courseid' => $this->entity->course
                ]
            );
        }

        // Start calling to the module function of updating instance.
        $fn = "{$modulename}_update_instance";
        if (!function_exists($fn)) {
            throw new \coding_exception("No module function '{$fn}'");
        }

        $result = call_user_func_array($fn, [$moduleinfo, $mform]);
        if (!$result) {
            throw new \coding_exception("Cannot update module '{$modulename}'");
        }

        $cm = $this->get_cm_record(true);

        // This needs to happen AFTER the grademin/grademax have already been updated.
        if (!empty($data->grade_rescalegrades) && $data->grade_rescalegrades == 'yes') {
            // Get the grade_item after the update call the activity to scale the grades.
            $newgradeitem = \grade_item::fetch(
                [
                    'itemtype' => 'mod',
                    'itemmodule' => $modulename,
                    'iteminstance' => $this->entity->instance,
                    'itemnumber' => 0,
                    'courseid' => $this->entity->course
                ]
            );


            if ($newgradeitem && $oldgradeitem->gradetype == GRADE_TYPE_VALUE &&
                $newgradeitem->gradetype == GRADE_TYPE_VALUE) {
                $params = [
                    $course,
                    $cm,
                    $oldgradeitem->grademin,
                    $oldgradeitem->grademax,
                    $newgradeitem->grademin,
                    $newgradeitem->grademax
                ];

                if (!component_callback("mod_{$modulename}", 'rescale_activity_grades', $params)) {
                    throw new \coding_exception("Cannot process on grades of the module '{$modulename}'");
                }
            }
        }

        // Make sure visibility is set correctly (in particular in calendar).
        if (has_capability('moodle/course:activityvisibility', $modcontext)) {
            $this->update_visible((int) $moduleinfo->visible);
        }

        if (isset($moduleinfo->cmidnumber)) {
            // Label. Set cm idnumber - uniqueness is already verified by form validation.
            $this->update_id_number($moduleinfo->cmidnumber);
        }

        // Update module tags.
        if (\core_tag_tag::is_enabled('core', 'course_modules') && isset($moduleinfo->tags)) {
            \core_tag_tag::set_item_tags(
                'core',
                'course_modules',
                $this->entity->id,
                $modcontext,
                $moduleinfo->tags
            );
        }

        // Now that module is fully updated, also update completion data if required.
        // TOTARA - the function below allows for unlock completion with delete.
        totara_core_update_module_completion_data($cm, $moduleinfo, $course, $completion);
        $this->set_extra_fields($moduleinfo);
    }

    /**
     * @param \stdClass $data
     * @return void
     */
    protected function post_update(\stdClass $data): void {
        $cm = $this->get_cm_record(true);

        $context = $this->get_context();
        $cm->name = $data->name;

        $event = course_module_updated::create_from_cm($cm, $context);
        $event->trigger();

        $container = $this->get_container();
        $course = $container->to_record();

        $data = edit_module_post_actions($data, $course);
        parent::post_update($data);
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function do_update_name(string $name): bool {
        global $DB, $CFG;
        require_once("{$CFG->libdir}/gradelib.php");

        $result = parent::do_update_name($name);
        if (!$result) {
            return false;
        }

        $cm = $this->get_cm_record(true);

        $event = course_module_updated::create_from_cm($cm);
        $event->trigger();

        cache_helper::rebuild_container_cache($this->entity->course, true);

        // Attempt to update the grade item if relevant;
        $grademodule = $DB->get_record($cm->modname, ['id' => $this->entity->instance]);
        $grademodule->cmidnumber = $this->entity->idnumber;
        $grademodule->modname = $cm->modname;

        grade_update_mod_grades($grademodule);
        return true;
    }

    /**
     * @param int $visible
     * @return bool
     */
    public function update_visible(int $visible): bool {
        global $CFG, $DB;

        $result = parent::update_visible($visible);
        if (!$result) {
            // If it is failed, then no point to do the rest of the code.
            return $result;
        }

        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->dirroot.'/calendar/lib.php');

        // Note: No point to check the module-name, because it should have been checked at the parent level.
        $modulename = $this->get_modulename();

        // Create events and propagate visibility to associated grade items if the value has changed.
        // Only do this if it's changed to avoid accidently overwriting manual showing/hiding of student grades.
        $events = $DB->get_records(
            'event',
            [
                'instance' => $this->get_instance(),
                'modulename' => $modulename
            ]
        );

        if (!empty($events)) {
            foreach ($events as $event) {
                $event = new \calendar_event($event);
                $event->toggle_visibility((bool) $visible);
            }
        }

        // Hide the associated grade items so the teacher doesn't also have to go to the gradebook and hide them there.
        // Note that this must be done after updating the row in course_modules, in case
        // the modules grade_item_update function needs to access $this->visible.

        $feature = FEATURE_CONTROLS_GRADE_VISIBILITY;
        $mod = "mod_{$modulename}";

        if (plugin_supports('mod', $modulename, $feature) && component_callback_exists($mod, 'grade_item_update')) {
            $instance = $DB->get_record($modulename, ['id' => $this->entity->instance], '*', MUST_EXIST);

            component_callback($mod, 'grade_item_update', [$instance]);
        } else {
            $gradeitems = \grade_item::fetch_all(
                [
                    'itemtype' => 'mod',
                    'itemmodule' => $modulename,
                    'iteminstance' => $this->entity->instance,
                    'courseid' => $this->entity->course
                ]
            );

            if ($gradeitems) {
                foreach ($gradeitems as $gradeitem) {
                    $gradeitem->set_hidden(!$visible);
                }
            }
        }

        // Reload the module and rebuild the cache.
        $this->reload();
        cache_helper::rebuild_container_cache($this->entity->course, true);
        return true;
    }

    /**
     * @param bool $async
     * @return bool
     */
    public function delete(bool $async = false): bool {
        global $CFG, $DB;

        if ($async) {
            // Check the 'course_module_background_deletion_recommended' hook first. Only use asynchronous deletion if
            // at least one plugin returns true and if async deletion has been requested. Both are checked because
            // plugins should not be allowed to dictate the deletion behaviour, only support/decline it.
            // It's up to plugins to handle things like whether or not they are enabled.
            $pluginsfunction = get_plugins_with_function('course_module_background_deletion_recommended');

            if (!empty($pluginsfunction)) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $fn) {
                        $rs = call_user_func($fn);

                        if ($rs) {
                            return $this->async_delete();
                        }
                    }
                }
            }
        }

        require_once("{$CFG->libdir}/gradelib.php");
        require_once("{$CFG->libdir}/questionlib.php");
        require_once("{$CFG->dirroot}/blog/lib.php");
        require_once("{$CFG->dirroot}/calendar/lib.php");

        $cm = $this->to_record();

        $modcontext = $this->get_context();
        $modulename = $this->get_modulename(true);

        helper::include_modulelib($modulename);
        $deleteinstancefunction = "{$modulename}_delete_instance";

        // Ensure the delete_instance function exists for this module.
        if (!function_exists($deleteinstancefunction)) {
            throw new \coding_exception(
                "Cannot delete this module as the function {$modulename}_delete_instance is missing ".
                "in mod/{$modulename}/lib.php."
            );
        }

        // Allow plugins to use this course module before we completely delete it.
        if ($pluginsfunction = get_plugins_with_function('pre_course_module_delete')) {
            foreach ($pluginsfunction as $plugintype => $plugins) {
                foreach ($plugins as $pluginfunction) {
                    call_user_func_array($pluginfunction, [$cm]);
                }
            }
        }

        // Delete activity context questions and question categories.
        question_delete_activity($cm);

        // TL-21617: If instance = 0, skip this step as there is no module instance.
        if ($this->entity->instance != 0) {
            // Call the delete_instance function, if it returns false throw an exception.
            $result = call_user_func_array($deleteinstancefunction, [$this->entity->instance]);
            if (!$result) {
                throw new \coding_exception("Cannot delete the module '{$modulename}' instance");
            }
        }

        // Remove all module files in case modules forget to do that.
        $fs = get_file_storage();
        $fs->delete_area_files($modcontext->id);

        $events = $DB->get_records(
            'event',
            [
                'instance' => $this->entity->instance,
                'modulename' => $modulename
            ]
        );

        // Delete events from calendar.
        if (!empty($events)) {
            $coursecontext = \context_course::instance($this->entity->course);

            foreach ($events as $event) {
                $event->context = $coursecontext;

                $calendarevent = \calendar_event::load($event);
                $calendarevent->delete();
            }
        }

        // Delete grade items, outcome items and grades attached to modules.
        $gradeitems = \grade_item::fetch_all(
            [
                'itemtype' => 'mod',
                'itemmodule' => $modulename,
                'iteminstance' => $this->entity->instance,
                'courseid' => $this->entity->course
            ]
        );

        if (!empty($gradeitems)) {
            foreach ($gradeitems as $gradeitem) {
                $gradeitem->delete('moddelete');
            }
        }

        // For the completion
        require_once("{$CFG->dirroot}/completion/criteria/completion_criteria.php");

        // Delete associated blogs and blog tag instances.
        blog_remove_associations_for_module($modcontext->id);

        // Delete completion and availability data; it is better to do this even if the
        // features are not turned on, in case they were turned on previously (these will be
        // very quick on an empty table).
        $DB->delete_records('course_modules_completion', ['coursemoduleid' => $this->entity->id]);
        $DB->delete_records(
            'course_completion_criteria',
            [
                'moduleinstance' => $cm->id,
                'course' => $cm->course,
                'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY
            ]
        );

        // Delete all tag instances associated with the instance of this module.
        \core_tag_tag::delete_instances("mod_{$modulename}", null, $modcontext->id);
        \core_tag_tag::remove_all_item_tags('core', 'course_modules', $this->entity->id);

        // Delete the context.
        \context_helper::delete_instance(CONTEXT_MODULE, $this->entity->id);

        // Delete module from the section
        $section = $this->get_section();
        $result = $section->remove_module($this->entity->id);

        if (!$result) {
            throw new \coding_exception("Cannot delete the module {$modulename} (instance) from section.");
        }

        // Delete the module from the course_modules table.
        $DB->delete_records('course_modules', ['id' => $this->entity->id]);

        $event = course_module_deleted::create(
            [
                'courseid' => $this->entity->course,
                'context' => $modcontext,
                'objectid' => $this->entity->id,
                'other' => [
                    'modulename' => $modulename,
                    'instanceid' => $this->entity->instance
                ]
            ]
        );

        $event->add_record_snapshot('course_modules', $cm);
        $event->trigger();

        cache_helper::rebuild_container_cache($this->entity->course, true);
        return true;
    }

    /**
     * @return bool
     */
    public function async_delete(): bool {
        global $USER;
        $result = parent::async_delete();

        if (!$result) {
            // Most likely that the cm record was not found.
            return false;
        }

        $cm = $this->get_cm_record();

        $task = new course_delete_modules();
        $task->set_custom_data(
            [
                'cms' => [$cm],
                'userid' => $USER->id,
                'realuserid' => manager::get_realuser()->id
            ]
        );

        // Queue the task for the next run.
        task_manager::queue_adhoc_task($task);
        cache_helper::rebuild_container_cache($this->entity->course, true);

        return true;
    }

    /**
     * @param \stdClass $data
     * @return module
     */
    public static function create(\stdClass $data): module {
        // Do NOT modify the original data.
        $data = fullclone($data);

        // Note: this is for backward compatibility
        if (property_exists($data, 'id')) {
            unset($data->id);
        }

        return parent::create($data);
    }

    /**
     * @param int $section_number
     * @param int|null $before_mod
     *
     * @return bool
     */
    public function add_to_section(int $section_number, int $before_mod = null): bool {
        global $CFG;

        $container = $this->get_container();
        $section = $container->get_section($section_number, false);

        if (null === $section) {
            if (!function_exists('course_create_sections_if_missing')) {
                require_once("{$CFG->dirroot}/course/lib.php");
            }

            course_create_sections_if_missing($container->get_id(), $section_number);
            $section = $container->get_section($section_number, true);
        }

        $result = $section->add_cm($this->entity->id, $before_mod);
        if (!$result) {
            return false;
        }

        $section_id = $section->get_id();

        $this->entity->section = $section_id;
        $this->entity->save();

        return true;
    }
}