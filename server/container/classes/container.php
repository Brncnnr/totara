<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_container
 */
namespace core_container;

use coding_exception;
use core_container\local\module_supported;
use core_container\module\helper;
use core_container\module\module;
use core_container\module\module_factory;
use core_container\section\section;
use core_container\section\section_factory;
use \container_course\course;

/**
 * @property-read int       $id
 * @property-read int       $category
 * @property-read int       $sortorder
 * @property-read string    $fullname
 * @property-read string    $shortname
 * @property-read string    $idnumber
 * @property-read string    $summary
 * @property-read int       $summaryformat
 * @property-read string    $format
 * @property-read int       $showgrades
 * @property-read int       $newsitems
 * @property-read int       $startdate
 * @property-read int       $enddate
 * @property-read int       $marker
 * @property-read int       $maxbytes
 * @property-read int       $legacyfiles
 * @property-read int       $showreports
 * @property-read int       $visible
 * @property-read int       $visibleold
 * @property-read int       $groupmode
 * @property-read int       $groupmodeforce
 * @property-read int       $defaultgroupingid
 * @property-read string    $lang
 * @property-read string    $calendartype
 * @property-read string    $theme
 * @property-read int       $timecreated
 * @property-read int       $timemodified
 * @property-read int       $requested
 * @property-read int       $enablecompletion
 * @property-read int       $completionstartonenrol
 * @property-read int       $completionprogressonview
 * @property-read int       $completionnotify
 * @property-read int       $audiencevisible
 * @property-read int       $cacherev
 * @property-read int       $coursetype
 * @property-read string    $icon
 * @property-read string    $containertype
 * @property-read int       $duedate
 * @property-read int       $duedateoffsetamount
 * @property-read int       $duedateoffsetunit
 */
abstract class container {
    /**
     * An associated array that represent for a row within table {course}. Using array, because it should
     * be kept away from modifying by the external.
     *
     * @var array
     */
    protected $containerdata;

    /**
     * @var null|\context
     */
    protected $context;

    /**
     * Blocked the construction from the child extending and also from the public usage. There are couples
     * factory methods already. Furthermore, it is recommended to use class {@see \core_container\factory}
     * to instantiate a container.
     *
     * container constructor.
     */
    final protected function __construct() {
        $this->containerdata = [];
        $this->context = null;

        $this->init();
    }

    /**
     * Override this function to set the default value if the container's properties.
     * @return void
     */
    protected function init(): void {
    }

    /**
     * Set the record from database to this protected property. However, it is converted to array, to keep the data
     * separately from bad mutation.
     *
     * @param \stdClass $record
     * @return void
     */
    protected function map_record(\stdClass $record): void {
        global $DB;

        if (!isset($record->id)) {
            throw new \coding_exception("Unable to set the record without id");
        }

        $columns = array_keys($DB->get_columns('course'));
        $properties = get_object_vars($record);

        // Setting up the properties from the record.
        foreach ($properties as $property => $value) {
            // Using array data, because we want the data in memory to NOT be referenced at all.
            if (!in_array($property, $columns)) {
                debugging(
                    "The property '{$property}' does not exist in the list of columns",
                    DEBUG_DEVELOPER
                );
            } else {
                $this->containerdata[$property] = $value;
            }
        }

        if (count($columns) !== count($this->containerdata)) {
            // Setting the default empty properties from columns. If there are any missing.
            foreach ($columns as $column) {
                if (!array_key_exists($column, $this->containerdata)) {
                    $this->containerdata[$column] = null;
                }
            }
        }

        // Set the default container type, because by default for course or site, this property was left empty on
        // creation process.
        $type = static::get_type();

        if (null != $this->containerdata['containertype'] && $type !== $this->containerdata['containertype']) {
            debugging(
                "Container type '{$type}' is not matching with the '{$this->containerdata['containertype']}' in data",
                DEBUG_DEVELOPER
            );

            return;
        }

        $this->containerdata['containertype'] = $type;
    }

    /**
     * @param int $id
     * @return container
     */
    public static function from_id(int $id): container {
        global $DB;

        $params = [
            'id' => $id
        ];

        $record = $DB->get_record('course', $params, '*', MUST_EXIST);
        return static::from_record($record);
    }

