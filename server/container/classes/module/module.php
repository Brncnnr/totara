<?php
/**
 * This file is part of Totara Learn
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
 * @package core_container
 */
namespace core_container\module;

use container_course\course_helper as course_helper;
use core\entity\course_module as course_module_entity;
use core_container\cache_helper;
use core_container\container;
use core_container\factory;
use core_container\section\section;
use core_container\section\section_factory;

/**
 * Base model class for course's module.
 */
abstract class module {
    /**
     * @var course_module_entity
     */
    protected $entity;

    /**
     * @var null|\context_module
     */
    protected $context;

    /**
     * The exact name that is getting from table {modules}.
     *
     * @var null|string
     */
    protected $modulename;

    /**
     * @var section
     */
    protected $sectionobject;

    /**
     * module constructor.
     * @param course_module_entity $entity
     */
    private function __construct(course_module_entity $entity) {
        if (!$entity->exists()) {
            throw new \coding_exception("Cannot instantiate a module from invalid entity");
        }

        $this->entity = $entity;
        $this->context = null;
        $this->modulename = null;
        $this->sectionobject = null;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->entity->id;
    }

    /**
     * @return int
     */
    public function get_container_id(): int {
        return $this->entity->course;
    }

    /**
     * @return int|null
     */
    public function get_instance(): ?int {
        return $this->entity->instance;
    }

    /**
     * @return int
     */
    public function get_time_added(): int {
        return $this->entity->added;
    }

    /**
     * Returns the completion tracking constants, which is one of either
     * these following:
     * + {@see COMPLETION_TRACKING_NONE}
     * + {@see COMPLETION_TRACKING_MANUAL}
     * + {@see COMPLETION_TRACKING_AUTOMATIC}
     *
     * @return int
     */
    public function get_completion(): int {
        return $this->entity->completion;
    }

    /**
     * Lazy loading the context module.
     *
     * @return \context_module
     */
    public function get_context(): \context_module {
        if (null == $this->context) {
            $id = $this->entity->id;
            $this->context = \context_module::instance($id);
        }

        return $this->context;
    }

    /**
     * Getting the module name from table '{modules}'. Not the name of the actual
     * module instance.
     *
     * @param bool  $strict
     * @return string
     */
    public function get_modulename(bool $strict = false): ?string {
        global $DB;

        if (null === $this->modulename) {
            $strictnes = IGNORE_MISSING;
            if ($strict) {
                $strictnes = MUST_EXIST;
            }

            $modulename = $DB->get_field(
                'modules',
                'name',
                ['id' => $this->entity->module],
                $strictnes
            );

            if (!$modulename) {
                return null;
            }

            $this->modulename = (string) $modulename;
        }

        return $this->modulename;
    }

    /**
     * Check whether this module is a course module.
     *
     * @return boolean
     */
    public function is_course_module(): bool {
        $modules = course_helper::get_all_modules();
        return !empty($modules[$this->get_modulename()]);
    }

    /**
     * @return int|null
     */
    public function get_visible_old(): int {
        $value = $this->entity->visibleold;

        if (null === $value) {
            return 0;
        }

        return $value;
    }

    /**
     * Magic SET is NOT supported.
     *
     * @param string $name
     * @param mixed  $value
     * @return void
     */
    public function __set(string $name, $value) {
        throw new \coding_exception("Magic set is not supported");
    }

    /**
     * Setting the visible and visible-old of the course-module.
     *
     * @param int $visible
     * @return bool
     */
    public function update_visible(int $visible): bool {
        if ($this->entity->visible == $visible) {
            return true;
        }

        $modulename = $this->get_modulename();
        if (null == $modulename) {
            // Invalid modulename
            return false;
        }

        $this->entity->visible = $visible;
        $this->entity->visibleold = $visible;

        $this->entity->save();

        cache_helper::rebuild_container_cache($this->entity->course);
        return true;
    }

    /**
     * Update the course module id number.
     *
     * Note: Do not forget to trigger the event \core\event\course_module_updated.
     *
     * @param string|null $id_number
     * @return bool
     */
    public function update_id_number(?string $id_number): bool {
        if ($this->entity->idnumber != $id_number) {
            $this->entity->idnumber = $id_number;
            $this->entity->save();

            cache_helper::rebuild_container_cache($this->entity->course, true);
            return true;
        }

        return false;
    }

    /**
     * Change the group mode of a course module.
     *
     * Note: Do not forget to trigger the event {@see \core\event\course_module_updated} as it needs
     * to be triggered manually, refer to {@see \core\event\course_module_updated::create_from_cm()}.
     *
     * @param int $group_mode
     * @return bool
     */
    public function update_group_mode(int $group_mode): bool {
        if ($this->entity->groupmode != $group_mode) {
            $this->entity->groupmode = $group_mode;
            $this->entity->save();

            cache_helper::rebuild_container_cache($this->entity->course, true);
            return true;
        }

        return false;
    }

