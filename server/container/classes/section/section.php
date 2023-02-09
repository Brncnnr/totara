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
namespace core_container\section;

use core\entity\course_section as course_section_entity;
use core\entity\course_section_repository;
use core\orm\query\builder;
use core_container\module\module;
use core_container\module\module_factory;
use core_container\container;
use core_container\cache_helper;
use core_container\factory;

/**
 * A class that represents for a row within table {course_sections}.
 */
abstract class section {
    /**
     * @var course_section_entity
     */
    protected $entity;

    /**
     * Forcing the caller to call the factory method instead of construction method.
     *
     * section constructor.
     * @param course_section_entity $entity
     */
    private function __construct(course_section_entity $entity) {
        if (!$entity->exists()) {
            throw new \coding_exception("Cannot instantiate a section from an invalid entity");
        }

        $this->entity = $entity;
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
     * @return int
     */
    public function get_section_number(): int {
        return $this->entity->section;
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return $this->entity->name;
    }

    /**
     * @return string
     */
    public function get_summary(): string {
        return $this->entity->summary;
    }

    /**
     * @return int
     */
    public function get_visible(): int {
        return $this->entity->visible;
    }

    /**
     * @return int
     */
    public function get_summary_format(): int {
        return $this->entity->summaryformat;
    }

    /**
     * @return array
     */
    public function get_sequence(): array {
        $sequence = $this->entity->sequence;
        if (empty($sequence)) {
            return [];
        }

        return explode(",", $sequence);
    }

    /**
     * @return array
     */
    public function get_availability(): array {
        $availability = $this->entity->availability;
        if (empty($availability)) {
            return [];
        }

        $data = json_decode($availability, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $message = json_last_error_msg();
            throw new \coding_exception("Cannot decode the json data due to: '{$message}'");
        }

        return $data;
    }

    /**
     * Factory method to build section from id.
     *
     * @param int   $id
     * @param bool  $strict
     *
     * @return section|null
     */
    public static function from_id(int $id, bool $strict = true): ?section {
        $repo = course_section_entity::repository();

        if ($strict) {
            /** @var course_section_entity $entity */
            $entity = $repo->find_or_fail($id);
        } else {
            /** @var course_section_entity $entity */
            $entity = $repo->find($id);

            if (empty($entity)) {
                return null;
            }
        }

        return new static($entity);
    }

    /**
     * Pass $strict as false, if the caller does not want exception on missing record.
     *
     * @param int  $containerid
     * @param int  $sectionnumber
     * @param bool $strict
     *
     * @return section|null
     */
    public static function from_section_number(int $containerid, int $sectionnumber, bool $strict = true): ?section {
        /** @var course_section_repository $repo */
        $repo = course_section_entity::repository();
        $entity = $repo->find_by_section_number_and_course($containerid, $sectionnumber, $strict);

        if (null === $entity) {
            return null;
        }

        return new static($entity);
    }

    /**
     * Magic set should be blocked.
     *
     * @param string        $name
     * @param mixed|null    $value
     */
    public function __set(string $name, $value) {
        throw new \coding_exception("Section does not support magic set");
    }

    /**
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        return (object) $this->entity->to_array();
    }

    /**
     * Public factory method to instantiate this section object via record.
     *
     * @param \stdClass $record
     * @return section
     */
    public static function from_record(\stdClass $record): section {
        $entity = new course_section_entity($record);
        return new static($entity);
    }

    /**
     * This function will try to invoke {@see container::get_type()} to strictly retrieving the container.
     *
     * @return container
     */
    public function get_container(): container {
        return factory::from_id($this->entity->course);
    }

    /**
     * Note that this function will not follow the old rule from course.
     * It will just purely create a new section.
     *
     * @param int   $containerid
     * @param int   $position
     *
     * @return section
     */
    public static function create(int $containerid, int $position): section {
        $entity = new course_section_entity();

        $entity->course = $containerid;
        $entity->section = $position;
        $entity->summary = '';
        $entity->summaryformat = FORMAT_HTML;
        $entity->sequence = '';
        $entity->name = null;
        $entity->visible = 1;
        $entity->availability = null;
        $entity->timemodified = time();

        $entity->save();
        return new static($entity);
    }

    /**
     * Adding course module to a section.
     *
     * If $beforemod is specified, then the $cmid will be added prior to the $beforemod. For example, if $cmid with
     * value 1 and $beforemod is specified with value 2 then the actual result will look something like this [1, 2].
     *
     * @param int      $cmid
     * @param int|null $beforemod
     *
     * @return bool
     */
    public function add_cm(int $cmid, int $beforemod = null): bool {
        if (0 == $cmid) {
            debugging("Cannot add invalid course module", DEBUG_DEVELOPER);
            return false;
        }

        $sequence = $this->get_sequence();
        if (!empty($sequence) && in_array($cmid, $sequence)) {
            // So the sequence is not empty, and it has the $cmid within itself. So there is really no point
            // to go further down this.
            debugging(
                "The section '{$this->entity->section}' already had the module '{$cmid}' in place",
                DEBUG_DEVELOPER
            );

            return false;
        }

        if (empty($sequence) || null == $beforemod) {
            $sequence[] = $cmid;
        } else {
            $key = array_keys($sequence, $beforemod);
            if (!empty($key)) {
                $insert = [$cmid, $beforemod];
                array_splice($sequence, $key[0], 1, $insert);
            } else {
                $sequence[] = $cmid;
            }
        }

        $this->entity->sequence = $sequence;
        $this->entity->save();

        // Rebuild the cache, somewhere down the line will also rebuild the cache for the container as well.
        // But most likely to rebuild the memory.
        cache_helper::rebuild_container_cache($this->entity->course, true);
        return true;
    }

    /**
     * Get the section info.
     *
     * @param bool $strict
     * @return \section_info|null
     */
    public function get_info(bool $strict = false): ?\section_info {
        $modinfo = get_fast_modinfo($this->entity->course);
        $strictnes = IGNORE_MISSING;

        if ($strict) {
            $strictnes = MUST_EXIST;
        }

        return $modinfo->get_section_info($this->entity->section, $strictnes);
    }

    /**
     * Removing mod from the sequence of the course's section.
     *
     * @param int $modid
     * @return bool
     */
    public function remove_module(int $modid): bool {
        $sequence = $this->get_sequence();

        $key = array_search($modid, $sequence);
        if (false === $key) {
            return false;
        }

        // Update the sequence of section to remove that mod-id.
        array_splice($sequence, $key, 1);

        $this->entity->sequence = $sequence;
        $this->entity->save();

        cache_helper::rebuild_container_cache($this->entity->course, true);
        return true;
    }

    /**
     * Reloading from the database.
     *
     * @return void
     */
    public function reload(): void {
        $this->entity->refresh();
    }

    /**
     * @return bool
     */
    public function delete(): bool {
        $modules = $this->get_all_modules();

        foreach ($modules as $module) {
            $module->delete();
        }

        $this->entity->delete();
        return true;
    }

    /**
     * Returning a list of module that are placed within this section.
     *
     * @return module[]
     */
    public function get_all_modules(): array {
        $sequence = $this->get_sequence();
        $modules = [];

        $builder = builder::table('course_modules');
        $builder->where_in('id', $sequence);

        $records = $builder->fetch();

        /** @var \stdClass $record */
        foreach ($records as $record) {
            $modules[] = module_factory::from_record($record);
        }

        if (count($sequence) !== count($modules)) {
            $sectionnumber = $this->entity->section;

            debugging(
                "There are missing module(s) in the section '{$sectionnumber}'",
                DEBUG_DEVELOPER
            );
        }

        return $modules;
    }
}