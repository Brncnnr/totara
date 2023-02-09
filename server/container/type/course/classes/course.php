<?php
/**
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

use core_completion\task\update_completion_due_dates_task;
use core_container\container;
use core_container\container_helper;
use core_container\module\helper;
use core_container\module\module;
use container_course\module\course_module;
use container_course\module\course_module_helper;
use core\event\course_created;
use core\event\course_module_created;
use core\event\course_updated;
use core\event\course_deleted;
use stdClass;

/**
 * Container for course
 *
 * @method static course from_record(stdClass $record)
 * @method static course from_id(int $id)
 */
class course extends container {

    /** @var int */
    public const DUEDATEOPERATOR_NONE = 0;
    /** @var int */
    public const DUEDATEOPERATOR_RELATIVE = 1;
    /** @var int */
    public const DUEDATEOPERATOR_FIXED = 2;

    /** @var int */
    public const DUEDATEOFFSETUNIT_MINUTES = 1;
    /** @var int */
    public const DUEDATEOFFSETUNIT_HOURS = 2;
    /** @var int */
    public const DUEDATEOFFSETUNIT_DAYS = 3;
    /** @var int */
    public const DUEDATEOFFSETUNIT_WEEKS = 4;
    /** @var int */
    public const DUEDATEOFFSETUNIT_MONTHS = 5;
    /** @var int */
    public const DUEDATEOFFSETUNIT_YEARS = 6;

    /**
     * @var bool
     */
    private $show_feedback_on_delete;

    /**
     * @var array
     */
    protected $extra;

    /**
     * @return void
     */
    protected function init(): void {
        $this->show_feedback_on_delete = false;
        $this->extra = [];
    }

    /**
     * @param bool $value
     * @return void
     */
    public function show_feedback_on_delete(bool $value = true): void {
        $this->show_feedback_on_delete = $value;
    }

    /**
     * Setter for extra data within containers.
     *
     * @param string        $name
     * @param mixed|null    $value
     *
     * @return void
     */
    public function set_extra(string $name, $value): void {
        $this->extra[$name] = $value;
    }

    /**
     * @param \stdClass $record
     * @return void
     */
    protected function map_record(\stdClass $record): void  {
        global $DB;

        $columns = array_keys($DB->get_columns('course'));

        $attributes = get_object_vars($record);
        $cleaned_attributes = $attributes;

        // Set up the extra fields, so that the map_record from container is not complaining.
        foreach ($attributes as $attribute => $value) {
            if (!in_array($attribute, $columns)) {
                $this->extra[$attribute] = $value;
                unset($cleaned_attributes[$attribute]);
            }
        }

        parent::map_record((object) $cleaned_attributes);
    }