    /**
     * Convert the a dummy data holder, that has fields from function {@link get_coursemodule_from_id}
     *
     * @param bool      $strict
     * @param int|null  $sectionnumber
     * @return \stdClass|null
     */
    public function get_cm_record(bool $strict = false, int $sectionnumber = null): ?\stdClass {
        // Setup strictnes.
        $strictnes = MUST_EXIST;
        if (!$strict) {
            $strictnes = IGNORE_MISSING;
        }

        // Setup the section number.
        if (null == $sectionnumber) {
            $sectionnumber = false;
        }

        // Note: Using $this->modulename because the function itself can accept NULL as well as the string.
        $cm = get_coursemodule_from_id(
            $this->modulename,
            $this->entity->id,
            $this->entity->course,
            $sectionnumber,
            $strictnes
        );

        if (!$cm) {
            return null;
        }

        return $cm;
    }

    /**
     * Convert to a dummy data holder that is mapped with the table columns.
     *
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        return (object) $this->entity->to_array();
    }

    /**
     * @param int $visible_old
     * @return bool
     */
    public function update_visible_old(int $visible_old): bool {
        $this->entity->visibleold = $visible_old;
        $this->entity->save();

        return true;
    }

    /**
     * @param int $sectionnumber
     * @param int $beforemod
     *
     * @return bool
     */
    public function add_to_section(int $sectionnumber, int $beforemod = null): bool {
        $container = $this->get_container();
        $section = $container->get_section($sectionnumber, true);

        $result = $section->add_cm($this->entity->id, $beforemod);
        if (!$result) {
            return false;
        }

        $section_id = $section->get_id();

        $this->entity->section = $section_id;
        $this->entity->save();

        return true;
    }

    /**
     * This is where the default data of the module info data is being populated.
     *
     * @param \stdClass $data   The module info data
     * @return \stdClass
     */
    protected function pre_update(\stdClass $data): \stdClass {
        // At this point, feel fre to modify the $data as it has already been cloned.
        $modulename = $this->get_modulename(true);
        helper::include_modulelib($modulename);

        if (!property_exists($data, 'coursemodule')) {
            // Setting coursemodule for $data if it is not set.
            $data->coursemodule = $this->entity->id;
        }

        if (!property_exists($data, 'instance')) {
            // Property 'instance' is needed for the callback functions in the actual course_module (instance) level.
            $data->instance = $this->entity->instance;
        }

        $container = $this->get_container();
        if (property_exists($data, 'course') && $container->id != $data->course) {
            debugging(
                "The value of property 'course' of \$data is not match with the current module's 'course'",
                DEBUG_DEVELOPER
            );
        }

        $data->course = $container->id;

        if (!empty($container->groupmodeforce) || !isset($data->groupmode)) {
            // Keep original.
            $data->groupmode = $this->entity->groupmode;
        }

        return $data;
    }

    /**
     * Function that is doing actual update on the module
     *
     * @param \stdClass             $data   The module info data that is represent for table {course_modules}. And also
     *                                      the data that is being used for the actual module instance.
     * @param \MoodleQuickForm|null $mform  the mform is required by some specific module in the function
     *                                      MODULE_update_instance(). This is due to a hack in this function
     *
     * @return void
     */
    protected function do_update(\stdClass $data, $mform = null): void {
        if (property_exists($data, 'groupmode')) {
           $this->entity->groupmode = $data->groupmode;
        }

        if (isset($data->groupingid)) {
            $this->entity->groupingid = $data->groupingid;
        }

        if (isset($data->showdescription)) {
            $this->entity->showdescription = $data->showdescription;
        } else {
            $this->entity->showdescription = 0;
        }

        $this->entity->save();
        $modulename = $this->get_modulename();

        // Start calling to the module function of updating instance.
        $fn = "{$modulename}_update_instance";
        if (!function_exists($fn)) {
            throw new \coding_exception("No module function '{$fn}'");
        }

        $result = call_user_func_array($fn, [$data, $mform]);
        if (!$result) {
            throw new \coding_exception("Cannot update module '{$modulename}'");
        }

        // Updating idnumber
        if (property_exists($data, 'cmidnumber')) {
            // Check for property 'cmidnumber' first.
            $this->update_id_number($data->cmidnumber);
        } else if (property_exists($data, 'idnumber')) {
            // Then we check for property 'idnumber'.
            $this->update_id_number($data->idnumber);
        }

        // Updating the visible
        if (isset($data->visible)) {
            $this->update_visible((int) $data->visible);
        }
    }