    /**
     * Instantiate an object from the record.
     *
     * @param \stdClass $record
     * @return container
     */
    public static function from_record(\stdClass $record): container {
        $container = new static();
        $container->map_record($record);

        return $container;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Magic get method, mostly it will be getting properties within the table '{course}'.
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name) {
        if (!array_key_exists($name, $this->containerdata)) {
            debugging("The property '{$name}' is not found within container data", DEBUG_DEVELOPER);
            return null;
        }

        return $this->containerdata[$name];
    }

    /**
     * Magic setter is not supported by the container and its child.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    final public function __set($name, $value): void {
        throw new \coding_exception("Dynamically setting the properties is blocked");
    }

    /**
     * Magic __isset method will allow the external caller for function {@see isset} to use access modifier of
     * the container.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool {
        if (!array_key_exists($name, $this->containerdata)) {
            return false;
        }

        return isset($this->containerdata[$name]);
    }

    /**
     * The external caller can use this method to get an actual row from the table {course}.
     * Note that this function will not include any field(s) from property $extra.
     *
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        global $DB;

        $columns = array_keys($DB->get_columns('course'));
        $record = new \stdClass();

        foreach ($columns as $column) {
            if (array_key_exists($column, $this->containerdata)) {
                $record->{$column} = $this->containerdata[$column];
                continue;
            }

            debugging("The column '{$column}' is not existing in container-data", DEBUG_DEVELOPER);
        }

        return $record;
    }

    /**
     * @return \context
     */
    public function get_context(): \context {
        if (null == $this->context) {
            $this->context = \context_course::instance($this->id);
        }

        return $this->context;
    }

    /**
     * Default course visibility. But could be extended via container implementation. Checking the visibility of
     * the current container to the target user. If target user is not defined, user in session will be used.
     *
     * @param int|null $userid If userid is not being set, it should be falled back to $USER
     * @return bool
     */
    public function is_visible(int $userid = null): bool {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/coursecatalog/lib.php");

        [$vsql, $vparams] = totara_visibility_where($userid);

        $params = array_merge(['courseid' => $this->id], $vparams);
        $sql = "SELECT 1 FROM {course} course WHERE course.id = :courseid AND {$vsql}";

        $record = $DB->get_record_sql($sql, $params);
        return $record != false;
    }

    /**
     * Rebuild the cache for the container.
     *
     * @param bool  $clearonly
     * @return void
     */
    public function rebuild_cache(bool $clearonly = false): void {
        cache_helper::rebuild_container_cache($this->id, $clearonly);
        $this->reload();
    }

    /**
     * Reload properties from the database record.
     *
     * @return void
     */
    public function reload(): void {
        global $DB;

        // Cached the id, before rebuilding the properties
        $id = $this->id;

        // After rebuild the cache, start resetting its own properties as well.
        $this->containerdata = [];
        $this->context = null;

        $record = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
        $this->map_record($record);
    }

    /**
     * Setter for the actual field data within container.
     *
     * @param string $name
     * @param mixed  $value
     * @return void
     */
    protected function set_field_raw(string $name, $value): void {
        if (array_key_exists($name, $this->containerdata)) {
            $this->containerdata[$name] = $value;
        }
    }

    /**
     * Given the section number, this function will return the section object for it.
     *
     * @param int $section_number
     * @param bool  $strict
     *
     * @return section|null
     */
    public function get_section(int $section_number, bool $strict = true): ?section {
        return section_factory::from_section_number($this->id, $section_number, $strict);
    }

    /**
     * Get the module object that should be belong to this very container. It is calling to one of the factory function
     * which is {@see module::from_id()}
     *
     * @param int  $cmid
     * @param bool $strict
     *
     * @return module|null
     */
    public function get_module(int $cmid, bool $strict = true): ?module {
        $module = module_factory::from_id($cmid, $strict);
        if (null == $module) {
            return null;
        }

        if ($module->get_container_id() != $this->id) {
            debugging(
                "The module is not belonging to this very container ({$this->id})",
                DEBUG_DEVELOPER
            );
        }

        return $module;
    }

    /**
     * @return section[]
     */
    public function get_sections(): array {
        global $DB;

        $records = $DB->get_records('course_sections', ['course' => $this->id], 'section ASC');
        $sections = [];

        foreach ($records as $record) {
            $sections[] = section_factory::from_record($record);
        }

        return $sections;
    }