    /**
     * @return void
     */
    public function reload(): void {
        $this->extra = [];
        parent::reload();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool  {
        if (array_key_exists($name, $this->extra)) {
            return isset($this->extra[$name]);
        }

        return parent::__isset($name);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name) {
        if (array_key_exists($name, $this->extra)) {
            return $this->extra[$name];
        }

        return parent::__get($name);
    }

    /**
     * Returning the view url of the current legacy course.
     *
     * @return \moodle_url
     */
    public function get_view_url(): \moodle_url {
        return new \moodle_url("/course/view.php", ['id' => $this->id]);
    }

    /**
     * @param \stdClass $data
     * @return void
     */
    protected static function pre_create(\stdClass $data): void {
        global $CFG;
        require_once("{$CFG->dirroot}/course/lib.php");

        if (!empty($data->shortname) &&
            container_helper::is_container_existing_with_field('shortname', $data->shortname)
        ) {
            throw new \moodle_exception('shortnametaken', 'error', '', $data->shortname);
        }

        if (!empty($data->idnumber) &&
            container_helper::is_container_existing_with_field('idnumber', $data->idnumber)
        ) {
            throw new \moodle_exception('courseidnumbertaken', 'error', '', $data->idnumber);
        }

        $errorcode = course_validate_dates((array) $data);
        if ($errorcode) {
            throw new \moodle_exception($errorcode);
        }

        if (!isset($data->category)) {
            throw new \coding_exception("No category set for the course");
        }
    }

    /**
     * Create any essentials component's instance for the course
     *
     * @param \stdClass  $data
     * @return container
     */
    protected static function do_create(\stdClass $data): container {
        $container = parent::do_create($data);
        $context = $container->get_context();

        $overviewfilesoptions = course_overviewfiles_options($container->id);
        if ($overviewfilesoptions) {
            // Save the course overviewfiles
            $data = file_postupdate_standard_filemanager(
                $data,
                'overviewfiles',
                $overviewfilesoptions,
                $context,
                'course',
                'overviewfiles',
                0
            );
        }

        // update course format options
        $courseformat = course_get_format($container->id);
        $courseformat->update_course_format_options($data);

        fix_course_sortorder();
        // purge appropriate caches in case fix_course_sortorder() did not change anything
        \cache_helper::purge_by_event('changesincourse');

        // Totara: Save the custom fields.
        $data->id = $container->id;
        customfield_save_data($data, 'course', 'course');

        $eventdata = [
            'objectid' => $container->id,
            'context' => $container->get_context(),
            'other' => [
                'shortname' => $container->shortname,
                'fullname' => $container->fullname
            ]
        ];

        // Trigger a course created event.
        $event = course_created::create($eventdata);
        $event->trigger();

        // Setup the blocks
        blocks_add_default_course_blocks($container);

        // Save any custom role names.
        save_local_role_names($container->id, (array) $data);

        // set up enrolments
        $record = $container->to_record();
        enrol_course_updated(true, $record, $data);

        // Update course tags.
        if (isset($data->tags)) {
            \core_tag_tag::set_item_tags(
                'core',
                'course',
                $container->id,
                $container->get_context(),
                $data->tags
            );
        }

        return $container;
    }

    /**
     * @param container $course
     * @param \stdClass $data
     *
     * @return void
     */
    protected static function post_create(container $course, \stdClass $data): void {
        global $CFG;

        if (!function_exists('course_create_sections_if_missing')) {
            require_once("{$CFG->dirroot}/course/lib.php");
        }

        $course_id = $course->get_id();
        $new_sections = [0];

        if (property_exists($data, 'numsections')) {
            if (!is_numeric($data->numsections)) {
                debugging("Property 'numsections' needs to be an integer", DEBUG_DEVELOPER);
            } else {
                // Create default section and initial sections if specified
                // (unless they've already been created earlier).
                if (0 != $data->numsections) {
                    $new_sections = range(0, $data->numsections);
                }
            }
        }

        course_create_sections_if_missing($course_id, $new_sections);
    }


    /**
     * @param \stdClass  $data
     * @return void
     */
    protected function pre_update(\stdClass $data): void {
        global $CFG, $SITE;
        require_once("{$CFG->dirroot}/course/lib.php");

        if (property_exists($data, 'id')) {
            if ($this->id != $data->id) {
                throw new \coding_exception("Id between data and the container are different");
            } else if ($SITE->id == $data->id) {
                // Prevent changes on front page course.
                throw new \coding_exception("Not allowed to change the front-page");
            }
        }

        // Check we don't have a duplicate shortname.
        if (!empty($data->shortname) && $this->shortname != $data->shortname) {
            if (container_helper::is_container_existing_with_field('shortname', $data->shortname, $this->id)) {
                throw new \moodle_exception('shortnametaken', null, '', $data->shortname);
            }
        }

        // Check we don't have a duplicate idnumber.
        if (!empty($data->idnumber) && $this->idnumber != $data->idnumber) {
            if (container_helper::is_container_existing_with_field('idnumber', $data->idnumber, $this->id)) {
                throw new \moodle_exception('courseidnumbertaken', 'error', '', $data->idnumber);
            }
        }

        $errorcode = course_validate_dates((array) $data);
        if ($errorcode) {
            throw new \moodle_exception($errorcode);
        }
    }

    /**
     * Update a course.
     *
     * Please note this functions does not verify any access control,
     * the calling code is responsible for all validation (usually it is the form definition).
     *
     * Note: it is hard to used container instance in this update function. Because one of the function down the stream
     * is trying to cast the $course object (which is being assumed as \stdClass) to an array. And so far that there
     * is no function in container to say which properties go to the array or not.
     *
     * @param \stdClass $data  - all the data needed for an entry in the 'course' table
     * @return bool
     */
    protected function do_update(\stdClass $data): bool {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/course/lib.php");

        $oldformat = $this->format;
        if (isset($data->format)) {
            $fm = new \stdClass();
            $fm->format = $data->format;

            $newcourseformat = course_get_format($fm);
            if (!$newcourseformat->supports_news()) {
                $data->newsitems = 0;
            }
        }

        $oldcourse = course_get_format($this->id)->get_course();
        $context = $this->get_context();

        $overviewfilesoptions = course_overviewfiles_options($data->id);
        if ($overviewfilesoptions) {
            $data = file_postupdate_standard_filemanager(
                $data,
                'overviewfiles',
                $overviewfilesoptions,
                $context,
                'course',
                'overviewfiles',
                0
            );
        }

        $old_duedate_settings = [
            'duedate' => $this->duedate,
            'duedateoffsetunit' => $this->duedateoffsetunit,
            'duedateoffsetamount' => $this->duedateoffsetamount,
        ];

        $result = parent::do_update($data);

        if (!$result) {
            return false;
        }

        // update course format options with full course data
        course_get_format($data->id)->update_course_format_options($data, $oldcourse);

        // Save any custom role names.
        save_local_role_names($this->id, (array) $data);

        // update enrol settings
        enrol_course_updated(false, $this, $data);

        // Totara: Update the custom fields.
        customfield_save_data($data, 'course', 'course');

        if ($oldformat !== $this->format) {
            // Remove all options stored for the previous format. We assume that new course format migrated everything
            // it needed watching trigger 'course_updated' and in method format_XXX::update_course_format_options()
            $DB->delete_records(
                'course_format_options',
                [
                    'courseid' => $this->id,
                    'format' => $oldformat
                ]
            );
        }

        // Update enrolled users' duedate settings if needed
        if ($old_duedate_settings['duedate'] != $this->duedate
            || $old_duedate_settings['duedateoffsetunit'] != $this->duedateoffsetunit
            || $old_duedate_settings['duedateoffsetamount'] != $this->duedateoffsetamount) {
            $adhocktask = new update_completion_due_dates_task();
            $adhocktask->set_custom_data(['course_id' => $this->id]);
            $adhocktask->set_component('core_completion');
            \core\task\manager::queue_adhoc_task($adhocktask);
        }

        return true;
    }

    /**
     * @param \stdClass  $data
     * @return void
     */
    protected function post_update(\stdClass $data): void {
        global $CFG;

        // Performance improvement - invalidate static caching of course information.
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_course.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

        \completion_criteria_activity::invalidatecache();
        \completion_criteria_course::invalidatecache();

        $context = $this->get_context();

        if (isset($data->tags)) {
            // Update course tags.
            \core_tag_tag::set_item_tags(
                'core',
                'course',
                $this->id,
                $context,
                $data->tags
            );
        }

        // Trigger a course updated event.
        /** @var course_updated $event */
        $event = course_updated::create(
            [
                'objectid' => $this->id,
                'context' => $context,
                'other' => [
                    'shortname' => $this->shortname,
                    'fullname' => $this->fullname
                ]
            ]
        );

        $event->set_legacy_logdata([$this->id, 'course', 'update', "edit.php?id={$this->id}", $this->id]);
        $event->trigger();
    }

    /**
     * @return bool
     */
    public static function is_using_system_category(): bool {
        return false;
    }

    /**
     * Returning the default category id that the course is able to be created within.
     * @return int
     */
    public static function get_default_category_id(): int {
        $course_category = \coursecat::get_default();
        return $course_category->id;
    }

    /**
     * Courses don't sit inside any sub-categories by default, so no need to create them here.
     */
    public static function create_categories(): void {
        // Do nothing.
    }

    /**
     * Let the children, which is Site to extend this function on creating an instance of its own module object.
     *
     * @param \stdClass $newcm
     * @return course_module
     */
    protected function create_module(\stdClass $newcm): module {
        return course_module::create($newcm);
    }

    /**
     * This function will call to {@see module::create()} to create/save the record of course_module.
     *
     * @param \stdClass $moduleinfo
     * @param null      $mform
     *
     * @return module
     */
    public function add_module(\stdClass $moduleinfo, $mform = null): module {
        global $DB, $CFG;

        if (!$this->is_module_allowed($moduleinfo->modulename)) {
            $type = static::get_type();
            throw new \coding_exception(
                "The module '{$moduleinfo->modulename}' is not allowed to be added in container '{$type}'"
            );
        }

        require_once("{$CFG->dirroot}/course/lib.php");
        require_once("{$CFG->dirroot}/course/modlib.php");

        helper::include_modulelib($moduleinfo->modulename);

        // Start the transaction.
        $transaction = $DB->start_delegated_transaction();

        $cloned = course_module_helper::set_moduleinfo_defaults($moduleinfo);
        $cloned->course = $this->id;

        if (!empty($this->groupmodeforce) || !isset($cloned->groupmode)) {
            // Do not set groupmode.
            $cloned->groupmode = 0;
        }

        $introeditor = [];
        if (plugin_supports('mod', $cloned->modulename, FEATURE_MOD_INTRO, true) && isset($cloned->introeditor)) {
            $introeditor = $cloned->introeditor;
            unset($cloned->introeditor);

            // Setup intro and intro format for using in adding module's instance.
            $cloned->intro = $introeditor['text'];
            $cloned->introformat = $introeditor['format'];
        }

        $newcm = course_module_helper::prepare_new_cm($cloned, $this);
        $module = $this->create_module($newcm);

        // Set the coursemodule property for the adding_instance usage.
        $cloned->coursemodule = $module->get_id();
        $fn = "{$cloned->modulename}_add_instance";

        try {
            $instanceid = call_user_func_array($fn, [$cloned, $mform]);
        } catch (\moodle_exception $e) {
            $instanceid = $e;
        }

        if (!$instanceid || !is_number($instanceid)) {
            // Rollback the transaction, because it is broken.
            $transaction->rollback();

            if (!is_number($instanceid)) {
                throw new \coding_exception("Incorrect function '{$fn}'");
            } else {
                throw new \coding_exception("Cannot add new module '{$cloned->modulename}'");
            }
        }

        $module->update_instance($instanceid);
        $modcontext = $module->get_context();
        $module->add_to_section($cloned->section);

        $cloned->instance = $module->get_instance();

        // Add module tags
        if (\core_tag_tag::is_enabled('core', 'course_modules') && isset($cloned->tags)) {
            \core_tag_tag::set_item_tags(
                'core',
                'course_modules',
                $module->get_id(),
                $modcontext,
                $cloned->tags
            );
        }

        // Update embedded links and save files for the actual module instance itself.
        if (!empty($introeditor)) {
            $intro = file_save_draft_area_files(
                $introeditor['itemid'],
                $modcontext->id,
                "mod_{$cloned->modulename}",
                'intro',
                0,
                ['subdirs' => true],
                $introeditor['text']
            );

            $DB->set_field(
                $cloned->modulename,
                'intro',
                $intro,
                ['id' => $module->get_instance()]
            );
        }

        // Trigger event based on the action we did. Api create_from_cm expects modname and id property,
        // and we don't want to modify $moduleinfo since we are returning it.
        $eventdata = clone $cloned;
        $eventdata->modname = $eventdata->modulename;
        $eventdata->id = $module->get_id();

        $event = course_module_created::create_from_cm($eventdata, $modcontext);
        $event->trigger();

        $cloned = edit_module_post_actions($cloned, $this->to_record());
        $transaction->allow_commit();

        // Set any other extra field data to the module
        $module->set_extra_fields($cloned);

        // One last time rebuild cache
        $this->rebuild_cache(true);
        return $module;
    }

    /**
     * Output this very object into the dummy data holder.
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        $course = parent::to_record();
        foreach ($this->extra as $property => $value) {
            if (property_exists($course, $property)) {
                continue;
            }

            $course->{$property} = $value;
        }

        return $course;
    }

    /**
     * Note that this function does not check for the ability to delete course
     * of whoever is in the session.
     *
     * @return void
     */
    public function delete(): void {
        $course_id = $this->get_id();
        $context = \context_course::instance($course_id);

        $plugin_functions = get_plugins_with_function('pre_course_delete');
        $course = $this->to_record();

        foreach ($plugin_functions as $plugin_type => $plugins) {
            foreach ($plugins as $plugin_function) {
                $plugin_function($course);
            }
        }

        remove_course_contents($course_id, $this->show_feedback_on_delete);
        parent::delete();

        if (class_exists('format_base', false)) {
            \format_base::reset_course_cache($course_id);
        }

        // Trigger a course deleted event.
        $event = course_deleted::create(array(
            'objectid' => $course->id,
            'context' => $context,
            'other' => array(
                'shortname' => $course->shortname,
                'fullname' => $course->fullname,
                'idnumber' => $course->idnumber
            )
        ));

        $event->add_record_snapshot('course', $course);
        $event->trigger();
    }
}