    /**
     * Post update is where we are rebuilding the cache and start triggering an event (if needed).
     *
     * @param \stdClass $data   The module info data
     * @return void
     */
    protected function post_update(\stdClass $data): void {
        $container = $this->get_container();
        $container->rebuild_cache(true);
    }

    /**
     * Updating module and its instance.
     *
     * @param \stdClass             $data   module info data
     * @param \MoodleQuickForm|null $mform  the mform is required by some specific module in the function
     *                                      MODULE_update_instance(). This is due to a hack in this function.
     *
     * @return void
     */
    public function update(\stdClass $data, $mform = null): void {
        // Do NOT modify the original data.
        $data = fullclone($data);
        $moduleinfo = $this->pre_update($data);

        $this->do_update($moduleinfo, $mform);
        $this->post_update($moduleinfo);
    }

    /**
     * Set the instance
     * @param int $instance
     *
     * @return bool
     */
    public function update_instance(int $instance): bool {
        $this->entity->instance = $instance;
        $this->entity->save();

        return true;
    }

    /**
     * Changes the course module name.
     * @param string $name  The new value of name
     * @return bool
     */
    final public function update_name(string $name): bool {
        $modulename = $this->get_modulename(true);
        helper::include_modulelib($modulename);

        $result = $this->do_update_name($name);

        if (!$result) {
            return false;
        }

        // Update calendar events with the new name.
        $this->refresh_module_events();
        $this->reload();
        return true;
    }

    /**
     * Changes the course module name.
     * @param string $name  The new value of name
     * @return bool
     */
    protected function do_update_name(string $name): bool {
        global $CFG, $DB;

        $cm = $this->get_cm_record(true);

        $record = new \stdClass();
        $record->id = $this->entity->instance;

        if (!empty($CFG->formatstringstriptags)) {
            $record->name = clean_param($name, PARAM_TEXT);
        } else {
            $record->name = clean_param($name, PARAM_CLEANHTML);
        }

        if ($record->name === $cm->name || '' === strval($record->name)) {
            return false;
        }

        // Update actual instance table.
        $record->timemodified = time();
        $DB->update_record($cm->modname, $record);

        return true;
    }

    /**
     * @return void
     */
    protected function refresh_module_events(): void {
        $modulename = $this->get_modulename(true);
        helper::include_modulelib($modulename);

        $fn = "{$modulename}_refresh_events";

        if (function_exists($fn)) {
            call_user_func($fn, $this->entity->course);
        }
    }

    /**
     * Retrieve a cm_info, propbably it is cached. Please be aware.
     *
     * @return \cm_info
     */
    public function get_cminfo(): \cm_info {
        $modinfo = get_fast_modinfo($this->entity->course);
        return $modinfo->get_cm($this->entity->id);
    }

    /**
     * @return int
     */
    public function get_visible(): int {
        $value = $this->entity->visible;
        if (null === $value) {
            return 0;
        }

        return $value;
    }

    /**
     * This function only run delete function to the actual module itself and call to the function of deleting
     * instance, example {@see facetoface_delete_instance()} or {@see forum_delete_instance()}. Furthermore, it will
     * also call the delete the context relate to it.
     *
     * This function will not call to any event or any file system deletion, it should be extended
     * child responsibility. As well as the completion and other related components.
     *
     * @param bool $async
     *
     * @return bool
     */
    public function delete(bool $async = false): bool {
        global $CFG, $DB;

        if ($async) {
            return $this->async_delete();
        }

        require_once("{$CFG->dirroot}/calendar/lib.php");
        $container = $this->get_container();

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

        $result = call_user_func_array($deleteinstancefunction, [$this->entity->instance]);

        if (!$result) {
            throw new \coding_exception("Cannot delete the module '{$modulename}' instance");
        }

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

        $container->rebuild_cache(true);
        return true;
    }

    /**
     * Schedule a module for deletion. The real deletion of the module is handled by the task, which calls
     * the function {@link \core_container\module\module::delete}
     *
     * @return bool
     */
    public function async_delete(): bool {
        $modulename = $this->get_modulename(true);
        helper::include_modulelib($modulename);

        $fn = "{$modulename}_delete_instance";

        // Ensure the delete_instance function exists for this module.
        if (!function_exists($fn)) {
            throw new \coding_exception(
                "Cannot delete this module as the function {$modulename}_delete_instance is missing ".
                "in mod/{$modulename}/lib.php."
            );
        }

        // We are going to defer the deletion as we can't be sure how long the module's pre_delete code will run for.
        $this->update_deletion_in_progress(true);

        cache_helper::rebuild_container_cache($this->entity->course, true);
        return true;
    }