    /**
     * Returns the localised human-readable names of all modules that are supported for the container.
     * Returning Array<string, string>
     *
     * @param bool $plural              If true returns the plural forms of the names
     * @param bool $include_disabled    This is to tell the loader whether we are going to included the disabled
     *                                  module(s) in the result set or not.
     *
     * @return string[]
     */
    public static function get_module_types_supported(bool $plural = false,
                                                      bool $include_disabled = false): array {
        $modsupported = module_supported::instance();
        return $modsupported->get_for_container(static::get_type(), $plural, $include_disabled);
    }

    /**
     * Checking whether the module is allowed to be added into the specific container or not.
     *
     * @param string $modname
     * @return bool
     */
    public function is_module_allowed(string $modname): bool {
        $moduletypes = static::get_module_types_supported(false, true);
        return isset($moduletypes[$modname]);
    }

    /**
     * A method to check whether this container is type of given $type.
     *
     * @param string $type
     * @return bool
     */
    public function is_typeof(string $type): bool {
        return static::get_type() === $type;
    }

    /**
     * This is where the default data should be populated for the record that is about to be stored in
     * table {course}. By default it will set the  containertype for the record if it is not existing.
     *
     * Extending this function to add more default properties values.
     *
     * @param \stdClass $data
     * @return \stdClass
     */
    protected static function normalise_data_on_create(\stdClass $data): \stdClass {
        // Do NOT modify the original data.
        $data = fullclone($data);

        if (!property_exists($data, 'containertype')) {
            $data->containertype = static::get_type();
        }

        // Check if timecreated is given.
        if (!property_exists($data, 'timecreated') || empty($data->timecreated)) {
            $data->timecreated = time();
        }

        $data->timemodified = $data->timecreated;
        return $data;
    }

    /**
     * This is where all the minor system logic check to be happened.
     * Extending this function make it run on more checking.
     *
     * @param \stdClass  $data
     * @return void
     */
    protected static function pre_create(\stdClass $data): void {
        if (!empty($data->shortname)) {
            // Check if the shortname already exists.
            if (container_helper::is_container_existing_with_field('shortname', $data->shortname)) {
                throw new \coding_exception("The container's 'shortname' had already been taken");
            }
        }

        if (!empty($data->idnumber)) {
            // Check if the idnumber already exists.
            if (container_helper::is_container_existing_with_field('idnumber', $data->idnumber)) {
                throw new \coding_exception("The container's 'idnumber' had arleady been taken");
            }
        }
    }

    /**
     * A static method to create an instance of its kind.
     * Create an instance and return a container itself.
     *
     * Please note this functions does not verify any access control,
     * the calling code is responsible for all validation (usually it is the form definition).
     *
     * This is just a very basic functionality to insert a record for table {course}. For adding extra logics around
     * this, the extended container either should fully override this function or extending this function with pre
     * and post code.
     *
     * @param \stdClass  $data
     * @return container
     */
    protected static function do_create(\stdClass $data): container {
        global $DB;

        //check the categoryid - must be given for all new courses
        $category = $DB->get_record('course_categories', ['id' => $data->category], '*', MUST_EXIST);

        // place at beginning of any category
        $data->sortorder = 0;

        if (!isset($data->visible)) {
            // data not from form, add missing visibility info
            $data->visible = $category->visible;
        }

        // Course duedate.
        if (empty($data->duedate_op)) {
            $data->duedate = null;
            $data->duedateoffsetamount = null;
            $data->duedateoffsetunit = null;
        } else if ($data->duedate_op == course::DUEDATEOPERATOR_RELATIVE) {
            $data->duedate = null;
        } else if ($data->duedate_op == course::DUEDATEOPERATOR_FIXED) {
            $data->duedateoffsetamount = null;
            $data->duedateoffsetunit = null;
        }

        $data->visibleold = $data->visible;
        $newid = $DB->insert_record('course', $data);

        // Creating context, by asking for the context.
        \context_course::instance($newid);

        // Using factory to build the container, because we do want it to be cached within session.
        return factory::from_id($newid);
    }