    /**
     * Returning a section object of this module.
     * @return section|null
     */
    public function get_section(): ?section {
        if (null === $this->sectionobject) {
            if (0 == $this->entity->section) {
                return null;
            }

            $this->sectionobject = section_factory::from_id($this->entity->section);
        }

        return $this->sectionobject;
    }

    /**
     * Whether the deletion is pending or not.
     *
     * @return bool
     */
    public function is_deletion_pending(): bool {
        return 1 == $this->entity->deletioninprogress;
    }

    /**
     * Moving this very section to the new section, that is specified by $sectionnumber.
     *
     * @param int       $sectionnumber  The number of section, not id
     * @param int|null  $beforemod      The id of the module to move this module in front of.
     *
     * @return bool
     */
    public function move_to_section(int $sectionnumber, int $beforemod = null): bool {
        $container = $this->get_container();
        $newsection = $container->get_section($sectionnumber, false);

        if (null === $newsection) {
            return false;
        }

        // Remove the module from old section first.
        $oldsection = $this->get_section();
        $result = $oldsection->remove_module($this->entity->id);

        if (!$result) {
            throw new \coding_exception("Could not delete module from existing section");
        }

        $newsectionid = $newsection->get_id();
        if ($this->entity->section != $newsectionid) {
            //If moving to a hidden section, then hide module.
            $newvisible = $newsection->get_visible();
            if (!$newvisible && $this->entity->visible) {
                // Module was visible but must become hidden after moving to hidden section.
                // Set visibleold to 1 so module will be visible when section is made visible.
                $this->update_visible(0);
                $this->update_visible_old(1);
            } else if ($newvisible && !$this->entity->visible) {
                // Hidden module was moved to the visible section, restore the module visibility from visibleold.
                $visibleold = $this->entity->visibleold;
                $this->update_visible($visibleold);
            }
        }

        $newsectionumber = $newsection->get_section_number();
        $this->add_to_section($newsectionumber, $beforemod);
        return true;
    }

    /**
     * This function will try to call {@see container::get_type()} to help on strictly querying the container, within
     * module level. However, to get the container type, it is depending whether the constant CONTAINER_CLASS is
     * being set or not.
     *
     * @return container
     */
    public function get_container(): container {
        return factory::from_id($this->entity->course);
    }

    /**
     * Update the table record for field 'deletioninprogress'. Mostly will be called
     * within function {@link \core_container\module\module::flag_for_ansyc_delete}
     *
     * @param bool $value
     * @return void
     */
    public function update_deletion_in_progress(bool $value): void {
        $this->entity->deletioninprogress = $value;
        $this->entity->save();
    }

    /**
     * Reloading this very object with the properties from table {course_modules}
     * @return void
     */
    public function reload(): void {
        // Reset modulename and context.
        $this->modulename = null;
        $this->context = null;
        $this->sectionobject = null;

        $this->entity->refresh();
    }

    /**
     * @param int  $id
     * @param bool $strict
     *
     * @return static|null
     */
    public static function from_id(int $id, bool $strict = true): ?module {
        $repo = course_module_entity::repository();

        if ($strict) {
            /** @var course_module_entity $entity */
            $entity = $repo->find_or_fail($id);
        } else {
            /** @var course_module_entity $entity */
            $entity = $repo->find($id);

            if (empty($entity)) {
                return null;
            }
        }

        return new static($entity);
    }

    /**
     * Build the module object from a course module entity.
     *
     * @param course_module_entity $entity
     *
     * @return static
     */
    public static function from_entity(course_module_entity $entity): module {
        return new static($entity);
    }

    /**
     * Build the module object from the actuall record object. But it MUST be the proper record from
     * table {course_modules}.
     *
     * @param \stdClass $record
     *
     * @return static
     */
    public static function from_record(\stdClass $record): module {
        $entity = new course_module_entity($record);
        return new static($entity);
    }

    /**
     * Creating a new record of the course module, but return this very object.
     *
     * @param \stdClass $data
     * @return module
     */
    public static function create(\stdClass $data): module {
        global $DB;

        // Do NOT modify the original data.
        $cm = clone $data;
        if (!isset($cm->course)) {
            throw new \coding_exception("There is no 'course' property in \$data");
        }

        $cm->added = time();
        if (isset($cm->id)) {
            debugging("The \$data has already set property 'id'", DEBUG_DEVELOPER);
            unset($cm->id);
        }

        $id = $DB->insert_record('course_modules', $cm);
        if (!$id) {
            throw new \coding_exception("Cannot add a new course module");
        }

        // Rebuild cache, to include this very module.
        cache_helper::rebuild_container_cache($cm->course, true);
        $module = static::from_id($id);

        // Creating context for module, by requesting one.
        $module->get_context();
        return $module;
    }
}