    /**
     * @param container  $container
     * @param \stdClass  $data
     *
     * @return void
     */
    protected static function post_create(container $container, \stdClass $data): void {
        $container_id = $container->get_id();

        if (property_exists($data, 'numsections')) {
            if (!is_numeric($data->numsections)) {
                debugging("Property 'numsections' needs to be an integer", DEBUG_DEVELOPER);
            } else {
                // Create default section and initial sections if specified
                // (unless they've already been created earlier).
                if (0 != $data->numsections) {
                    $newsections = range(0, $data->numsections);
                } else {
                    // Zero is the default for almost all the course
                    $newsections = [0];
                }

                section_factory::create_sections($container_id, $newsections);
            }
        } else {
            section_factory::create_section($container_id, 0);
        }
    }

    /**
     * Note: no actor's id passed here, nor any permission checks should be done in this level, as this functionality
     * is just purely creating record within database, triggering events and lots of other related stuffs that need
     * to be populated about container.
     *
     * If you need to do permission check, please do it before this function was called.
     *
     * @param \stdClass  $data
     * @return container
     */
    public static function create(\stdClass $data): container {
        $data = static::normalise_data_on_create($data);
        static::pre_create($data);

        // Allow the children to have the ability to add default category id before creation.
        // If it is not added, then debugging will help to fail unit tests.
        if (!property_exists($data, 'category')) {
            debugging("No property 'category' within the parameter \$data", DEBUG_DEVELOPER);
            $data->category = static::get_default_category_id();
        }

        $container = static::do_create($data);

        static::post_create($container, $data);
        return $container;
    }

    /**
     * A static method to update the instance of its kind.
     *
     * @param \stdClass  $data
     * @return bool
     */
    public function update(\stdClass $data): bool {
        // Do NOT modify the original data, only modify it inside the function scope.
        $data = fullclone($data);

        if (!property_exists($data, 'timemodified')) {
            $data->timemodified = time();
        }

        if (!property_exists($data, 'id')) {
            // Popuate the record's id, if it is not set.
            $data->id = $this->get_id();
        }

        $this->pre_update($data);

        $result = $this->do_update($data);
        if (!$result) {
            return false;
        }

        $this->post_update($data);
        return true;
    }

    /**
     * This is where all the validation check will be happening.
     * @param \stdClass  $data
     * @return void
     */
    protected function pre_update(\stdClass $data): void {
        global $SITE;

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
                throw new \coding_exception("The container's 'shortname' had already been taken");
            }
        }

        // Check we don't have a duplicate idnumber.
        if (!empty($data->idnumber) && $this->idnumber != $data->idnumber) {
            if (container_helper::is_container_existing_with_field('idnumber', $data->idnumber, $this->id)) {
                throw new \coding_exception("The container's 'idnumber' had arleady been taken");
            }
        }
    }

    /**
     * @param \stdClass  $data
     * @return bool
     */
    protected function do_update(\stdClass $data): bool {
        global $DB;

        $changesincoursecat = false;
        $movecat = false;

        if (property_exists($data, 'category')) {
            if (empty($data->category)) {
                // prevent nulls and 0 in category field
                unset($data->category);
            } else if ($this->category != $data->category) {
                $changesincoursecat = true;
                $movecat = true;
            }
        }

        if (!isset($data->visible)) {
            // data not from form, add missing visibility info
            $data->visible = $this->visible;
        }

        if ($data->visible != $this->visible) {
            // reset the visibleold flag when manually hiding/unhiding course
            $data->visibleold = $data->visible;
            $changesincoursecat = true;
        } else {
            if ($movecat) {
                $newcategory = $DB->get_record('course_categories', ['id' => $data->category]);
                if (empty($newcategory->visible)) {
                    // make sure when moving into hidden category the course is hidden automatically
                    $data->visible = 0;
                }
            }
        }

        // Course duedate.
        if (empty($data->duedate_op)) {
            $data->duedate = null;
            $data->duedateoffsetamount = null;
            $data->duedateoffsetunit = null;
        } else if ($data->duedate_op == course::DUEDATEOPERATOR_RELATIVE) {
            $data->duedate = null;
        } else if ($data->duedate_op == course::DUEDATEOPERATOR_FIXED) {
            $data->duedateoffsetamount = null;
            $data->duedateoffsetunit = null;
        }

        $context = $this->get_context();

        // Update with the new data.
        // TL-22080: lower the frequency of context rebuilds.
        $trans = $DB->start_delegated_transaction();
        $DB->update_record('course', $data);
        if ($movecat) {
            $newparent = \context_coursecat::instance($data->category);
            $context->update_moved($newparent);
        }
        $trans->allow_commit();

        // Make sure the modinfo cache is reset, and bump the cacherev.
        $this->rebuild_cache();

        if ($movecat || (isset($data->sortorder) && $this->sortorder != $data->sortorder)) {
            fix_course_sortorder();
        }

        // purge appropriate caches in case fix_course_sortorder() did not change anything
        \cache_helper::purge_by_event('changesincourse');

        if ($changesincoursecat) {
            \cache_helper::purge_by_event('changesincoursecat');
        }

        return true;
    }

    /**
     * @param \stdClass  $data
     * @return void
     */
    protected function post_update(\stdClass $data): void {
    }

    /**
     * Whether it is a site or not.
     * @return bool
     */
    public static function is_site(): bool {
        return false;
    }

    /**
     * Create and add a module to a container passing by $container.
     * This function is for the replacement of {@see add_moduleinfo}
     *
     * @param \stdClass $moduleinfo Data of the module.
     * @param null      $mform      This is required by an existing hack to deal with
     *                              files during MODULENAME_add_instance()
     *
     * @return module
     */
    public function add_module(\stdClass $moduleinfo, $mform = null): module {
        if (!$this->is_module_allowed($moduleinfo->modulename)) {
            $type = static::get_type();
            throw new \coding_exception(
                "The module is not allowed to be added in container '{$type}'"
            );
        }

        helper::include_modulelib($moduleinfo->modulename);

        // Do NOT modify the original data.
        $cloned = fullclone($moduleinfo);

        $newcm = helper::prepare_new_cm($moduleinfo, $this);
        $module = module_factory::create_module($this->id, $newcm);

        // Set the course module property for the adding_instance usage.
        $cloned->coursemodule = $module->get_id();

        if (empty($moduleinfo->instanceid)) {
            $fn = "{$cloned->modulename}_add_instance";
            $instanceid = call_user_func_array($fn, [$cloned, $mform]);

            if (!$instanceid || !is_number($instanceid)) {
                if (!is_number($instanceid)) {
                    throw new \coding_exception("Incorrect function '{$fn}'");
                } else {
                    throw new \coding_exception("Cannot add new module '{$cloned->modulename}'");
                }
            }
        } else {
            $instanceid = $moduleinfo->instanceid;
        }

        $module->update_instance($instanceid);
        $module->get_context();

        // Course_modules and course_sections each contain a reference to each other.
        // So we have to update one of them twice.
        if (property_exists($cloned, 'section')) {
            $module->add_to_section($cloned->section);
        } else {
            debugging(
                "No property 'section' defined in parameter \$moduleinfo, will default to section zero",
                DEBUG_DEVELOPER
            );

            $module->add_to_section(0);
        }

        return $module;
    }

    /**
     * A type string that would appear within the course table, under field containertype.
     *
     * @return string
     */
    final public static function get_type(): string {
        $cls = get_called_class();
        if (self::class === $cls) {
            throw new \coding_exception("Parent container should not be able to call this function");
        }

        return container_helper::get_container_type_from_classname($cls);
    }

    /**
     * Note that this function does not check for the capability of user who is deleting
     * the container itself. It should be done prior to the point where this API is called.
     *
     * @return void
     */
    public function delete(): void {
        global $DB;

        $id = $this->get_id();
        \context_helper::delete_instance(CONTEXT_COURSE, $id);

        $DB->delete_records('course', ['id' => $id]);
        $DB->delete_records('course_format_options', ['courseid' => $id]);
    }

    /**
     * A flag to tell whether the container is belonging to the category where
     * it is not maintain-able by the users. Which means it is only being maintained by the
     * system only and these categories that are holding this container will not be shown
     * to the page.
     *
     * @return bool
     */
    public static function is_using_system_category(): bool {
        return true;
    }

    /**
     * Returning a default categoryid which the container should be belonging to if no category provide.
     *
     * @return int
     */
    public static function get_default_category_id(): int {
        $container_type = static::get_type();
        return container_category_helper::get_default_category_id($container_type);
    }

    /**
     * Create the default course categories for this container type.
     */
    public static function create_categories(): void {
        $container_type = static::get_type();
        container_category_helper::create_container_categories($container_type);
    }

    /**
     * Returning the view url of the container.
     * @return \moodle_url
     */
    abstract public function get_view_url(): \moodle_url;
}